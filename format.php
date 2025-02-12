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

// Define guessitmode modes.
/**
 * Use all answers (default mode).
 */
define('guessitMODE_DEFAULT',   '0');

/**
 * Manual selection.
 */
define('guessitMODE_MANUAL',     '1');

/**
 * Automatic random selection.
 */
define('guessitMODE_AUTO',    '2');

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

    protected function extract_elements($input) {
        $pattern = '/(?:::(.*?)::)?([^\{]*)\{(.*?)(?:\s*(\[(.*?)\]))?(?:####(.*?))?\}/';        
        if (preg_match($pattern, $input, $matches)) {
            return array_filter([
                'name' => isset($matches[1]) ? trim($matches[1], '[]') : null,
                'description' => isset($matches[2]) ? trim($matches[2], '[]') : null,
                'guessitgaps' => isset($matches[3]) ? trim($matches[3], '[]') : null,
                'params' => isset($matches[4]) ? trim($matches[4], '[]') : null,
                'generalfeedback' => trim($matches[6] ?? '')
            ], function ($value) {
                return $value !== null && $value !== '';
            });
        }
        // Should not happen.
        return 'ERROR';
    }
    
    protected function extract_params_elements($params) {
        $elements = explode('|', $params);
        $display = $elements[0] ?? null;
        $nbmax = $elements[1] ?? null;
        $rmfb = $elements[2] ?? null;
        if (is_numeric($display)) {
            $nbmax = $display;
            $display = null;
        }
        if ($nbmax < 2) {
            $rmfb = $nbmax;
            $nbmax = '';
        }
        return [
            'display' => $display !== null ? $display : null,
            'nbmax' => $nbmax !== null ? $nbmax : null,
            'rmfb' => $rmfb !== null ? $rmfb : null
        ];
    }
    
    protected function check_element($element, $errormsg, $text) {
        if ($element == '') {
            $this->error('<br>' . get_string(''. $errormsg . '', 'qformat_gift_guessit', $text));
            return false;
        }
        return true;
    }
    
    protected function has_curly_braced_string($text, $errormsg) {
        if (preg_match('/\{[^{}]+\}/', $text) === 1) {
            return true;
        } else {
            $this->error('<br>' . get_string(''. $errormsg . '', 'qformat_gift_guessit', $text));
            return false;
        }
    }

    public function readquestions($lines) {
        $questions = array();
        $question = null;
        $endchar = chr(13);
        $questionnumber = 1;
        $hascategory = false;
        foreach ($lines as $line) {
            $newlines = explode($endchar, $line);
            $linescount = count($newlines);
            for ($i=0; $i < $linescount; $i++) {
                $nowline = trim($newlines[$i]);
                if (strlen($nowline) < 2) {
                    continue;
                }
                $hascategory = preg_match('~^\$CATEGORY:~', $line);
                if (!$hascategory && (substr($line, 0, 2) !== '//')) {
                    if (!$this->has_curly_braced_string($nowline, 'braceerror')) {
                    continue;
                    }
                }

                if (substr($line, 0, 2) !== '//') {
                    $question = $this->readquestion($line);
                    if (!$hascategory && $question->questiontext == '') {
                        // todo put this string in language file
                        echo '<br>NO DESCRIPTION PROVIDED for question: ' . $questionnumber . ' ' .$question->name.  '<br>';
                    }
                    $questions[] = $question;
                    if (!$hascategory) {
                        $questionnumber++;
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
     * @param array $lines The array of lines defining a question.
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
        $display = 'gapsizegrow';
        $question->gapsizedisplay = 'gapsizegrow';
        $nbmax = '6';
        $rmfb = '0';
        $question->nbmaxtrieswordle = '10';
        $question->nbtriesbeforehelp = '6';
        
        $elements = $this->extract_elements($line);
        if ($this->check_element($elements['guessitgaps'], 'noguessitgaps', $line) == '') {
            return false;
        }
        if (isset($elements['params'])) {
            $params = $this->extract_params_elements($elements['params']);
            $nbmax = ($params['nbmax'] == null) ? null : $params['nbmax'];
            $display = ($params['display'] == null) ? 'gapsizegrow' : $params['display'];
            $nbmax = ($params['nbmax'] == null) ? null : $params['nbmax'];
            $rmfb = ($params['rmfb'] == null) ? null : $params['rmfb'];
        }
        $name = isset($elements['name']) ? $elements['name'] : '';
        $description = isset($elements['description']) ? $elements['description'] : '';
        $guessitgaps = isset($elements['guessitgaps']) ? $elements['guessitgaps'] : '';
        $generalfeedback = isset($elements['generalfeedback']) ? $elements['generalfeedback'] : '';

        // Add paragraph tags to separate the description from the gaps line.
        $description = ($description == null) ? '' : '<p>' . $description . '</p>';
        /** Description goes in fact to the Question text field in all Moodle questions.
         * But in the guessit question it has been made optional, so can be entered as ''.
         * NO DESCRIPTION PROVIDED is only displayed on the import page.
        */
        // If no name provided, use the description if exists.
        $name = ($name == '') ? $description : $name;
        $name = ($name == '') ? $guessitgaps : $name;
        $question->questiontext = $description;
        $generalfeedback = ($generalfeedback == '') ? '' : $generalfeedback;
        $question->name = $name;
        if ($display == 'wordle') {
            $question->wordle = '1';
            // Set default if param is missing.
            $nbmax = ($nbmax == '') ? '10' : $nbmax;
            $question->nbmaxtrieswordle = $nbmax;
            if (preg_match('/[^A-Z]/', $guessitgaps) ) {
                $this->error('<br>' . get_string('wordlecapitalsonly', 'qformat_gift_guessit', '<br>' . $line));
                return false;
            };
        } else {
            $question->wordle = '0';
            // Set default if param is missing.
            $nbmax = ($nbmax == '') ? '6' : $nbmax;
            $question->nbtriesbeforehelp = $nbmax;
            $question->gapsizedisplay = $display;
        }

        $question->guessitgaps = $guessitgaps;
        $question->removespecificfeedback = $rmfb;
        $question->generalfeedback = $generalfeedback;
        // To prevent penalty display when viewing guessit questions.
        $question->penalty = 0;
        // Remove all useless elements from question.        
        unset($question->image, $question->usecase, $question->multiplier,
            $question->answernumbering,
            $question->correctfeedback, $question->length, $question->partiallycorrectfeedback, $question->incorrectfeedback, $question->shuffleanswers);
        return $question;
    }
}

