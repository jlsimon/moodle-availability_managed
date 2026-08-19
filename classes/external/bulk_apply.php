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
use core_external\external_single_structure;
use core_external\external_value;
use availability_managed\local\dashboard_service;

/**
 * AJAX section-to-children bulk operation.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_apply extends external_api {
    /**
     * Define parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT),
            'sectionid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Execute the bulk copy.
     */
    public static function execute(int $courseid, int $sectionid): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'sectionid'));
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('availability/managed:manage', $context);
        $count = (new dashboard_service())->copy_section_to_children(
            $params['courseid'],
            $params['sectionid'],
            (int) $USER->id
        );
        return ['updatedcount' => $count];
    }

    /**
     * Define result.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(['updatedcount' => new external_value(PARAM_INT)]);
    }
}
