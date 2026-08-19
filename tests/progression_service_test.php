<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace availability_managed;

use availability_managed\local\course_manager;
use availability_managed\local\progression_service;

/**
 * Manual progression tests.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(progression_service::class)]
final class progression_service_test extends \advanced_testcase {
    public function test_open_next_group_uses_course_order(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 3]);
        $actor = $generator->create_user();
        $group = $generator->create_group(['courseid' => $course->id]);
        (new course_manager())->enable($course->id, 'closed', $actor->id);
        $service = new progression_service();

        $first = $service->open_next($course->id, 'group', $group->id, $actor->id);
        $second = $service->open_next($course->id, 'group', $group->id, $actor->id);
        $view = $service->get_view($course->id, 'group', $group->id);

        $this->assertSame($view[0]['sectionid'], $first['sectionid']);
        $this->assertSame($view[1]['sectionid'], $second['sectionid']);
        $this->assertTrue($view[0]['open']);
        $this->assertTrue($view[1]['open']);
        $this->assertFalse($view[2]['open']);
    }

    public function test_open_next_user_skips_course_wide_open_section(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 2]);
        $actor = $generator->create_user();
        $student = $generator->create_and_enrol($course);
        (new course_manager())->enable($course->id, 'open', $actor->id);
        $service = new progression_service();

        $this->assertNull($service->open_next($course->id, 'user', $student->id, $actor->id));
    }

    public function test_rejects_user_from_another_course(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $outsider = $generator->create_user();
        $actor = $generator->create_user();
        (new course_manager())->enable($course->id, 'closed', $actor->id);

        $this->expectException(\invalid_parameter_exception::class);
        (new progression_service())->get_view($course->id, 'user', $outsider->id);
    }

    public function test_disabled_course_rejects_progression(): void {
        $this->resetAfterTest();
        set_config('globallyenabled', 1, 'availability_managed');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $group = $generator->create_group(['courseid' => $course->id]);

        $this->expectException(\moodle_exception::class);
        (new progression_service())->get_view($course->id, 'group', $group->id);
    }
}
