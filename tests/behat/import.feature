@qformat @qformat_gift_guessit
Feature: Test importing questions from GUESSIT format.
  In order to reuse questions
  As a teacher
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
  Scenario: import a GUESSIT file with all kind of formatting
    When I set the field "id_format_gift_guessit" to "1"
    And I upload "question/format/gift_guessit/tests/fixtures/gift_guessit.complete_cases.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "No description provided for question n°2 -> WHISKY"
    And I should see "No description provided for question n°6 -> A rolling stone gathers no moss."
    And I should see "Importing 7 questions from file"
    And I should see "4. Guess an Italian dish that’s cooked in a very hot oven"
    When I press "Continue"
    Then I should see "A rolling stone gathers no moss."

  @javascript @_file_upload
  Scenario: import a GUESSIT file with category name and ID
    When I set the field "id_format_gift_guessit" to "1"
    And I upload "question/format/gift_guessit/tests/fixtures/gift_guessit.english_proverbs.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "No description provided for question n°1 -> An apple a day keeps the doctor away."
    And I should see "Importing 9 questions from file"
    When I press "Continue"
    Then I should see "A bird in hand is worth two in the bush."
    And I should see "enPrvb-A-4"

  @javascript @_file_upload
  Scenario: import wordle with category
    When I set the field "id_format_gift_guessit" to "1"
    And I upload "question/format/gift_guessit/tests/fixtures/gift_guessit.academic_glossary.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "Importing 10 questions from file"
    And I should see "1. distribute according to a plan or set apart for a purpose"
    When I press "Continue"
    Then I should see "ALLOCATE"

  @javascript @_file_upload
  Scenario: import wordle with html tags in description and URL link in feedback
    When I set the field "id_format_gift_guessit" to "1"
    And I upload "question/format/gift_guessit/tests/fixtures/gift_guessit.with_link_in_feedback.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "Importing 1 questions from file"
    And I should see "1. Guess an _Italian_ type of dish"
    When I press "Continue"
    And I should see "PASTA"

  @javascript @_file_upload
  Scenario: import guessit file with errors
    When I set the field "id_format_gift_guessit" to "1"
    And I click on "//a[contains(@href,'#id_generalcontainer')]" "xpath_element"
    And I set the field "id_stoponerror" to "0"
    And I upload "question/format/gift_guessit/tests/fixtures/gift_guessit.with_errors.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    And I should see "Error importing question"
    And I should see "Could not find a pair of {...} around word(s) to be guessed -> Too many cooks"
    And I should see "Could not find a pair of {...} around word(s) to be guessed -> {My tailor is rich."
    And I should see "Could not find a pair of {...} around word(s) to be guessed -> My tailor is rich}."
    And I should see "No name provided or badly formatted colons for this question -> :Question 02::Find this cook{Too many cooks spoil the broth.}"
    And I should see "No name provided or badly formatted colons for this question -> ::Question 03:The description{My tailor is rich.}"
    And I should see "No name provided or badly formatted colons for this question -> :Question 05:{My brother is not a girl.}"
    And I should see "No name provided or badly formatted colons for this question -> ::Question 06{My mum likes me.}"
    And I should see "Incorrectly matched square brackets in this question -> ::Proverb::Description{My sister is not a boy.[6}"
    And I should see "Number of tries (9) not in correct range: 6, 8, 10, 12, 14 -> {My tailor is very rich.[9]}"
    And I should see "Importing 3 questions from file"
    And I should see "3. When there are too many people..."
    And I press "Continue"
    And I should see "A rolling stone gathers no moss."

  @javascript @_file_upload
  Scenario: import guessit wordle file with errors
    When I set the field "id_format_gift_guessit" to "1"
    And I click on "//a[contains(@href,'#id_generalcontainer')]" "xpath_element"
    And I set the field "id_stoponerror" to "0"
    And I upload "question/format/gift_guessit/tests/fixtures/gift_guessit_.wordle_with_errors.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "Error importing question"
    And I should see "Could not find a pair of {...} around word(s) to be guessed -> Too many cooks"
    And I should see "Could not find a pair of {...} around word(s) to be guessed -> {PIZZA"
    And I should see "Could not find a pair of {...} around word(s) to be guessed -> PESTO}"
    And I should see "No name provided or badly formatted colons for this question -> :Question 02::Find this word{SALMI}"
    And I should see "No name provided or badly formatted colons for this question -> ::Question 03:Guess this drink{COFFEE}"
    And I should see "No name provided or badly formatted colons for this question -> :Question 05:{PASTA}"
    And I should see "No name provided or badly formatted colons for this question -> ::Question 06{WHISKY}"
    And I should see "ERROR! In the Wordle option, You must type a single word and only use UPPERCASE LETTERS (A-Z) and no accents -> {tiger}"
    And I should see "Number of tries (5) not in correct range: 6, 8, 10, 12, 14 -> {CROCODILE[5]}"
    And I should see "Too long! ERROR! In the Wordle option, words are limited to 8 characters. -> {CROCODILE}"
    And I should see "No description provided for question n°1 -> CROCODILE"
    And I should see "Importing 3 questions from file"
    And I press "Continue"
    And I should see "CROCODILE"