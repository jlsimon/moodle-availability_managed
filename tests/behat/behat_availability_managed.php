<?php
// This file is part of Moodle - https://moodle.org/
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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by Behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Behat steps for Managed Availability acceptance tests.
 *
 * @package    availability_managed
 * @category   test
 * @copyright  2026 Juan Luis Simon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_availability_managed extends behat_base {
    /**
     * Give an activity a core date condition which always fails.
     *
     * This setup step avoids opening the activity editor, so the scenario
     * remains focused on whether Managed Availability preserves and combines
     * an unrelated core condition.
     *
     * @Given /^the activity "(?P<activity_string>(?:[^"\\]|\\.)*)" has a past date restriction$/
     * @param string $activity activity name
     */
    public function the_activity_has_a_past_date_restriction(string $activity): void {
        global $DB;

        $sql = "SELECT cm.id, cm.course
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {page} p ON p.id = cm.instance AND m.name = :modname
                 WHERE p.name = :activity";
        $cm = $DB->get_record_sql($sql, ['modname' => 'page', 'activity' => $activity], MUST_EXIST);
        $availability = json_encode([
            'op' => '&',
            'c' => [['type' => 'date', 'd' => '<', 't' => 1356998400]],
            'showc' => [false],
        ], JSON_THROW_ON_ERROR);
        $DB->set_field('course_modules', 'availability', $availability, ['id' => $cm->id]);
        rebuild_course_cache((int) $cm->course, true);
    }
}
