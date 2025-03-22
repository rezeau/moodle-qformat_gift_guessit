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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Code for changing gift to guessit when importing gift.
 *
 * @package    qformat_gift_guessit
 * @copyright  Joseph Rézeau 2025 <joseph@rezeau.org>
 * @copyright based on work by 1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/format/xml/format.php');

/**
 * Importer for guessit question format FROM gift files.
 *
 * @copyright  Joseph Rézeau 2021  <joseph@rezeau.org>
 * @copyright based on work by 1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_gift_guessit extends qformat_default {
    /**
     * Explicitly declare the property
     * @var tag
     */
    public $randomselectcorrect;
    /**
     * Provide import
     *
     * @return bool
     */
    public function provide_import() {

        return true;
    }

    /**
     * We do not export
     *
     * @return bool
     */
    public function provide_export() {
        return false;
    }

    /**
     * Check if the given file is capable of being imported by this plugin.
     *
     * Note that expensive or detailed integrity checks on the file should
     * not be performed by this method. Simple file type or magic-number tests
     * would be suitable.
     *
     * @param stored_file $file the file to check
     * @return bool whether this plugin can import the file
     */
    public function can_import_file($file) {
        $mimetypes = [
            mimeinfo('type', '.txt'),
        ];

        return in_array($file->get_mimetype(), $mimetypes);
    }

    /**
     * Extracts elements from a formatted input string using regex.
     *
     * @param string $input The input string to parse.
     * @return array|string An associative array of extracted elements or 'ERROR' on failure.
     */
    protected function extract_elements($input) {
        $pattern = '/(?:::(.*?)::)?([^\{]*)\{(.*?)(?:\s*\[(\d+)\])?(?:####(.*?))?\}/';
        if (preg_match($pattern, $input, $matches)) {
            return array_filter([
                'name' => isset($matches[1]) ? trim($matches[1], '[]') : null,
                'description' => isset($matches[2]) ? trim($matches[2], '[]') : null,
                'guessitgaps' => isset($matches[3]) ? trim($matches[3], '[]') : null,
                'nbtries' => isset($matches[4]) ? trim($matches[4], '[]') : null,
                'generalfeedback' => trim($matches[5] ?? ''),
            ], function ($value) {
                return $value !== null && $value !== '';
            });
        }
        // Should not happen.
        return 'ERROR';
    }

    /**
     * Checks if an element is non-empty; otherwise, logs an error.
     *
     * @param mixed $element The element to check.
     * @param string $errormsg The error message key.
     * @param string $text Additional text for the error message.
     * @return bool True if the element is non-empty, false otherwise.
     */
    protected function check_element($element, $errormsg, $text) {
        if ($element == '') {
            $this->error('<br>' . get_string(''. $errormsg . '', 'qformat_gift_guessit', $text));
            return false;
        }
        return true;
    }

    /**
     * Checks if a string contains a correctly enclosed curly-braced substring.
     *
     * @param string $text The text to check.
     * @param string $errormsg The error message key.
     * @return bool True if a valid curly-braced substring is found, false otherwise.
     */
    protected function has_curly_braced_string($text, $errormsg) {
        if (preg_match('/\{[^{}]+\}/', $text) === 1) {
            return true;
        } else {
            $this->error('<br>' . get_string(''. $errormsg . '', 'qformat_gift_guessit', $text));
            return false;
        }
    }

    /**
     * Checks if a string contains correctly balanced square brackets.
     *
     * @param string $text The text to check.
     * @param string $errormsg The error message key.
     * @return bool True if correctly balanced square brackets, false otherwise.
     */
    protected function is_balanced_brackets($text, $errormsg) {
        // Remove all characters except brackets.
        $brackets = preg_replace('/[^\[\]]/', '', $text);
        // Use a loop to remove balanced pairs iteratively.
        while (strpos($brackets, '[]') !== false) {
            $brackets = str_replace('[]', '', $brackets);
        }
        // If there are remaining brackets, they are unbalanced.
        if (!($brackets === '')) {
            $this->error('<br>' . get_string(''. $errormsg . '', 'qformat_gift_guessit', $text));
            return false;
        }
        return true;
    }

    /**
     * Checks if the given value is in the predefined list of valid values.
     *
     * @param mixed $nbtries The value to check.
     * @return bool True if the value is empty or in the valid list, otherwise false.
     */
    protected function check_value_in_list($nbtries) {
        if ($nbtries == '') {
            return true;
        }
        $validvalues = ['6', '8', '10', '12', '14'];
        return in_array($nbtries    , $validvalues);
    }

    /**
     * Validates whether a line is correctly enclosed with a specific pattern.
     *
     * @param string $line The line to check.
     * @param string $errormsg The error message key.
     * @return bool True if correctly enclosed, false otherwise.
     */
    protected function is_correctly_enclosed($line, $errormsg) {
        $pattern = '/^(::[\w\s]+::)?[^:{}]*\{[^}]+\}/';
        if (preg_match($pattern, $line) === 1) {
            return true;
        } else {
            $this->error('<br>' . get_string(''. $errormsg . '', 'qformat_gift_guessit', $line));
            return false;
        }
    }

    /**
     * Extracts text between square brackets from a given string.
     *
     * @param string $string The input string.
     * @return array An array containing category and idnumber
     */
    protected function extract_between_brackets($string) {
        preg_match('/\[(.*?)\]$/', $string, $matches);
        $idnumber = $matches[1] ?? '';
        // Remove the idnumber part (including brackets) from the original string.
        $category = trim(preg_replace('/\s*\[.*?\]$/', '', $string));
        return ['category' => $category, 'idnumber' => $idnumber];
    }

    /**
     * Parses an array of lines to extract questions.
     *
     * @param array $lines The lines to process.
     * @return array An array of extracted questions.
     */
    public function readquestions($lines) {
        $questions = [];
        $question = null;
        $endchar = chr(13);
        $questionnumber = 1;
        $hascategory = false;
        $categoryname = '';
        foreach ($lines as $line) {
            $newlines = explode($endchar, $line);
            $linescount = count($newlines);
            for ($i = 0; $i < $linescount; $i++) {
                $nowline = trim($newlines[$i]);
                if (strlen($nowline) < 2) {
                    continue;
                }
                $hascategory = preg_match('~^\$CATEGORY:~', $line);
                if (!$hascategory && (substr($line, 0, 2) !== '//')) {
                    if (!$this->has_curly_braced_string($nowline, 'braceerror')
                        || !$this->is_balanced_brackets($nowline, 'bracketserror')) {
                        continue;
                    }
                }

                if (substr($line, 0, 2) !== '//') {
                    $question = $this->readquestion($line);
                    if ($question) {
                        if (!$hascategory && !isset($question->questiontext)) {
                            $question->questiontext = '';
                            $a = new stdClass();
                            $a->questionnumber = $questionnumber;
                            $a->questionname = $question->name;
                            echo('<br>' . get_string('nodescriptionprovided', 'qformat_gift_guessit', $a));
                        }
                        // If gift file has a category and an idnumber, automatically assign a number to each question.
                        if (!$hascategory) {
                            if ($categoryname != '') {
                                $question->idnumber = $categoryname . '-' . $questionnumber;
                            }
                            $questionnumber++;
                        } else {
                            $categoryelements = $this->extract_between_brackets($question->category);
                            $question->category = $categoryelements['category'];
                            $question->idnumber = $categoryelements['idnumber'];
                            $categoryname = $categoryelements['idnumber'];
                        }
                        $questions[] = $question;
                    }
                }
            }
        }
        return $questions;
    }

    /**
     * Parses an array of lines to create a question object suitable for Moodle.
     *
     * Given an array of lines representing a question in a specific format,
     * this method processes the lines and converts them into a question object
     * that can be further processed and inserted into Moodle.
     *
     * @param array $line The array of lines defining a question.
     * @return object The question object generated from the input lines.
     */
    public function readquestion($line) {
        $question = $this->defaultquestion();

        // Look for category modifier.
        if (preg_match('~^\$CATEGORY:~', $line)) {
            $newcategory = trim(substr($line, 10));
            // Build fake question to contain category.
            $question->qtype = 'category';
            $question->category = $newcategory;
            return $question;
        }

        // Init variables with their default values.
        $question->qtype = 'guessit';
        $name = '';
        $description = '';
        $isname = $this->is_correctly_enclosed($line, 'noname');
        if (!$isname) {
            $question = null;
            return false;
            return $question;
        }
        $question->nbmaxtrieswordle = '10';
        $question->nbtriesbeforehelp = '6';
        $elements = $this->extract_elements($line);
        if ($this->check_element($elements['guessitgaps'], 'noguessitgaps', $line) == '') {
            return false;
        }
        $name = isset($elements['name']) ? $elements['name'] : '';
        $description = isset($elements['description']) ? $elements['description'] : '';
        $guessitgaps = isset($elements['guessitgaps']) ? $elements['guessitgaps'] : '';
        $nbtries = isset($elements['nbtries']) ? $elements['nbtries'] : '';
        $generalfeedback = isset($elements['generalfeedback']) ? $elements['generalfeedback'] : '';

        // Check nbtries values from 6 to 14.
        if (!$this->check_value_in_list($nbtries)) {
            $a = new stdClass();
            $a->nbtries = '(' . $nbtries . ')';
            $a->line = $line;
            $this->error('<br>' . get_string('nbtrieserror', 'qformat_gift_guessit', $a));
        }

        // Add paragraph tags to separate the description from the gaps line.
        $description = ($description == null) ? '' : $description;
        // Description goes in fact to the Question text field in all Moodle questions.
        // But in the guessit question it has been made optional, so can be entered as ''.
        // NO DESCRIPTION PROVIDED is only displayed on the import page.
        $name = ($name == '') ? $guessitgaps : $name;
        if ($description !== '') {
            $description = str_replace(':', '', $description);
            $question->questiontext = '<p>' . $description . '</p>';
        }
        $generalfeedback = ($generalfeedback == '') ? '' : $generalfeedback;
        $name = str_replace(':', '', $name);
        $question->name = $name;
        $nbgaps = 0;
        // If guessitgaps is single word, then it's a Wordle.
        $wordle = preg_match('/^\S+$/', $guessitgaps);
        if ($wordle) {
            $question->wordle = '1';
            // Set default if nbtries is missing.
            $nbmax = ($nbtries == '') ? '10' : $nbtries;
            $question->nbmaxtrieswordle = $nbmax;
            if (preg_match('/[^A-Z]/', $guessitgaps) ) {
                $this->error('<br>' . get_string('wordlecapitalsonly', 'qformat_gift_guessit', $line));
                return false;
            }
            if (strlen($guessitgaps) > 8) {
                $this->error('<br>' . get_string('wordletoolong', 'qformat_gift_guessit', $line));
            }
            $nbgaps = count(str_split($guessitgaps));
        } else {
            $question->wordle = '0';
            // Set default if nbtries is missing.
            $nbmax = ($nbtries == '') ? '6' : $nbtries;
            $question->nbtriesbeforehelp = $nbmax;
            $nbgaps = count(explode(' ', $guessitgaps));
        }

        $question->guessitgaps = $guessitgaps;
        $question->generalfeedback = $generalfeedback;
        // To prevent penalty display when viewing guessit questions.
        $question->penalty = 0;
        $question->defaultmark = $nbgaps;
        // Needed if there is an URL link in the feedback text.
        $question->generalfeedbackformat = 1;
        // Remove all useless elements from question.
        unset($question->image, $question->usecase, $question->multiplier,
            $question->answernumbering, $question->correctfeedback, $question->length,
            $question->partiallycorrectfeedback, $question->incorrectfeedback,
            $question->shuffleanswers);
        return $question;
    }
}

