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
 * Read model and transactional writes for the teacher dashboard.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_service {
    /**
     * Get the rule selection for an item.
     *
     * @param int $courseid course id
     * @param string $itemtype item type
     * @param int $itemid item id
     * @return array rule selection
     */
    public function get_item_rules(int $courseid, string $itemtype, int $itemid): array {
        global $DB;
        availability_guard::require_operational($courseid);
        $this->require_item($courseid, $itemtype, $itemid);
        $rules = $DB->get_records('availability_managed_rule', [
            'courseid' => $courseid, 'itemtype' => $itemtype, 'itemid' => $itemid, 'enabled' => 1,
        ]);
        $result = ['everyone' => false, 'groupids' => [], 'userids' => []];
        foreach ($rules as $rule) {
            if ($rule->scope === 'course' && (int) $rule->scopeid === $courseid) {
                $result['everyone'] = true;
            } else if ($rule->scope === 'group') {
                $result['groupids'][] = (int) $rule->scopeid;
            } else if ($rule->scope === 'user') {
                $result['userids'][] = (int) $rule->scopeid;
            }
        }
        return $result;
    }

    /**
     * Replace the complete rule selection for an item.
     *
     * @param int $courseid course id
     * @param string $itemtype item type
     * @param int $itemid item id
     * @param bool $everyone whether everyone is allowed
     * @param array $groupids allowed group ids
     * @param array $userids allowed user ids
     * @param int $userid acting user id
     * @return array updated rule selection
     */
    public function set_item_rules(
        int $courseid,
        string $itemtype,
        int $itemid,
        bool $everyone,
        array $groupids,
        array $userids,
        int $userid
    ): array {
        global $DB;
        availability_guard::require_operational($courseid);
        $this->require_item($courseid, $itemtype, $itemid);
        $oldrules = $this->get_item_rules($courseid, $itemtype, $itemid);
        $groupids = array_values(array_unique(array_map('intval', $groupids)));
        $userids = array_values(array_unique(array_map('intval', $userids)));
        foreach ($groupids as $groupid) {
            if (!$DB->record_exists('groups', ['id' => $groupid, 'courseid' => $courseid])) {
                throw new \invalid_parameter_exception('Invalid course group');
            }
        }
        $context = \context_course::instance($courseid);
        foreach ($userids as $targetuserid) {
            if (!is_enrolled($context, $targetuserid, '', true)) {
                throw new \invalid_parameter_exception('Invalid enrolled user');
            }
        }
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('availability_managed_rule', compact('courseid', 'itemtype', 'itemid'));
        $repository = new rule_repository();
        if ($everyone) {
            $repository->upsert($courseid, $itemtype, $itemid, 'course', $courseid, $userid);
        } else {
            foreach ($groupids as $groupid) {
                $repository->upsert($courseid, $itemtype, $itemid, 'group', $groupid, $userid);
            }
            foreach ($userids as $targetuserid) {
                $repository->upsert($courseid, $itemtype, $itemid, 'user', $targetuserid, $userid);
            }
        }
        $transaction->allow_commit();
        $newrules = $this->get_item_rules($courseid, $itemtype, $itemid);
        (new audit_repository())->record(
            $courseid,
            'set_rules',
            $userid,
            $itemtype,
            $itemid,
            json_encode($oldrules),
            json_encode($newrules)
        );
        return $newrules;
    }

    /**
     * Copy a section rule selection to all of its course modules.
     *
     * @param int $courseid course id
     * @param int $sectionid section id
     * @param int $userid acting user id
     * @return int number of modules updated
     */
    public function copy_section_to_children(int $courseid, int $sectionid, int $userid): int {
        global $DB;

        availability_guard::require_operational($courseid);

        $this->require_item($courseid, 'section', $sectionid);
        $rules = $this->get_item_rules($courseid, 'section', $sectionid);
        $cmids = $DB->get_fieldset_select(
            'course_modules',
            'id',
            'course = :courseid AND section = :sectionid AND deletioninprogress = 0',
            ['courseid' => $courseid, 'sectionid' => $sectionid]
        );
        $transaction = $DB->start_delegated_transaction();
        foreach ($cmids as $cmid) {
            $this->set_item_rules(
                $courseid,
                'cm',
                (int) $cmid,
                $rules['everyone'],
                $rules['groupids'],
                $rules['userids'],
                $userid
            );
        }
        $transaction->allow_commit();
        (new audit_repository())->record($courseid, 'bulk_apply', $userid, 'section', $sectionid);
        return count($cmids);
    }

    /**
     * Return a localized compact summary.
     *
     * @param array $rules rule selection
     * @return string localized summary
     */
    public function summary(array $rules): string {
        if ($rules['everyone']) {
            return get_string('everyone', 'availability_managed');
        }
        $parts = [];
        if ($rules['groupids']) {
            $parts[] = get_string('groupscount', 'availability_managed', count($rules['groupids']));
        }
        if ($rules['userids']) {
            $parts[] = get_string('userscount', 'availability_managed', count($rules['userids']));
        }
        return $parts ? implode(', ', $parts) : get_string('closed', 'availability_managed');
    }

    /**
     * Validate that an item belongs to the course.
     *
     * @param int $courseid course id
     * @param string $itemtype item type
     * @param int $itemid item id
     */
    private function require_item(int $courseid, string $itemtype, int $itemid): void {
        global $DB;
        if (!in_array($itemtype, rule_repository::ITEM_TYPES, true)) {
            throw new \invalid_parameter_exception('Invalid item type');
        }
        $table = $itemtype === 'cm' ? 'course_modules' : 'course_sections';
        if (!$DB->record_exists($table, ['id' => $itemid, 'course' => $courseid])) {
            throw new \invalid_parameter_exception('Item does not belong to course');
        }
    }
}
