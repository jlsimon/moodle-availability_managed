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
 * AJAX service definitions.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'availability_managed_get_item_rules' => [
        'classname' => 'availability_managed\\external\\get_item_rules',
        'description' => 'Get managed rules for a course item',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'availability/managed:manage',
    ],
    'availability_managed_set_item_rules' => [
        'classname' => 'availability_managed\\external\\set_item_rules',
        'description' => 'Replace managed rules for a course item',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'availability/managed:manage',
    ],
    'availability_managed_bulk_apply' => [
        'classname' => 'availability_managed\\external\\bulk_apply',
        'description' => 'Copy a section rule to its activities',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'availability/managed:manage',
    ],
    'availability_managed_get_target_view' => [
        'classname' => 'availability_managed\\external\\get_target_view',
        'description' => 'Get ordered section availability for a group or user',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'availability/managed:manage',
    ],
    'availability_managed_open_next' => [
        'classname' => 'availability_managed\\external\\open_next',
        'description' => 'Open the next section for a group or user',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'availability/managed:manage',
    ],
];
