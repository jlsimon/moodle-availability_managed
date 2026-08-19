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
 * Resolves and restores the site-wide operating state.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class global_manager {
    /**
     * Return true unless the setting is explicitly disabled.
     */
    public static function is_enabled(): bool {
        $value = get_config('availability_managed', 'globallyenabled');
        return $value === false || (bool) $value;
    }

    /**
     * Reconcile retained courses after global reactivation.
     */
    public static function reconcile_enabled_courses(): int {
        global $DB;
        $courseids = $DB->get_fieldset_select('availability_managed_course', 'courseid', 'enabled = ?', [1]);
        $service = new reconciliation_service();
        $count = 0;
        foreach ($courseids as $courseid) {
            try {
                $service->reconcile_course((int) $courseid);
                $count++;
            } catch (\Throwable $exception) {
                debugging('Managed availability reconciliation failed for course ' . $courseid . ': ' .
                    $exception->getMessage(), DEBUG_DEVELOPER);
            }
        }
        return $count;
    }
}
