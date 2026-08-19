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

use availability_managed\local\global_manager;

/**
 * Global switch with automatic repair on reactivation.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_global_enabled extends \admin_setting_configcheckbox {
    /**
     * Save the switch and reconcile when changing from off to on.
     */
    public function write_setting($data): string {
        $wasenabled = global_manager::is_enabled();
        $result = parent::write_setting($data);
        if ($result === '' && !$wasenabled && global_manager::is_enabled()) {
            global_manager::reconcile_enabled_courses();
        }
        return $result;
    }
}
