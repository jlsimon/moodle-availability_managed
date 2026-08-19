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
 * Course backup definition.
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_availability_managed_plugin extends backup_plugin {
    /**
     * Define backup tree and sources.
     */
    protected function define_course_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);
        $state = new backup_nested_element(
            'state',
            ['id'],
            ['enabled', 'defaultstate', 'timecreated', 'timemodified', 'usermodified']
        );
        $rules = new backup_nested_element('rules');
        $rule = new backup_nested_element('rule', ['id'], ['itemtype', 'itemid', 'scope', 'scopeid', 'enabled',
            'timecreated', 'timemodified', 'usermodified']);
        $wrapper->add_child($state);
        $wrapper->add_child($rules);
        $rules->add_child($rule);
        $state->set_source_table('availability_managed_course', ['courseid' => backup::VAR_COURSEID]);
        $rule->set_source_table('availability_managed_rule', ['courseid' => backup::VAR_COURSEID]);
        return $plugin;
    }
}
