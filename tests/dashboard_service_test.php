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
use availability_managed\local\dashboard_service;

/**
 * Dashboard service tests.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(dashboard_service::class)]
final class dashboard_service_test extends \advanced_testcase {
    public function test_replace_rules_and_summary(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $cm = $generator->create_module('page', ['course' => $course->id]);
        $actor = $generator->create_user();
        $student = $generator->create_and_enrol($course);
        $group = $generator->create_group(['courseid' => $course->id]);
        (new course_manager())->enable($course->id, 'closed', $actor->id);
        $service = new dashboard_service();

        $rules = $service->set_item_rules(
            $course->id,
            'cm',
            $cm->cmid,
            false,
            [$group->id],
            [$student->id],
            $actor->id
        );

        $this->assertSame([(int) $group->id], $rules['groupids']);
        $this->assertSame([(int) $student->id], $rules['userids']);
        $this->assertStringContainsString('1', $service->summary($rules));
    }

    public function test_rejects_cross_course_item(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $other = $generator->create_course();
        $cm = $generator->create_module('page', ['course' => $other->id]);
        $actor = $generator->create_user();
        (new course_manager())->enable($course->id, 'closed', $actor->id);

        $this->expectException(\invalid_parameter_exception::class);
        (new dashboard_service())->get_item_rules($course->id, 'cm', $cm->cmid);
    }

    public function test_copy_section_rules_to_children(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 1]);
        $cmone = $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $cmtwo = $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $actor = $generator->create_user();
        $group = $generator->create_group(['courseid' => $course->id]);
        (new course_manager())->enable($course->id, 'closed', $actor->id);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $service = new dashboard_service();
        $service->set_item_rules($course->id, 'section', $section->id, false, [$group->id], [], $actor->id);

        $this->assertSame(2, $service->copy_section_to_children($course->id, $section->id, $actor->id));
        foreach ([$cmone->cmid, $cmtwo->cmid] as $cmid) {
            $rules = $service->get_item_rules($course->id, 'cm', $cmid);
            $this->assertSame([(int) $group->id], $rules['groupids']);
            $this->assertFalse($rules['everyone']);
        }
    }

    public function test_disabled_course_rejects_operational_reads_and_writes(): void {
        $this->resetAfterTest();
        set_config('globallyenabled', 1, 'availability_managed');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $cm = $generator->create_module('page', ['course' => $course->id]);
        $actor = $generator->create_user();
        $manager = new course_manager();
        $manager->enable($course->id, 'open', $actor->id);
        $manager->disable($course->id, $actor->id);

        $this->expectException(\moodle_exception::class);
        (new dashboard_service())->get_item_rules($course->id, 'cm', $cm->cmid);
    }
}
