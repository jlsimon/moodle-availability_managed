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

/**
 * Course audit page.
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('availability/managed:viewaudit', $context);
$PAGE->set_url('/availability/condition/managed/audit.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('auditlog', 'availability_managed'));
$PAGE->set_heading(format_string($course->fullname));

$records = $DB->get_records('availability_managed_audit', ['courseid' => $courseid], 'timecreated DESC', '*', 0, 500);
$table = new html_table();
$table->head = [get_string('date'), get_string('user'), get_string('action'), get_string('item')];
foreach ($records as $record) {
    $user = core_user::get_user($record->userid);
    $table->data[] = [
        userdate($record->timecreated),
        $user ? fullname($user) : get_string('deleteduser', 'core'),
        s($record->action),
        s(($record->itemtype ?? '') . ($record->itemid ? ' #' . $record->itemid : '')),
    ];
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('auditlog', 'availability_managed'));
echo html_writer::table($table);
echo $OUTPUT->footer();
