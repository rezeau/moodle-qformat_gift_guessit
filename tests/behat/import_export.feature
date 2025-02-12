@qformat @qformat_gift_guessit
Feature: Test importing questions from GUESSIT format.
  In order to reuse questions
  As an teacher
  I need to be able to import them in GUESSIT format.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teacher   |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And I am on the "Course 1" "core_question > course question import" page logged in as "teacher"

  @javascript @_file_upload
  Scenario: import some GUESSIT questions
    When I set the field "id_format_guessit" to "1"
    And I upload "question/format/guessit/tests/fixtures/examples.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I pause
    And I should see "Importing 9 questions from file"
    And I should see "What's between orange and green in the spectrum?"
    When I press "Continue"
    Then I should see "colours"

  @javascript @_file_upload
  Scenario: import a GUESSIT file which specifies the category
    When I set the field "id_format_guessit" to "1"
    And I upload "question/format/guessit/tests/fixtures/examples02.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I pause
    And I should see "Importing 4 questions from file"
    And I should see "Match the activity to the description."
    When I press "Continue"
    Then I should see "Moodle activities"
