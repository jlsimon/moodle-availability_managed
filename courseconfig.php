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
 * Per-course activation settings.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('availability/managed:configure', $context);

$url = new moodle_url('/availability/condition/managed/courseconfig.php', ['courseid' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('courseconfiguration', 'availability_managed'));
$PAGE->set_heading(format_string($course->fullname));
$manager = new \availability_managed\local\course_manager();

if (!\availability_managed\local\global_manager::is_enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('globalsuspended_config', 'availability_managed'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

if ($action === 'enable' && data_submitted()) {
    require_sesskey();
    $defaultstate = required_param('defaultstate', PARAM_ALPHA);
    $manager->enable($courseid, $defaultstate, (int) $USER->id);
    redirect(
        $url,
        get_string('courseenabled', 'availability_managed'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action === 'disable' && data_submitted()) {
    require_sesskey();
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('disableconfirm', 'availability_managed'),
        new moodle_url($url, ['action' => 'confirmdisable', 'sesskey' => sesskey()]),
        $url
    );
    echo $OUTPUT->footer();
    exit;
}

if ($action === 'confirmdisable') {
    require_sesskey();
    $manager->disable($courseid, (int) $USER->id);
    redirect(
        $url,
        get_string('coursedisabled', 'availability_managed'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$enabled = $manager->is_enabled($courseid);
$state = $DB->get_record('availability_managed_course', ['courseid' => $courseid]);
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('availability_managed/courseconfig', [
    'courseid' => $courseid,
    'enabled' => $enabled,
    'disabled' => !$enabled,
    'isopen' => $state && $state->defaultstate === 'open',
    'isclosed' => $state && $state->defaultstate === 'closed',
    'sesskey' => sesskey(),
    'dashboardurl' => (new moodle_url(
        '/availability/condition/managed/index.php',
        ['courseid' => $courseid]
    ))->out(false),
]);
echo $OUTPUT->footer();
