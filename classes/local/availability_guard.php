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

namespace availability_managed\local;

/**
 * Central operating-state guard for all management entry points.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_guard {
    /**
     * Require both site-wide and course activation.
     *
     * @param int $courseid course id
     */
    public static function require_operational(int $courseid): void {
        global $DB;

        if (!global_manager::is_enabled()) {
            throw new \moodle_exception('globalsuspended_error', 'availability_managed');
        }
        if (!$DB->record_exists('availability_managed_course', ['courseid' => $courseid, 'enabled' => 1])) {
            throw new \moodle_exception('coursedisabled_error', 'availability_managed');
        }
    }

    /**
     * Check course activation without throwing.
     *
     * @param int $courseid course id
     * @return bool
     */
    public static function is_course_enabled(int $courseid): bool {
        global $DB;

        return $DB->record_exists('availability_managed_course', ['courseid' => $courseid, 'enabled' => 1]);
    }
}
