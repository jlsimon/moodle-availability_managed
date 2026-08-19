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

namespace availability_managed\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use availability_managed\local\dashboard_service;

/**
 * AJAX rule reader.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_item_rules extends external_api {
    /**
     * Return parameter definition.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT),
            'itemtype' => new external_value(PARAM_ALPHA),
            'itemid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Execute the rule read.
     */
    public static function execute(int $courseid, string $itemtype, int $itemid): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'itemtype', 'itemid'));
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('availability/managed:manage', $context);
        return (new dashboard_service())->get_item_rules($params['courseid'], $params['itemtype'], $params['itemid']);
    }

    /**
     * Return result definition.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'everyone' => new external_value(PARAM_BOOL),
            'groupids' => new external_multiple_structure(new external_value(PARAM_INT)),
            'userids' => new external_multiple_structure(new external_value(PARAM_INT)),
        ]);
    }
}
