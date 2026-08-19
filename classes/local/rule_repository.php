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
 * Persistence boundary for managed availability rules.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_repository {
    /** Supported item types. */
    public const ITEM_TYPES = ['section', 'cm'];

    /** Supported scopes. */
    public const SCOPES = ['course', 'group', 'user'];

    /**
     * Return enabled rules for a course.
     *
     * @param int $courseid course id
     * @return array
     */
    public function get_enabled_for_course(int $courseid): array {
        global $DB;

        return $DB->get_records('availability_managed_rule', [
            'courseid' => $courseid,
            'enabled' => 1,
        ], 'id ASC');
    }

    /**
     * Check whether management is active for a course.
     *
     * @param int $courseid course id
     * @return bool
     */
    public function is_course_enabled(int $courseid): bool {
        global $DB;

        return $DB->record_exists('availability_managed_course', ['courseid' => $courseid, 'enabled' => 1]);
    }

    /**
     * Create or enable a rule.
     *
     * @param int $courseid course id
     * @param string $itemtype section or cm
     * @param int $itemid section or course-module id
     * @param string $scope course, group, or user
     * @param int $scopeid target id
     * @param int $usermodified acting user id
     * @return \stdClass stored rule
     */
    public function upsert(
        int $courseid,
        string $itemtype,
        int $itemid,
        string $scope,
        int $scopeid,
        int $usermodified
    ): \stdClass {
        global $DB;

        $this->validate($courseid, $itemtype, $itemid, $scope, $scopeid, $usermodified);
        $conditions = compact('courseid', 'itemtype', 'itemid', 'scope', 'scopeid');
        $now = time();
        $existing = $DB->get_record('availability_managed_rule', $conditions);
        if ($existing) {
            $existing->enabled = 1;
            $existing->timemodified = $now;
            $existing->usermodified = $usermodified;
            $DB->update_record('availability_managed_rule', $existing);
            access_manager::reset_cache();
            return $existing;
        }

        $record = (object) ($conditions + [
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $usermodified,
        ]);
        $record->id = $DB->insert_record('availability_managed_rule', $record);
        access_manager::reset_cache();
        return $record;
    }

    /**
     * Delete one rule if it exists.
     *
     * @param int $courseid course id
     * @param string $itemtype section or cm
     * @param int $itemid item id
     * @param string $scope target scope
     * @param int $scopeid target id
     */
    public function delete(int $courseid, string $itemtype, int $itemid, string $scope, int $scopeid): void {
        global $DB;

        self::validate_names($itemtype, $scope);
        $DB->delete_records('availability_managed_rule', compact('courseid', 'itemtype', 'itemid', 'scope', 'scopeid'));
        access_manager::reset_cache();
    }

    /**
     * Validate a new rule and all of its course relationships.
     *
     * @param int $courseid course id
     * @param string $itemtype item type
     * @param int $itemid item id
     * @param string $scope target scope
     * @param int $scopeid target id
     * @param int $usermodified acting user id
     */
    private function validate(
        int $courseid,
        string $itemtype,
        int $itemid,
        string $scope,
        int $scopeid,
        int $usermodified
    ): void {
        global $DB;

        self::validate_names($itemtype, $scope);
        $DB->get_record('course', ['id' => $courseid], 'id', MUST_EXIST);
        $DB->get_record('user', ['id' => $usermodified], 'id', MUST_EXIST);

        $itemtable = $itemtype === 'cm' ? 'course_modules' : 'course_sections';
        $coursefield = $itemtype === 'cm' ? 'course' : 'course';
        if (!$DB->record_exists($itemtable, ['id' => $itemid, $coursefield => $courseid])) {
            throw new \invalid_parameter_exception('The managed item does not belong to the course');
        }

        if ($scope === 'course' && $scopeid !== $courseid) {
            throw new \invalid_parameter_exception('Course scope id must equal course id');
        }
        if ($scope === 'group' && !$DB->record_exists('groups', ['id' => $scopeid, 'courseid' => $courseid])) {
            throw new \invalid_parameter_exception('The group does not belong to the course');
        }
        if ($scope === 'user') {
            $context = \context_course::instance($courseid);
            if (!is_enrolled($context, $scopeid, '', true)) {
                throw new \invalid_parameter_exception('The user is not actively enrolled in the course');
            }
        }
    }

    /**
     * Validate enum-like values.
     *
     * @param string $itemtype item type
     * @param string $scope target scope
     */
    private static function validate_names(string $itemtype, string $scope): void {
        if (!in_array($itemtype, self::ITEM_TYPES, true)) {
            throw new \invalid_parameter_exception('Invalid managed item type');
        }
        if (!in_array($scope, self::SCOPES, true)) {
            throw new \invalid_parameter_exception('Invalid managed scope');
        }
    }
}
