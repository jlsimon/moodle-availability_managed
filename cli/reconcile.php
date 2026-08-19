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
 * Reconcile managed availability markers and rules.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'courseid' => null,
    'all' => false,
    'help' => false,
], [
    'c' => 'courseid',
    'a' => 'all',
    'h' => 'help',
]);

if ($unrecognised || $options['help'] || (!$options['courseid'] && !$options['all'])) {
    echo "Reconcile Managed availability\n\n";
    echo "--courseid=ID  Reconcile one enabled course\n";
    echo "--all          Reconcile all enabled courses\n";
    echo "-h, --help     Show this help\n";
    exit($unrecognised ? 1 : 0);
}

$courseids = [];
if ($options['all']) {
    $courseids = $DB->get_fieldset_select('availability_managed_course', 'courseid', 'enabled = ?', [1]);
} else {
    $courseids[] = clean_param($options['courseid'], PARAM_INT);
}

$service = new \availability_managed\local\reconciliation_service();
foreach ($courseids as $courseid) {
    $result = $service->reconcile_course((int) $courseid);
    cli_writeln("Course {$courseid}: checked {$result->itemschecked}, added {$result->added}, " .
        "duplicates removed {$result->duplicatesremoved}, orphan rules removed {$result->orphanrulesremoved}");
}
