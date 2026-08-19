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

namespace availability_managed;

/**
 * Managed availability condition.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /**
     * Constructor.
     *
     * The marker intentionally contains no rule data.
     *
     * @param \stdClass $structure decoded JSON condition
     */
    public function __construct($structure) {
        // There is no condition-specific configuration in the JSON marker.
    }

    /**
     * Save the minimal marker.
     *
     * @return \stdClass
     */
    public function save() {
        return (object) ['type' => 'managed'];
    }

    /**
     * Evaluate the condition.
     *
     * @param bool $not whether Moodle is negating the condition
     * @param \core_availability\info $info availability information
     * @param bool $grabthelot bulk-loading hint
     * @param int $userid user being checked
     * @return bool
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        try {
            if (
                class_exists(\availability_managed\local\global_manager::class)
                    && !\availability_managed\local\global_manager::is_enabled()
            ) {
                return !$not;
            }
            if (!class_exists(\availability_managed\local\access_manager::class)) {
                return false;
            }
            $courseid = (int) $info->get_course()->id;
            if ($info instanceof \core_availability\info_module) {
                $itemtype = 'cm';
                $itemid = (int) $info->get_course_module()->id;
            } else if ($info instanceof \core_availability\info_section) {
                $itemtype = 'section';
                $itemid = (int) $info->get_section()->id;
            } else {
                return false;
            }
            $manager = new \availability_managed\local\access_manager();
            $allowed = $manager->is_allowed($courseid, $itemtype, $itemid, (int) $userid);
            return $not ? !$allowed : $allowed;
        } catch (\Throwable $exception) {
            debugging('Managed availability could not resolve access: ' . $exception->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Describe the condition.
     *
     * @param bool $full full description requested
     * @param bool $not whether Moodle is negating the condition
     * @param \core_availability\info $info availability information
     * @return string
     */
    public function get_description($full, $not, \core_availability\info $info) {
        return get_string('requires_managed', 'availability_managed');
    }

    /**
     * Debug representation.
     *
     * @return string
     */
    protected function get_debug_string() {
        return 'managed';
    }
}
