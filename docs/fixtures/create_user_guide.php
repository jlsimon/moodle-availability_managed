<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Rebuild the local user-guide demonstration course.
 *
 * Run from the Moodle root:
 * php /path/to/create_user_guide.php
 *
 * @package availability_managed
 */

define('CLI_SCRIPT', true);

$moodleroot = getcwd();
if (!is_file($moodleroot . '/config.php')) {
    fwrite(STDERR, "Run this script from the Moodle root.\n");
    exit(1);
}

require($moodleroot . '/config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/grouplib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/resourcelib.php');

$admin = get_admin();
\core\session\manager::set_user($admin);

/** Create or update a dedicated fictional guide user. */
function guide_user(string $username, string $firstname, string $lastname, string $lang): stdClass {
    global $CFG, $DB;
    $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
    $record = (object) [
        'username' => $username,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => $username . '@example.invalid',
        'auth' => 'manual',
        'confirmed' => 1,
        'mnethostid' => $CFG->mnet_localhost_id,
        'lang' => $lang,
        'password' => 'Guide2026!',
    ];
    if ($user) {
        $record->id = $user->id;
        user_update_user($record, true, false);
        return $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
    }
    $record->id = user_create_user($record, true, false);
    return $DB->get_record('user', ['id' => $record->id], '*', MUST_EXIST);
}

/** Add a Page resource with stable guide content. */
function guide_page(stdClass $course, int $section, string $name, string $body): stdClass {
    global $DB;
    $data = (object) [
        'course' => $course->id,
        'section' => $section,
        'modulename' => 'page',
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
        'name' => $name,
        'intro' => '',
        'introformat' => FORMAT_HTML,
        'content' => '<div class="guide-content"><p>' . $body . '</p></div>',
        'contentformat' => FORMAT_HTML,
        'display' => RESOURCELIB_DISPLAY_OPEN,
        'printintro' => 0,
        'printlastmodified' => 0,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'cmidnumber' => '',
        'groupmode' => NOGROUPS,
        'groupingid' => 0,
        'completion' => COMPLETION_TRACKING_NONE,
        'availability' => null,
    ];
    return add_moduleinfo($data, $course);
}

$users = [
    'teacher' => guide_user('mavail_teacher', 'Elena', 'Martín', 'es'),
    'ana' => guide_user('mavail_ana', 'Ana', 'López', 'es'),
    'bruno' => guide_user('mavail_bruno', 'Bruno', 'Silva', 'es'),
    'carla' => guide_user('mavail_carla', 'Carla', 'Reed', 'en'),
];

$shortname = 'MAVAIL-GUIDE';
if ($oldcourse = $DB->get_record('course', ['shortname' => $shortname])) {
    delete_course($oldcourse, false);
}

$course = create_course((object) [
    'fullname' => 'Learning Pathways — Managed Availability Demo',
    'shortname' => $shortname,
    'category' => 1,
    'format' => 'topics',
    'numsections' => 4,
    'visible' => 1,
    'enablecompletion' => 1,
    'summary' => 'A fictional course built exclusively for the illustrated Managed Availability user guide.',
    'summaryformat' => FORMAT_HTML,
]);

$sectionnames = [
    0 => 'Course information',
    1 => '1. Foundations',
    2 => '2. Team workshop',
    3 => '3. Individual practice',
    4 => '4. Final challenge',
];
foreach ($sectionnames as $number => $name) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $number], '*', MUST_EXIST);
    $section->name = $name;
    $section->summary = '<p>Demonstration content for <strong>' . s($name) . '</strong>.</p>';
    $section->summaryformat = FORMAT_HTML;
    $DB->update_record('course_sections', $section);
}

