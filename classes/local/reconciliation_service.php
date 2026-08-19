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
 * Repairs managed markers and removes orphan rule rows.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reconciliation_service {
    /**
     * Reconcile an enabled course.
     *
     * @param int $courseid course id
     * @return reconciliation_result
     */
    public function reconcile_course(int $courseid): reconciliation_result {
        global $DB;

        if (!$DB->record_exists('availability_managed_course', ['courseid' => $courseid, 'enabled' => 1])) {
            throw new \moodle_exception('Course is not enabled for Managed availability');
        }
        $tree = new availability_tree_manager();
        $added = 0;
        $duplicates = 0;
        $checked = 0;
        $sections = $DB->get_records('course_sections', ['course' => $courseid], '', 'id,availability');
        $cms = $DB->get_records('course_modules', ['course' => $courseid], '', 'id,availability');
        foreach ($sections as $section) {
            $count = availability_tree_manager::count_in_json($section->availability);
            $added += $count === 0 ? 1 : 0;
            $duplicates += max(0, $count - 1);
            $tree->ensure_managed_condition_for_section((int) $section->id);
            $checked++;
        }
        foreach ($cms as $cm) {
            $count = availability_tree_manager::count_in_json($cm->availability);
            $added += $count === 0 ? 1 : 0;
            $duplicates += max(0, $count - 1);
            $tree->ensure_managed_condition_for_cm((int) $cm->id);
            $checked++;
        }

        $orphans = 0;
        foreach ($DB->get_records('availability_managed_rule', ['courseid' => $courseid]) as $rule) {
            $table = $rule->itemtype === 'cm' ? 'course_modules' : 'course_sections';
            if (!$DB->record_exists($table, ['id' => $rule->itemid, 'course' => $courseid])) {
                $DB->delete_records('availability_managed_rule', ['id' => $rule->id]);
                $orphans++;
            }
        }
        access_manager::reset_cache();
        return new reconciliation_result($added, $duplicates, $orphans, $checked);
    }
}
