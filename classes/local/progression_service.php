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
 * Builds target-centric section views and performs manual progression.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progression_service {
    /**
     * Return ordered section states for a group or user.
     *
     * @param int $courseid course id
     * @param string $scope group or user
     * @param int $scopeid target id
     * @return array
     */
    public function get_view(int $courseid, string $scope, int $scopeid): array {
        global $DB;

        availability_guard::require_operational($courseid);
        $this->require_target($courseid, $scope, $scopeid);
        $course = get_course($courseid);
        $rules = $DB->get_records('availability_managed_rule', [
            'courseid' => $courseid,
            'itemtype' => 'section',
            'enabled' => 1,
        ]);
        $courseopen = [];
        $targetopen = [];
        foreach ($rules as $rule) {
            if ($rule->scope === 'course' && (int) $rule->scopeid === $courseid) {
                $courseopen[(int) $rule->itemid] = true;
            } else if ($rule->scope === $scope && (int) $rule->scopeid === $scopeid) {
                $targetopen[(int) $rule->itemid] = true;
            }
        }
        $result = [];
        foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
            if ((int) $section->section === 0) {
                continue;
            }
            $id = (int) $section->id;
            $result[] = [
                'sectionid' => $id,
                'name' => get_section_name($course, $section),
                'open' => isset($courseopen[$id]) || isset($targetopen[$id]),
                'targetopen' => isset($targetopen[$id]),
            ];
        }
        return $result;
    }

    /**
     * Open the first section not already open for the target.
     *
     * @param int $courseid course id
     * @param string $scope group or user
     * @param int $scopeid target id
     * @param int $userid acting user id
     * @return array|null opened section, or null if none remains
     */
    public function open_next(int $courseid, string $scope, int $scopeid, int $userid): ?array {
        $view = $this->get_view($courseid, $scope, $scopeid);
        foreach ($view as $section) {
            if (!$section['open']) {
                (new rule_repository())->upsert(
                    $courseid,
                    'section',
                    $section['sectionid'],
                    $scope,
                    $scopeid,
                    $userid
                );
                (new audit_repository())->record(
                    $courseid,
                    'open_next',
                    $userid,
                    'section',
                    $section['sectionid'],
                    null,
                    json_encode(['scope' => $scope, 'scopeid' => $scopeid])
                );
                $section['open'] = true;
                $section['targetopen'] = true;
                return $section;
            }
        }
        return null;
    }

    /**
     * Validate target course ownership or enrolment.
     *
     * @param int $courseid course id
     * @param string $scope group or user
     * @param int $scopeid target id
     */
    private function require_target(int $courseid, string $scope, int $scopeid): void {
        global $DB;

        if ($scope === 'group') {
            if (!$DB->record_exists('groups', ['id' => $scopeid, 'courseid' => $courseid])) {
                throw new \invalid_parameter_exception('Invalid course group');
            }
        } else if ($scope === 'user') {
            if (!is_enrolled(\context_course::instance($courseid), $scopeid, '', true)) {
                throw new \invalid_parameter_exception('Invalid enrolled user');
            }
        } else {
            throw new \invalid_parameter_exception('Invalid progression scope');
        }
    }
}
