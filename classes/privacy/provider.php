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

namespace availability_managed\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy API provider.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe stored personal data.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'availability_managed_rule',
            ['scopeid' => 'privacy:metadata:availability_managed_rule:scopeid'],
            'privacy:metadata:availability_managed_rule'
        );
        $collection->add_database_table(
            'availability_managed_audit',
            ['userid' => 'privacy:metadata:availability_managed_audit:userid'],
            'privacy:metadata:availability_managed_audit'
        );
        return $collection;
    }

    /**
     * Find course contexts containing data for a user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $sql = "SELECT ctx.id FROM {context} ctx JOIN {availability_managed_rule} r ON r.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :level AND r.scope = 'user' AND r.scopeid = :userid
                 UNION SELECT ctx.id FROM {context} ctx JOIN {availability_managed_audit} a ON a.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :level2 AND a.userid = :userid2";
        $list->add_from_sql($sql, ['level' => CONTEXT_COURSE, 'userid' => $userid,
            'level2' => CONTEXT_COURSE, 'userid2' => $userid]);
        return $list;
    }

    /**
     * Export approved user data.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $data = [
                'rules' => array_values($DB->get_records('availability_managed_rule', [
                    'courseid' => $context->instanceid, 'scope' => 'user', 'scopeid' => $userid,
                ])),
                'audit' => array_values($DB->get_records('availability_managed_audit', [
                    'courseid' => $context->instanceid, 'userid' => $userid,
                ])),
            ];
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'availability_managed')],
                (object) $data
            );
        }
    }

    /**
     * Delete personal data in one context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel === CONTEXT_COURSE) {
            $DB->delete_records('availability_managed_rule', ['courseid' => $context->instanceid, 'scope' => 'user']);
            $DB->delete_records('availability_managed_audit', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete data for one approved user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $DB->delete_records('availability_managed_rule', [
                'courseid' => $context->instanceid, 'scope' => 'user', 'scopeid' => $userid,
            ]);
            $DB->delete_records('availability_managed_audit', ['courseid' => $context->instanceid, 'userid' => $userid]);
        }
    }
}
