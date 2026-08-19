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
 * Enables and disables management for a course.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_manager {
    /** Supported initial states. */
    public const DEFAULT_STATES = ['open', 'closed'];

    /**
     * Enable and initialise a course.
     *
     * @param int $courseid course id
     * @param string $defaultstate open or closed
     * @param int $userid acting user
     * @return reconciliation_result
     */
    public function enable(int $courseid, string $defaultstate, int $userid): reconciliation_result {
        global $DB;

        if (!in_array($defaultstate, self::DEFAULT_STATES, true)) {
            throw new \invalid_parameter_exception('Invalid initial managed state');
        }
        $DB->get_record('course', ['id' => $courseid], 'id', MUST_EXIST);
        $DB->get_record('user', ['id' => $userid], 'id', MUST_EXIST);
        $now = time();
        $state = $DB->get_record('availability_managed_course', ['courseid' => $courseid]);
        if ($state) {
            $state->enabled = 1;
            $state->defaultstate = $defaultstate;
            $state->timemodified = $now;
            $state->usermodified = $userid;
            $DB->update_record('availability_managed_course', $state);
        } else {
            $DB->insert_record('availability_managed_course', (object) [
                'courseid' => $courseid,
                'enabled' => 1,
                'defaultstate' => $defaultstate,
                'timecreated' => $now,
                'timemodified' => $now,
                'usermodified' => $userid,
            ]);
        }

        $result = (new reconciliation_service())->reconcile_course($courseid);
        if ($defaultstate === 'open') {
            $repository = new rule_repository();
            foreach ($DB->get_records('course_sections', ['course' => $courseid], '', 'id') as $section) {
                $repository->upsert($courseid, 'section', (int) $section->id, 'course', $courseid, $userid);
            }
            foreach ($DB->get_records('course_modules', ['course' => $courseid], '', 'id') as $cm) {
                $repository->upsert($courseid, 'cm', (int) $cm->id, 'course', $courseid, $userid);
            }
        } else {
            $DB->delete_records('availability_managed_rule', ['courseid' => $courseid]);
            access_manager::reset_cache();
        }
        (new audit_repository())->record($courseid, 'enable_course', $userid, null, null, null, $defaultstate);
        return $result;
    }

    /**
     * Disable a course and remove only product-owned markers and rules.
     *
     * @param int $courseid course id
     * @param int $userid acting user
     */
    public function disable(int $courseid, int $userid): void {
        global $DB;

        $state = $DB->get_record('availability_managed_course', ['courseid' => $courseid], '*', MUST_EXIST);
        $tree = new availability_tree_manager();
        foreach ($DB->get_records('course_sections', ['course' => $courseid], '', 'id') as $section) {
            $tree->remove_managed_condition_from_section((int) $section->id);
        }
        foreach ($DB->get_records('course_modules', ['course' => $courseid], '', 'id') as $cm) {
            $tree->remove_managed_condition_from_cm((int) $cm->id);
        }
        $state->enabled = 0;
        $state->timemodified = time();
        $state->usermodified = $userid;
        $DB->update_record('availability_managed_course', $state);
        $DB->delete_records('availability_managed_rule', ['courseid' => $courseid]);
        access_manager::reset_cache();
        (new audit_repository())->record($courseid, 'disable_course', $userid);
    }

    /**
     * Check course state.
     *
     * @param int $courseid course id
     * @return bool
     */
    public function is_enabled(int $courseid): bool {
        global $DB;

        return $DB->record_exists('availability_managed_course', ['courseid' => $courseid, 'enabled' => 1]);
    }
}
