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

use availability_managed\local\access_manager;
use availability_managed\local\rule_repository;

/**
 * Tests managed rule resolution.
 *
 * @covers \availability_managed\local\access_manager
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class access_manager_test extends \advanced_testcase {
    /** @var \stdClass course */
    private \stdClass $course;

    /** @var \stdClass course module */
    private \stdClass $cm;

    /** @var \stdClass first student */
    private \stdClass $studentone;

    /** @var \stdClass second student */
    private \stdClass $studenttwo;

    /** @var \stdClass acting user */
    private \stdClass $actor;

    /** @var rule_repository repository */
    private rule_repository $repository;

    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->cm = $generator->create_module('page', ['course' => $this->course->id]);
        $this->studentone = $generator->create_and_enrol($this->course);
        $this->studenttwo = $generator->create_and_enrol($this->course);
        $this->actor = $generator->create_user();
        $generator->enrol_user($this->actor->id, $this->course->id, 'editingteacher');
        $now = time();
        $DB->insert_record('availability_managed_course', (object) [
            'courseid' => $this->course->id,
            'enabled' => 1,
            'defaultstate' => 'closed',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $this->actor->id,
        ]);
        $this->repository = new rule_repository();
        access_manager::reset_cache();
    }

    protected function tearDown(): void {
        access_manager::reset_cache();
        parent::tearDown();
    }

    public function test_no_rule_denies_access(): void {
        $this->assertFalse($this->manager()->is_allowed(
            $this->course->id,
            'cm',
            $this->cm->cmid,
            $this->studentone->id
        ));
    }

    public function test_course_rule_allows_enrolled_users(): void {
        $this->add_rule('course', $this->course->id);

        $this->assertTrue($this->is_student_allowed($this->studentone));
        $this->assertTrue($this->is_student_allowed($this->studenttwo));
    }

    public function test_group_rule_allows_member_and_denies_nonmember(): void {
        $generator = $this->getDataGenerator();
        $group = $generator->create_group(['courseid' => $this->course->id]);
        groups_add_member($group, $this->studentone);
        $this->add_rule('group', $group->id);

        $this->assertTrue($this->is_student_allowed($this->studentone));
        $this->assertFalse($this->is_student_allowed($this->studenttwo));
    }

    public function test_any_matching_group_allows_access(): void {
        $generator = $this->getDataGenerator();
        $unmatched = $generator->create_group(['courseid' => $this->course->id]);
        $matched = $generator->create_group(['courseid' => $this->course->id]);
        groups_add_member($matched, $this->studentone);
        $this->add_rule('group', $unmatched->id);
        $this->add_rule('group', $matched->id);

        $this->assertTrue($this->is_student_allowed($this->studentone));
    }

    public function test_user_rule_allows_only_selected_user(): void {
        $this->add_rule('user', $this->studentone->id);

        $this->assertTrue($this->is_student_allowed($this->studentone));
        $this->assertFalse($this->is_student_allowed($this->studenttwo));
    }

    public function test_rule_does_not_grant_access_to_unenrolled_user(): void {
        global $DB;

        $outsider = $this->getDataGenerator()->create_user();
        $now = time();
        $DB->insert_record('availability_managed_rule', (object) [
            'courseid' => $this->course->id,
            'itemtype' => 'cm',
            'itemid' => $this->cm->cmid,
            'scope' => 'user',
            'scopeid' => $outsider->id,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $this->actor->id,
        ]);

        $this->assertFalse($this->manager()->is_allowed(
            $this->course->id,
            'cm',
            $this->cm->cmid,
            $outsider->id
        ));
    }

    public function test_repository_rejects_cross_course_group(): void {
        $othercourse = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $othercourse->id]);

        $this->expectException(\invalid_parameter_exception::class);
        $this->add_rule('group', $group->id);
    }

    /**
     * Create the access manager under test.
     *
     * @return access_manager
     */
    private function manager(): access_manager {
        return new access_manager($this->repository);
    }

    /**
     * Add a rule for the fixture module.
     *
     * @param string $scope scope
     * @param int $scopeid target id
     */
    private function add_rule(string $scope, int $scopeid): void {
        $this->repository->upsert(
            $this->course->id,
            'cm',
            $this->cm->cmid,
            $scope,
            $scopeid,
            $this->actor->id
        );
    }

    /**
     * Check a fixture student.
     *
     * @param \stdClass $student student
     * @return bool
     */
    private function is_student_allowed(\stdClass $student): bool {
        return $this->manager()->is_allowed($this->course->id, 'cm', $this->cm->cmid, $student->id);
    }
}
