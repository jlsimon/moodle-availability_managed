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
 * Teacher dashboard page.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('availability/managed:manage', $context);

$PAGE->set_url('/availability/condition/managed/index.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('pluginname', 'availability_managed'));
$PAGE->set_heading(format_string($course->fullname));

if (!\availability_managed\local\global_manager::is_enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('globalsuspended_notice', 'availability_managed'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

if (!\availability_managed\local\availability_guard::is_course_enabled($courseid)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('coursedisabled_notice', 'availability_managed'), 'info');
    if (has_capability('availability/managed:configure', $context)) {
        echo html_writer::link(
            new moodle_url('/availability/condition/managed/courseconfig.php', ['courseid' => $courseid]),
            get_string('enablecourse', 'availability_managed'),
            ['class' => 'btn btn-primary']
        );
    }
    echo $OUTPUT->footer();
    exit;
}

$service = new \availability_managed\local\dashboard_service();
$modinfo = get_fast_modinfo($course);
$items = [];
foreach ($modinfo->get_section_info_all() as $section) {
    $sectionrules = $service->get_item_rules($courseid, 'section', (int) $section->id);
    $sectiondata = [
        'id' => (int) $section->id,
        'name' => get_section_name($course, $section),
        'summary' => $service->summary($sectionrules),
        'everyone' => $sectionrules['everyone'],
        'closed' => !$sectionrules['everyone'] && !$sectionrules['groupids'] && !$sectionrules['userids'],
        'modules' => [],
    ];
    foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
        $cm = $modinfo->get_cm($cmid);
        if ($cm->deletioninprogress) {
            continue;
        }
        $rules = $service->get_item_rules($courseid, 'cm', (int) $cm->id);
        $sectiondata['modules'][] = [
            'id' => (int) $cm->id,
            'name' => format_string($cm->name, true, ['context' => $cm->context]),
            'modname' => get_string('modulename', $cm->modname),
            'summary' => $service->summary($rules),
            'everyone' => $rules['everyone'],
            'closed' => !$rules['everyone'] && !$rules['groupids'] && !$rules['userids'],
        ];
    }
    $sectiondata['hasmodules'] = !empty($sectiondata['modules']);
    $items[] = $sectiondata;
}

$groups = [];
foreach (groups_get_all_groups($courseid, 0, 0, 'g.id,g.name') as $group) {
    $groups[] = ['id' => (int) $group->id, 'name' => format_string($group->name, true, ['context' => $context])];
}
$users = [];
$userfields = 'u.id,' . implode(',', \core_user\fields::get_name_fields(true, 'u'));
foreach (get_enrolled_users($context, '', 0, $userfields, 'u.lastname,u.firstname') as $user) {
    $users[] = ['id' => (int) $user->id, 'name' => fullname($user)];
}

$PAGE->requires->js_call_amd('availability_managed/dashboard', 'init', [[
    'courseid' => $courseid,
    'groups' => $groups,
    'users' => $users,
    'quicklabels' => [
        'open' => get_string('openeveryone', 'availability_managed'),
        'close' => get_string('close', 'availability_managed'),
        'openaction' => get_string('openaction', 'availability_managed'),
        'closeeveryone' => get_string('closeeveryone', 'availability_managed'),
        'alreadyopen' => get_string('alreadyopen', 'availability_managed'),
        'alreadyclosed' => get_string('alreadyclosed', 'availability_managed'),
        'alreadyopenhelp' => get_string('alreadyopen_help', 'availability_managed'),
        'alreadyclosedhelp' => get_string('alreadyclosed_help', 'availability_managed'),
    ],
]]);

echo $OUTPUT->header();
echo html_writer::div(
    (has_capability('availability/managed:configure', $context) ? html_writer::link(
        new moodle_url('/availability/condition/managed/courseconfig.php', ['courseid' => $courseid]),
        get_string('courseconfiguration', 'availability_managed'),
        ['class' => 'btn btn-outline-primary me-2']
    ) : '') . html_writer::link(
        new moodle_url('/availability/condition/managed/help.php', ['courseid' => $courseid]),
        get_string('openhelp', 'availability_managed'),
        ['class' => 'btn btn-outline-secondary mb-3']
    ),
    'text-end'
);
echo $OUTPUT->render_from_template('availability_managed/dashboard', [
    'courseid' => $courseid,
    'helpurl' => (new moodle_url('/availability/condition/managed/help.php', ['courseid' => $courseid]))->out(false),
    'sections' => $items,
]);
echo $OUTPUT->footer();
