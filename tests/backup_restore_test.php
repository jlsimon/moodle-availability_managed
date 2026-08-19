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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/tests/backup_restore_base_testcase.php');

/**
 * Tests safe behaviour during course backup and restore.
 *
 * @coversNothing
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class backup_restore_test extends \core_backup_backup_restore_base_testcase {
    public function test_restored_course_starts_unmanaged_without_orphan_markers(): void {
        global $DB, $USER;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 1]);
        $module = $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $student = $generator->create_and_enrol($course);
        $group = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
        $section = get_fast_modinfo($course)->get_section_info(1);

        (new course_manager())->enable($course->id, 'closed', $USER->id);
        $service = new dashboard_service();
        $service->set_item_rules($course->id, 'section', $section->id, false, [$group->id], [], $USER->id);
        $service->set_item_rules($course->id, 'cm', $module->cmid, false, [], [$student->id], $USER->id);

        $backupid = $this->perform_backup($course);
        $restoredcourse = $generator->create_course();
        $this->perform_restore($backupid, $restoredcourse);

        $this->assertFalse($DB->record_exists('availability_managed_course', ['courseid' => $restoredcourse->id]));
        $this->assertFalse($DB->record_exists('availability_managed_rule', ['courseid' => $restoredcourse->id]));
        foreach ($DB->get_records('course_sections', ['course' => $restoredcourse->id], '', 'id,availability') as $item) {
            $this->assertStringNotContainsString('"type":"managed"', (string) $item->availability);
        }
        foreach ($DB->get_records('course_modules', ['course' => $restoredcourse->id], '', 'id,availability') as $item) {
            $this->assertStringNotContainsString('"type":"managed"', (string) $item->availability);
        }
    }
}
