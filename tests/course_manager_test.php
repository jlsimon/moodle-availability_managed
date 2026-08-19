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

use availability_managed\local\availability_tree_manager;
use availability_managed\local\course_manager;
use availability_managed\local\reconciliation_service;

/**
 * Tests course activation and lifecycle behaviour.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(course_manager::class)]
final class course_manager_test extends \advanced_testcase {
    /** @var \stdClass course */
    private \stdClass $course;

    /** @var \stdClass module */
    private \stdClass $cm;

    /** @var \stdClass actor */
    private \stdClass $actor;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course(['numsections' => 2]);
        $this->cm = $generator->create_module('page', ['course' => $this->course->id, 'section' => 1]);
        $this->actor = $generator->create_user();
    }

    public function test_enable_open_attaches_markers_and_opens_existing_items(): void {
        global $DB;

        $result = (new course_manager())->enable($this->course->id, 'open', $this->actor->id);
        $sectioncount = $DB->count_records('course_sections', ['course' => $this->course->id]);
        $cmcount = $DB->count_records('course_modules', ['course' => $this->course->id]);

        $this->assertSame($sectioncount + $cmcount, $result->added);
        $this->assertSame($sectioncount + $cmcount, $result->itemschecked);
        $this->assertSame($sectioncount + $cmcount, $DB->count_records('availability_managed_rule', [
            'courseid' => $this->course->id,
            'scope' => 'course',
        ]));
        $this->assert_every_item_has_one_marker();
    }

    public function test_enable_closed_attaches_markers_without_rules(): void {
        global $DB;

        (new course_manager())->enable($this->course->id, 'closed', $this->actor->id);

        $this->assertFalse($DB->record_exists('availability_managed_rule', ['courseid' => $this->course->id]));
        $this->assert_every_item_has_one_marker();
    }

    public function test_new_module_is_automatically_managed(): void {
        global $DB;

        (new course_manager())->enable($this->course->id, 'closed', $this->actor->id);
        $newcm = $this->getDataGenerator()->create_module('page', ['course' => $this->course->id]);
        $json = $DB->get_field('course_modules', 'availability', ['id' => $newcm->cmid]);

        $this->assertSame(1, availability_tree_manager::count_in_json($json));
    }

    public function test_new_module_uses_open_course_default(): void {
        global $DB;

        (new course_manager())->enable($this->course->id, 'open', $this->actor->id);
        $newcm = $this->getDataGenerator()->create_module('page', ['course' => $this->course->id]);

        $this->assertTrue($DB->record_exists('availability_managed_rule', [
            'courseid' => $this->course->id,
            'itemtype' => 'cm',
            'itemid' => $newcm->cmid,
            'scope' => 'course',
            'scopeid' => $this->course->id,
        ]));
    }

    public function test_disable_removes_only_managed_conditions_and_rules(): void {
        global $DB;

        $date = (object) ['type' => 'date', 'd' => '>=', 't' => 123];
        $DB->set_field('course_modules', 'availability', json_encode((object) [
            'op' => '&',
            'c' => [$date],
            'showc' => [true],
        ]), ['id' => $this->cm->cmid]);
        $manager = new course_manager();
        $manager->enable($this->course->id, 'open', $this->actor->id);
        $manager->disable($this->course->id, $this->actor->id);
        $json = $DB->get_field('course_modules', 'availability', ['id' => $this->cm->cmid]);
        $tree = json_decode($json);

        $this->assertSame('date', $tree->c[0]->type);
        $this->assertFalse(availability_tree_manager::has_in_json($json));
        $this->assertFalse($DB->record_exists('availability_managed_rule', ['courseid' => $this->course->id]));
        $this->assertFalse($manager->is_enabled($this->course->id));
    }

    public function test_reconcile_repairs_duplicates_and_removes_orphans(): void {
        global $DB;

        (new course_manager())->enable($this->course->id, 'closed', $this->actor->id);
        $duplicate = '{"op":"&","c":[{"type":"managed"},{"type":"managed"}],"showc":[false,false]}';
        $DB->set_field('course_modules', 'availability', $duplicate, ['id' => $this->cm->cmid]);
        $now = time();
        $DB->insert_record('availability_managed_rule', (object) [
            'courseid' => $this->course->id,
            'itemtype' => 'cm',
            'itemid' => PHP_INT_MAX,
            'scope' => 'course',
            'scopeid' => $this->course->id,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $this->actor->id,
        ]);

        $result = (new reconciliation_service())->reconcile_course($this->course->id);

        $this->assertSame(1, $result->duplicatesremoved);
        $this->assertSame(1, $result->orphanrulesremoved);
        $json = $DB->get_field('course_modules', 'availability', ['id' => $this->cm->cmid]);
        $this->assertSame(1, availability_tree_manager::count_in_json($json));
    }

    /**
     * Assert that every fixture item contains exactly one marker.
     */
    private function assert_every_item_has_one_marker(): void {
        global $DB;

        foreach ($DB->get_records('course_sections', ['course' => $this->course->id]) as $section) {
            $this->assertSame(1, availability_tree_manager::count_in_json($section->availability));
        }
        foreach ($DB->get_records('course_modules', ['course' => $this->course->id]) as $cm) {
            $this->assertSame(1, availability_tree_manager::count_in_json($cm->availability));
        }
    }
}
