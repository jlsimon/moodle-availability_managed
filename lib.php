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
 * Plugin callbacks.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add the teacher dashboard to course navigation.
 *
 * @param navigation_node $navigation course navigation
 * @param stdClass $course course record
 */
function availability_managed_extend_navigation_course(navigation_node $navigation, stdClass $course): void {
    $context = context_course::instance($course->id);
    if (
        has_capability('availability/managed:manage', $context)
            && \availability_managed\local\availability_guard::is_course_enabled((int) $course->id)
    ) {
        $navigation->add(
            get_string('pluginname', 'availability_managed'),
            new moodle_url('/availability/condition/managed/index.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'managedavailability'
        );
    }
    if (has_capability('availability/managed:manage', $context)) {
        $navigation->add(
            get_string('help', 'availability_managed'),
            new moodle_url('/availability/condition/managed/help.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'managedavailabilityhelp'
        );
    }
    if (has_capability('availability/managed:viewaudit', $context)) {
        $navigation->add(
            get_string('auditlog', 'availability_managed'),
            new moodle_url('/availability/condition/managed/audit.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING
        );
    }
    if (has_capability('availability/managed:configure', $context)) {
        $navigation->add(
            get_string('courseconfiguration', 'availability_managed'),
            new moodle_url('/availability/condition/managed/courseconfig.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'managedavailabilityconfig'
        );
    }
}