$pages = [
    [0, 'Welcome and course map', 'Use this page to understand the learning path and the access rules used in this demonstration course.'],
    [1, 'Core concepts', 'Review the shared concepts before moving on to collaborative work.'],
    [1, 'Foundation checklist', 'Check that you can explain the three core ideas in your own words.'],
    [2, 'Blue team brief', 'Instructions prepared for the Blue team workshop.'],
    [2, 'Red team brief', 'Instructions prepared for the Red team workshop.'],
    [2, 'Shared workshop board', 'A shared resource available to participants admitted to this section.'],
    [3, 'Ana’s extension activity', 'An individually released extension activity.'],
    [3, 'Independent practice', 'Practice material that can be released to selected learners.'],
    [4, 'Final challenge instructions', 'The final challenge remains closed until the teacher opens the section.'],
    [4, 'Submission checklist', 'A final checklist retained behind the closed section.'],
];
$createdmodules = [];
foreach ($pages as [$section, $name, $body]) {
    $createdmodules[$name] = guide_page($course, $section, $name, $body);
}

$manual = enrol_get_plugin('manual');
$instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
$roles = $DB->get_records_list('role', 'shortname', ['editingteacher', 'student'], '', 'id,shortname');
$rolebyname = [];
foreach ($roles as $role) {
    $rolebyname[$role->shortname] = $role;
}
$manual->enrol_user($instance, $users['teacher']->id, $rolebyname['editingteacher']->id);
foreach (['ana', 'bruno', 'carla'] as $key) {
    $manual->enrol_user($instance, $users[$key]->id, $rolebyname['student']->id);
}

$groups = [];
foreach (['Blue team' => ['ana', 'carla'], 'Red team' => ['bruno']] as $name => $members) {
    $groupid = groups_create_group((object) ['courseid' => $course->id, 'name' => $name]);
    $groups[$name] = $groupid;
    foreach ($members as $member) {
        groups_add_member($groupid, $users[$member]->id);
    }
}

rebuild_course_cache($course->id, true);
(new availability_managed\local\course_manager())->enable($course->id, 'closed', $admin->id);
$service = new availability_managed\local\dashboard_service();
$modinfo = get_fast_modinfo($course->id);
$sections = [];
foreach ($modinfo->get_section_info_all() as $section) {
    $sections[(int) $section->section] = (int) $section->id;
}

// Information and foundations: open to everyone.
$service->set_item_rules($course->id, 'section', $sections[0], true, [], [], $admin->id);
$service->copy_section_to_children($course->id, $sections[0], $admin->id);
$service->set_item_rules($course->id, 'section', $sections[1], true, [], [], $admin->id);
$service->copy_section_to_children($course->id, $sections[1], $admin->id);

// Workshop: Blue and Red teams can enter, but their briefs remain specific.
$service->set_item_rules($course->id, 'section', $sections[2], false, array_values($groups), [], $admin->id);
$service->set_item_rules($course->id, 'cm', $createdmodules['Blue team brief']->coursemodule, false, [$groups['Blue team']], [], $admin->id);
$service->set_item_rules($course->id, 'cm', $createdmodules['Red team brief']->coursemodule, false, [$groups['Red team']], [], $admin->id);
$service->set_item_rules($course->id, 'cm', $createdmodules['Shared workshop board']->coursemodule, true, [], [], $admin->id);

// Individual practice: Ana can enter; one activity is specifically assigned to her.
$service->set_item_rules($course->id, 'section', $sections[3], false, [], [$users['ana']->id], $admin->id);
$service->set_item_rules($course->id, 'cm', $createdmodules['Ana’s extension activity']->coursemodule, false, [], [$users['ana']->id], $admin->id);
$service->set_item_rules($course->id, 'cm', $createdmodules['Independent practice']->coursemodule, false, [$groups['Blue team']], [], $admin->id);

// Final challenge: section and activities remain closed, illustrating hidden controls.

rebuild_course_cache($course->id, true);
file_put_contents(__DIR__ . '/.courseid', (string) $course->id);
echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $shortname,
    'users' => array_map(static fn($user) => ['id' => (int) $user->id, 'username' => $user->username], $users),
    'groups' => $groups,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
