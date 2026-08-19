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

/**
 * Upgrade steps for availability_managed.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Ensure the consolidated plugin tables exist.
 */
function availability_managed_ensure_tables(): void {
    global $DB;
    $dbman = $DB->get_manager();

    $course = new xmldb_table('availability_managed_course');
    $course->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $course->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $course->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
    $course->add_field('defaultstate', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'open');
    $course->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $course->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $course->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $course->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $course->add_key('courseid', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'course', ['id']);
    $course->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
    if (!$dbman->table_exists($course)) {
        $dbman->create_table($course);
    }

    $rule = new xmldb_table('availability_managed_rule');
    $rule->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $rule->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $rule->add_field('itemtype', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL);
    $rule->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $rule->add_field('scope', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL);
    $rule->add_field('scopeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $rule->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
    $rule->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $rule->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $rule->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $rule->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $rule->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    $rule->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
    $rule->add_index('rule', XMLDB_INDEX_UNIQUE, ['courseid', 'itemtype', 'itemid', 'scope', 'scopeid']);
    $rule->add_index('item', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'itemtype', 'itemid']);
    $rule->add_index('scope', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'scope', 'scopeid']);
    if (!$dbman->table_exists($rule)) {
        $dbman->create_table($rule);
    }

    $audit = new xmldb_table('availability_managed_audit');
    $audit->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $audit->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $audit->add_field('itemtype', XMLDB_TYPE_CHAR, '16');
    $audit->add_field('itemid', XMLDB_TYPE_INTEGER, '10');
    $audit->add_field('action', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
    $audit->add_field('scope', XMLDB_TYPE_CHAR, '16');
    $audit->add_field('scopeid', XMLDB_TYPE_INTEGER, '10');
    $audit->add_field('oldvalue', XMLDB_TYPE_TEXT);
    $audit->add_field('newvalue', XMLDB_TYPE_TEXT);
    $audit->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $audit->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $audit->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $audit->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    $audit->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
    $audit->add_index('course-time', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'timecreated']);
    if (!$dbman->table_exists($audit)) {
        $dbman->create_table($audit);
    }
}

/**
 * Copy legacy records without overwriting consolidated records.
 *
 * @param string $legacy Legacy table name
 * @param string $target Consolidated table name
 * @param array $uniquekeys Fields identifying an existing record
 */
function availability_managed_migrate_table(string $legacy, string $target, array $uniquekeys): void {
    global $DB;
    if (!$DB->get_manager()->table_exists(new xmldb_table($legacy))) {
        return;
    }
    $recordset = $DB->get_recordset($legacy);
    foreach ($recordset as $record) {
        unset($record->id);
        $conditions = [];
        foreach ($uniquekeys as $key) {
            $conditions[$key] = $record->{$key};
        }
        if (!$DB->record_exists($target, $conditions)) {
            $DB->insert_record($target, $record);
        }
    }
    $recordset->close();
}

/**
 * Upgrade availability_managed.
 *
 * @param int $oldversion Installed plugin version
 * @return bool
 */
function xmldb_availability_managed_upgrade(int $oldversion): bool {
    global $DB;
    if ($oldversion < 2026082000) {
        availability_managed_ensure_tables();
        availability_managed_migrate_table('local_mavail_course', 'availability_managed_course', ['courseid']);
        availability_managed_migrate_table(
            'local_mavail_rule',
            'availability_managed_rule',
            ['courseid', 'itemtype', 'itemid', 'scope', 'scopeid']
        );
        if ($DB->count_records('availability_managed_audit') === 0) {
            $legacytable = new xmldb_table('local_mavail_audit');
            if ($DB->get_manager()->table_exists($legacytable)) {
                $recordset = $DB->get_recordset('local_mavail_audit');
                foreach ($recordset as $record) {
                    unset($record->id);
                    $DB->insert_record('availability_managed_audit', $record);
                }
                $recordset->close();
            }
        }

        $legacyenabled = get_config('local_managedavailability', 'globallyenabled');
        if ($legacyenabled !== false && get_config('availability_managed', 'globallyenabled') === false) {
            set_config('globallyenabled', $legacyenabled, 'availability_managed');
        }

        $capabilitymap = [
            'local/managedavailability:manage' => 'availability/managed:manage',
            'local/managedavailability:viewaudit' => 'availability/managed:viewaudit',
            'local/managedavailability:configure' => 'availability/managed:configure',
        ];
        // Plugin capabilities are normally refreshed after the XMLDB step.
        // Register them now because the legacy assignments must be copied
        // during this same atomic migration.
        update_capabilities('availability_managed');
        foreach ($capabilitymap as $legacycapability => $capability) {
            $assignments = $DB->get_records('role_capabilities', ['capability' => $legacycapability]);
            foreach ($assignments as $assignment) {
                if (
                    !$DB->record_exists('role_capabilities', [
                        'roleid' => $assignment->roleid,
                        'contextid' => $assignment->contextid,
                        'capability' => $capability,
                    ])
                ) {
                    assign_capability(
                        $capability,
                        $assignment->permission,
                        $assignment->roleid,
                        $assignment->contextid,
                        true
                    );
                }
            }
        }
        upgrade_plugin_savepoint(true, 2026082000, 'availability', 'managed');
    }
    return true;
}
