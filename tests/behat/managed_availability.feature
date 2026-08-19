@availability_managed @javascript
Feature: Teachers centrally manage course content availability
  In order to release course content safely
  As an editing teacher
  I need managed rules to combine with Moodle restrictions

  Background:
    Given the following "courses" exist:
      | fullname       | shortname | format | initsections |
      | Managed course | MC1       | topics | 2            |
    And the following "users" exist:
      | username | firstname | lastname   |
      | teacher1 | Teacher   | One        |
      | studenta | Alice     | Alpha      |
      | studentb | Bob       | Beta       |
      | studenti | Ingrid    | Individual |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | MC1     | editingteacher |
      | studenta | MC1     | student        |
      | studentb | MC1     | student        |
      | studenti | MC1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group A | MC1     | GA       |
      | Group B | MC1     | GB       |
    And the following "group members" exist:
      | user     | group |
      | studenta | GA    |
      | studentb | GB    |
    And the following "activities" exist:
      | activity | course | section | name   |
      | page     | MC1     | 1       | Page A |
      | page     | MC1     | 1       | Page B |
      | page     | MC1     | 2       | Page C |

  Scenario: Enabling a course exposes the dashboard to its teacher
    Given I am on the "Managed course" "course" page logged in as "admin"
    And I navigate to "Managed availability configuration" in current page administration
    When I press "Enable for this course"
    Then I should see "Managed availability has been enabled for the course."
    And I log out
    And I am on the "Managed course" "course" page logged in as "teacher1"
    And I navigate to "Managed availability" in current page administration
    Then I should see "Page A"
    And I should see "Page B"
    And I should see "Page C"

  Scenario: Closing an activity immediately hides it from students
    Given I am on the "Managed course" "course" page logged in as "admin"
    And I navigate to "Managed availability configuration" in current page administration
    And I press "Enable for this course"
    And I navigate to "Managed availability" in current page administration
    When I click on "//li[contains(., 'Page A')]//button[@data-action='quick-close']" "xpath_element"
    And I wait until the page is ready
    Then I should see "Changes saved"
    And I am on the "Managed course" "course" page logged in as "studenta"
    And I should not see "Page A" in the "region-main" "region"
    And I should see "Page B" in the "region-main" "region"

  Scenario: An activity can be released to one group only
    Given I am on the "Managed course" "course" page logged in as "admin"
    And I navigate to "Managed availability configuration" in current page administration
    And I press "Enable for this course"
    And I navigate to "Managed availability" in current page administration
    When I click on "//li[contains(., 'Page A')]//button[@data-action='edit']" "xpath_element"
    And I set the field "Everyone" to "0"
    And I set the field "Group A" to "1"
    And I press "Save changes"
    And I should see "Changes saved"
    And I am on the "Managed course" "course" page logged in as "studenta"
    Then I should see "Page A" in the "region-main" "region"
    And I am on the "Managed course" "course" page logged in as "studentb"
    And I should not see "Page A" in the "region-main" "region"

  Scenario: An activity can be released to one individual only
    Given I am on the "Managed course" "course" page logged in as "admin"
    And I navigate to "Managed availability configuration" in current page administration
    And I press "Enable for this course"
    And I navigate to "Managed availability" in current page administration
    When I click on "//li[contains(., 'Page A')]//button[@data-action='edit']" "xpath_element"
    And I set the field "Everyone" to "0"
    And I set the field "Ingrid Individual" to "1"
    And I press "Save changes"
    And I should see "Changes saved"
    And I am on the "Managed course" "course" page logged in as "studenti"
    Then I should see "Page A" in the "region-main" "region"
    And I am on the "Managed course" "course" page logged in as "studenta"
    And I should not see "Page A" in the "region-main" "region"

  Scenario: Copying a section rule replaces all child activity rules
    Given I am on the "Managed course" "course" page logged in as "admin"
    And I navigate to "Managed availability configuration" in current page administration
    And I press "Enable for this course"
    And I navigate to "Managed availability" in current page administration
    When I click on "//section[.//li[contains(., 'Page A')]]//div[contains(@class, 'card-header')]//button[@data-action='edit']" "xpath_element"
    And I set the field "Everyone" to "0"
    And I set the field "Group A" to "1"
    And I press "Save changes"
    And I should see "Changes saved"
    And I click on "//section[.//li[contains(., 'Page A')]]//button[@data-action='apply-children']" "xpath_element"
    And I click on "Yes" "button"
    And I am on the "Managed course" "course" page logged in as "studenta"
    Then I should see "Page A" in the "region-main" "region"
    And I should see "Page B" in the "region-main" "region"
    And I am on the "Managed course" "course" page logged in as "studentb"
    And I should not see "Page A" in the "region-main" "region"
    And I should not see "Page B" in the "region-main" "region"

  Scenario: A Moodle date restriction remains effective after managed availability is disabled
    Given the activity "Page A" has a past date restriction
    And I am on the "Managed course" "course" page logged in as "admin"
    And I navigate to "Managed availability configuration" in current page administration
    And I press "Enable for this course"
    And I am on the "Managed course" "course" page logged in as "studenta"
    Then I should not see "Page A" in the "region-main" "region"
    And I am on the "Managed course" "course" page logged in as "admin"
    And I navigate to "Managed availability configuration" in current page administration
    And I press "Disable for this course"
    And I press "Continue"
    And I am on the "Managed course" "course" page logged in as "studenta"
    And I should not see "Page A" in the "region-main" "region"
