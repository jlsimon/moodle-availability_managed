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
use availability_managed\local\rule_repository;

/**
 * Lightweight lifecycle event observers.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Attach a marker to a new course module in an enabled course.
     *
     * @param \core\event\course_module_created $event event
     */
    public static function course_module_created(\core\event\course_module_created $event): void {
        if (!\availability_managed\local\global_manager::is_enabled()) {
            return;
        }
        if ((new course_manager())->is_enabled((int) $event->courseid)) {
            (new availability_tree_manager())->ensure_managed_condition_for_cm((int) $event->objectid);
            self::apply_default_rule((int) $event->courseid, 'cm', (int) $event->objectid, (int) $event->userid);
        }
    }

    /**
     * Attach a marker to a new section in an enabled course.
     *
     * @param \core\event\course_section_created $event event
     */
    public static function course_section_created(\core\event\course_section_created $event): void {
        if (!\availability_managed\local\global_manager::is_enabled()) {
            return;
        }
        if ((new course_manager())->is_enabled((int) $event->courseid)) {
            (new availability_tree_manager())->ensure_managed_condition_for_section((int) $event->objectid);
            self::apply_default_rule(
                (int) $event->courseid,
                'section',
                (int) $event->objectid,
                (int) $event->userid
            );
        }
    }

    /**
     * Clean rules for a deleted module.
     *
     * @param \core\event\course_module_deleted $event event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        self::delete_item_rules((int) $event->courseid, 'cm', (int) $event->objectid);
    }

    /**
     * Clean rules for a deleted section.
     *
     * @param \core\event\course_section_deleted $event event
     */
    public static function course_section_deleted(\core\event\course_section_deleted $event): void {
        self::delete_item_rules((int) $event->courseid, 'section', (int) $event->objectid);
    }

    /**
     * Clean rules for a deleted group.
     *
     * @param \core\event\group_deleted $event event
     */
    public static function group_deleted(\core\event\group_deleted $event): void {
        global $DB;

        $DB->delete_records('availability_managed_rule', [
            'courseid' => (int) $event->courseid,
            'scope' => 'group',
            'scopeid' => (int) $event->objectid,
        ]);
        \availability_managed\local\access_manager::reset_cache();
    }

    /**
     * Clean all state for a deleted course.
     *
     * @param \core\event\course_deleted $event event
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        global $DB;

        $DB->delete_records('availability_managed_rule', ['courseid' => (int) $event->objectid]);
        $DB->delete_records('availability_managed_course', ['courseid' => (int) $event->objectid]);
        \availability_managed\local\access_manager::reset_cache();
    }

    /**
     * Remove copied markers when a restored course has no managed state.
     *
     * Availability plugins cannot add their own records to Moodle course
     * backups. Leaving the marker without its rules would close restored
     * content, so restored copies start unmanaged and can be enabled by a
     * course manager afterwards.
     *
     * @param \core\event\course_restored $event event
     */
    public static function course_restored(\core\event\course_restored $event): void {
        global $DB;

        $courseid = (int) $event->courseid;
        if ($DB->record_exists('availability_managed_course', ['courseid' => $courseid])) {
            return;
        }

        $tree = new availability_tree_manager();
        foreach ($DB->get_records('course_sections', ['course' => $courseid], '', 'id') as $section) {
            $tree->remove_managed_condition_from_section((int) $section->id);
        }
        foreach ($DB->get_records('course_modules', ['course' => $courseid], '', 'id') as $module) {
            $tree->remove_managed_condition_from_cm((int) $module->id);
        }
        \availability_managed\local\access_manager::reset_cache();
    }

    /**
     * Delete rules for a removed item.
     *
     * @param int $courseid course id
     * @param string $itemtype item type
     * @param int $itemid item id
     */
    private static function delete_item_rules(int $courseid, string $itemtype, int $itemid): void {
        global $DB;

        $DB->delete_records('availability_managed_rule', compact('courseid', 'itemtype', 'itemid'));
        \availability_managed\local\access_manager::reset_cache();
    }

    /**
     * Apply the configured default state to a newly created item.
     *
     * @param int $courseid course id
     * @param string $itemtype item type
     * @param int $itemid item id
     * @param int $userid event user id
     */
    private static function apply_default_rule(int $courseid, string $itemtype, int $itemid, int $userid): void {
        global $DB;

        $state = $DB->get_record('availability_managed_course', ['courseid' => $courseid, 'enabled' => 1]);
        if ($state && $state->defaultstate === 'open') {
            $actorid = $userid ?: (int) get_admin()->id;
            (new rule_repository())->upsert($courseid, $itemtype, $itemid, 'course', $courseid, $actorid);
        }
    }
}
