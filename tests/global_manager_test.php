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

use availability_managed\local\availability_tree_manager;
use availability_managed\local\course_manager;
use availability_managed\local\global_manager;

/**
 * Site-wide suspension tests.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(global_manager::class)]
final class global_manager_test extends \advanced_testcase {
    public function test_reactivation_reconciles_content_created_while_suspended(): void {
        global $DB;

        $this->resetAfterTest();
        set_config('globallyenabled', 1, 'availability_managed');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $actor = $generator->create_user();
        (new course_manager())->enable($course->id, 'closed', $actor->id);

        set_config('globallyenabled', 0, 'availability_managed');
        $this->assertFalse(global_manager::is_enabled());
        $cm = $generator->create_module('page', ['course' => $course->id]);
        $json = $DB->get_field('course_modules', 'availability', ['id' => $cm->cmid]);
        $this->assertFalse(availability_tree_manager::has_in_json($json));

        set_config('globallyenabled', 1, 'availability_managed');
        $this->assertSame(1, global_manager::reconcile_enabled_courses());
        $json = $DB->get_field('course_modules', 'availability', ['id' => $cm->cmid]);
        $this->assertTrue(availability_tree_manager::has_in_json($json));
    }
}
