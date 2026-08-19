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
use availability_managed\local\progression_service;

/**
 * AJAX target view reader.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_target_view extends external_api {
    /**
     * Define parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT),
            'scope' => new external_value(PARAM_ALPHA),
            'scopeid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Execute read.
     *
     * @param int $courseid course id
     * @param string $scope target scope
     * @param int $scopeid target id
     * @return array target view
     */
    public static function execute(int $courseid, string $scope, int $scopeid): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'scope', 'scopeid'));
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('availability/managed:manage', $context);
        return ['sections' => (new progression_service())->get_view(
            $params['courseid'],
            $params['scope'],
            $params['scopeid']
        )];
    }

    /**
     * Define result.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sections' => new external_multiple_structure(new external_single_structure([
                'sectionid' => new external_value(PARAM_INT),
                'name' => new external_value(PARAM_TEXT),
                'open' => new external_value(PARAM_BOOL),
                'targetopen' => new external_value(PARAM_BOOL),
            ])),
        ]);
    }
}
