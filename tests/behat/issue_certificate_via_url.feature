@mod @mod_certificate
Feature: Issue a certificate on behalf of a user via a URL parameter
  In order to award a certificate to a learner who cannot reach it themselves
  As a manager with the manage capability
  I need to issue the certificate by passing the learner's userid in the URL

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity    | name             | course |
      | certificate | Test Certificate | C1     |

  Scenario: Manager issues a certificate for a student via the userid URL parameter
    Given I am logged in as "teacher1"
    When I visit the "Test Certificate" certificate view page passing the userid of "student1"
    Then I should see "Student One"
    And I should see "Awarded To"
