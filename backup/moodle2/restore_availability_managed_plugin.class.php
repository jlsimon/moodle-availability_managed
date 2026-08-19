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
 * Course restore definition.
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_availability_managed_plugin extends restore_plugin {
    /**
     * Define restore paths.
     */
    protected function define_course_plugin_structure(): array {
        return [
            new restore_path_element('availability_managed_state', $this->get_pathfor('/state')),
            new restore_path_element('availability_managed_rule', $this->get_pathfor('/rules/rule')),
        ];
    }

    /**
     * Restore course state.
     */
    public function process_availability_managed_state(array $data): void {
        global $DB;
        $record = (object) $data;
        $record->courseid = $this->task->get_courseid();
        unset($record->id);
        $DB->insert_record('availability_managed_course', $record);
    }

    /**
     * Restore a safely mappable rule.
     */
    public function process_availability_managed_rule(array $data): void {
        global $DB;
        $record = (object) $data;
        $record->courseid = $this->task->get_courseid();
        unset($record->id);
        if ($record->scope === 'user') {
            return;
        }
        if ($record->itemtype === 'cm') {
            $record->itemid = $this->get_mappingid('course_module', $record->itemid);
        } else {
            $record->itemid = $this->get_mappingid('course_section', $record->itemid);
        }
        if ($record->scope === 'course') {
            $record->scopeid = $record->courseid;
        } else if ($record->scope === 'group') {
            $record->scopeid = $this->get_mappingid('group', $record->scopeid);
        }
        if ($record->itemid && $record->scopeid) {
            $DB->insert_record('availability_managed_rule', $record);
        }
    }
}
