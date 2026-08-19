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
 * English language strings.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Managed availability';
$string['closed'] = 'Closed';
$string['close'] = 'Close';
$string['courseconfiguration'] = 'Managed availability configuration';
$string['courseconfiguration_desc'] = 'Enable or disable centralized management for this course.';
$string['courseenabled'] = 'Managed availability has been enabled for the course.';
$string['coursedisabled'] = 'Managed availability has been disabled for the course.';
$string['coursedisabled_error'] = 'Managed availability is not enabled for this course.';
$string['coursedisabled_notice'] = 'Managed availability is not enabled for this course. Enable it before configuring access to sections and activities.';
$string['currentdefaultopen'] = 'New content is initially opened to everyone.';
$string['currentdefaultclosed'] = 'New content is initially created closed.';
$string['disableconfirm'] = 'Are you sure you want to disable Managed availability? Its rules and conditions will be removed from this course. All other Moodle restrictions will remain unchanged.';
$string['disablecourse'] = 'Disable for this course';
$string['disablecourse_desc'] = 'Managed conditions and rules will be removed. Dates, completion, grades, and other Moodle restrictions will not be changed.';
$string['enablecourse'] = 'Enable for this course';
$string['initialstate'] = 'Initial content state';
$string['initialstate_desc'] = 'This choice applies to all existing sections, activities, and resources, and becomes the initial state for new content.';
$string['initialopen'] = 'Keep all content open';
$string['initialopen_desc'] = 'Recommended for existing courses. Enabling the tool will not close content to students.';
$string['initialclosed'] = 'Start with all content closed';
$string['initialclosed_desc'] = 'No student will pass the managed condition until you configure targets. Use this only when you want to prepare release from scratch.';
$string['statusenabled'] = 'Status: enabled';
$string['statusdisabled'] = 'Status: disabled';
$string['applychildren'] = 'Copy section settings to activities';
$string['alreadyclosed'] = 'Already closed';
$string['alreadyclosed_help'] = 'This item is already closed for everyone.';
$string['alreadyopen'] = 'Already open';
$string['alreadyopen_help'] = 'This item is already open for everyone.';
$string['auditlog'] = 'Audit log';
$string['applychildrenconfirm'] = 'Replace the Managed availability configuration of every activity in this section?';
$string['applychildrendone'] = 'Configuration applied to {$a} activities.';
$string['sectioncontrolhint'] = 'The section controls access to everything inside it.';
$string['sectionclosedmodules'] = 'Activities are inaccessible while this section is closed, so their controls are hidden.';
$string['whyhidden'] = 'Why?';
$string['modulecontrolshelp'] = 'The section allows entry. You can now set more specific access for individual activities; these rules cannot grant access to anyone excluded by the section.';
$string['closeeveryone'] = 'Close for everyone';
$string['edititem'] = 'Edit availability for {$a}';
$string['everyone'] = 'Everyone';
$string['groups'] = 'Groups';
$string['help'] = 'Managed availability help';
$string['helptitle'] = 'How to use Managed availability';
$string['helpintro'] = 'Control who can access each course section and activity from one screen.';
$string['openhelp'] = 'Help: how to use this tool';
$string['gotodashboard'] = 'Go to management dashboard';
$string['helpkeytitle'] = 'Key idea';
$string['helpkeytext'] = 'This tool can restrict access further, but it never removes other Moodle restrictions. If an activity also has a date, grade, or completion condition, the student must meet that condition too.';
$string['helpwhat_title'] = 'What you can control';
$string['helpwhat_text'] = 'You can configure sections, activities, and resources for one or more targets:';
$string['helpwhat_everyone'] = 'Everyone: any enrolled student passes this condition.';
$string['helpwhat_groups'] = 'Groups: students pass when they currently belong to any selected group.';
$string['helpwhat_users'] = 'Users: only the individually selected students pass.';
$string['helpwhat_closed'] = 'Closed: nobody passes this condition until you add a target.';
$string['helpedit_title'] = 'Configure a section or activity';
$string['helpedit_step1'] = 'Find the content with the search box or inside its section.';
$string['helpedit_step2'] = 'Select the button that displays its current state.';
$string['helpedit_step3'] = 'Choose Everyone, or select the groups and students who should have access.';
$string['helpedit_step4'] = 'Select Save. The new access applies immediately.';
$string['helpfast_title'] = 'Quick actions';
$string['helpfast_open'] = 'Replaces the current item configuration and opens it to everyone.';
$string['helpfast_close'] = 'Removes all managed targets from the item and leaves it closed.';
$string['helpfast_apply'] = 'Copies the section configuration to all its activities. This happens only when you confirm it; later changes are not synchronized automatically.';
$string['helpadvance_title'] = 'Advance a group or student';
$string['helpadvance_text'] = 'Group and user views let you manually open the next section that is still closed for that target.';
$string['helpadvance_step1'] = 'Choose Group view or User view.';
$string['helpadvance_step2'] = 'Select the target and review which sections are open.';
$string['helpadvance_step3'] = 'Select Open next section. Grades and completion are not considered: this decision is always manual.';
$string['helpcare_title'] = 'Before saving, remember';
$string['helpcare_other'] = 'Normal Moodle restrictions remain active and combine with this configuration.';
$string['helpcare_section'] = 'Closing a section effectively blocks its contents, even if an activity inside appears open.';
$string['helpcare_groups'] = 'Group membership is read from Moodle on every access. Moving a student to another group changes their access.';
$string['helpcare_bulk'] = 'Copy section settings to activities replaces the managed configuration of every activity in that section. Review the confirmation before continuing.';
$string['helpexamples_title'] = 'Common examples';
$string['helpexample_group_title'] = 'Release a unit to Group A';
$string['helpexample_group_text'] = 'Edit the section, select Group A, and save. That is enough to control access to all its content. Configure individual activities only when one needs a different rule.';
$string['helpexample_user_title'] = 'Give one student exceptional access';
$string['helpexample_user_text'] = 'Edit the item, find and select the student, then save. Other students remain closed unless they have access through Everyone or a selected group.';
$string['globallyenabled'] = 'Enable Managed availability';
$string['globallyenabled_desc'] = 'When disabled, managed conditions are temporarily bypassed across the site. Rules and course states are retained, other Moodle restrictions continue to apply, and retained courses are reconciled automatically when this is enabled again.';
$string['globalstatus'] = 'Site-wide status';
$string['globalsuspended_notice'] = 'Managed availability is temporarily suspended by a site administrator. Managed rules are not currently restricting access and this dashboard is read-only.';
$string['globalsuspended_config'] = 'Managed availability is temporarily suspended across the site. Course activation settings cannot be changed until a site administrator enables it again.';
$string['globalsuspended_error'] = 'Managed availability is temporarily suspended across the site.';
$string['managedcoursescount'] = 'Courses currently retained as managed: {$a}';
$string['privacy:metadata:availability_managed_audit'] = 'Functional changes made by teachers and managers.';
$string['privacy:metadata:availability_managed_audit:userid'] = 'The user who made the change.';
$string['privacy:metadata:availability_managed_rule'] = 'Managed availability rules targeting users.';
$string['privacy:metadata:availability_managed_rule:scopeid'] = 'The targeted user ID when the scope is user.';
$string['groupview'] = 'Group view';
$string['groupscount'] = 'Groups: {$a}';
$string['manageavailability'] = 'Manage availability';
$string['openeveryone'] = 'Open for everyone';
$string['open'] = 'Open';
$string['openaction'] = 'Open';
$string['opennext'] = 'Open next section';
$string['opennextdone'] = 'Opened section: {$a}';
$string['opennextnone'] = 'All sections are already open for this target.';
$string['selecttarget'] = 'Select a group or student';
$string['save'] = 'Save';
$string['searchcontent'] = 'Search content';
$string['users'] = 'Users';
$string['userview'] = 'User view';
$string['userscount'] = 'Users: {$a}';
$string['description'] = 'Allowed by Managed availability';
$string['requires_managed'] = 'This content is not currently available to you.';
