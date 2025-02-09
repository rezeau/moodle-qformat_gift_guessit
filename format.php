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
 * @copyright  Joseph Rézeau 2021 <joseph@rezeau.org>
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

    protected function get_question_name($line) {    
        if (preg_match('/::(.*?)::/', $line, $matches)) {
            return $matches[1];
        }
        return '';
    }

    protected function extract_question_text($line) {
        if (preg_match('/::(.*?)::(.*?)\{/', $line, $matches)) {
            return $matches[2];
        }
        return '';
    }

    protected function extract_guessitgaps($line) {
        if (!preg_match('/::(.*?)::/', $line) && !preg_match('/\[.*?\|.*?\]/', $line)
        && !preg_match('/^####/', $line) /*&& !preg_match('/^\}/', $line)*/) {
            return trim($line);
        }
        return '';
    }

    protected function extract_param($line) {
        if (preg_match('/\[(.*?)\]/', $line, $matches)) {
            return $matches[1];
        }
        return '';
    }
    protected function extract_elements($input) {        
        $pattern = '/(?:::(.*?)::)?([^\{]*)\{(.*?)(?:\s*\[(\w*)\|(\d*)\|(\d*)\])?(?:####(.*?))?\}/';
        if (preg_match($pattern, $input, $matches)) {
            return [
            'name' => trim($matches[1]) ?? '',
            'description' => isset($matches[2]) && $matches[2] !== '' ? trim($matches[2]) : '',
            'guessitgaps' => isset($matches[3]) ? trim($matches[3]) : '',
            'gaptype' => isset($matches[4]) ? trim($matches[4]) : '',
            'nbtries' => isset($matches[5]) ? trim($matches[5]) : '',
            'rmfb' => isset($matches[6]) ? trim($matches[6]) : '',
            'feedback' => isset($matches[7]) ? trim($matches[7]) : ''
            ];
        }
        return 'ERROR';
    }
    protected function extract_feedback_message($line) {
        if (preg_match('/^####(.*)/', $line, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
    protected function extract_params_elements($params) {
        $elements = explode('|', $params);
        $param1 = $elements[0] ?? null;
        $param2 = $elements[1] ?? null;
        $param3 = $elements[2] ?? null;
        return [
            'display' => $param1 !== '' ? $param1 : null,
            'nbmax' => $param2 !== '' ? $param2 : null,
            'removespecificfeedback' => $param3 !== '' ? $param3 : null
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
    public function readquestion($lines) {
        // Given an array of lines known to define a question in this format, this function
        // converts it into a question object suitable for processing and insertion into Moodle.
        $question = $this->defaultquestion();

        // Define replaced by simple assignment, stop redefine notices.
        $giftanswerweightregex = '/^%\-*([0-9]{1,2})\.?([0-9]*)%/';

        // Separate comments and implode.
        $comments = '';
        foreach ($lines as $key => $line) {
            $line = trim($line);
            if (substr($line, 0, 2) == '//') {
                $comments .= $line . "\n";
                $lines[$key] = ' ';
            }
        }
        $text = trim(implode("\n", $lines));
        if ($text == '') {
            return false;
        }
        // Substitute escaped control characters with placeholders.
        //$text = $this->escapedchar_pre($text);
        // Look for category modifier.
        if (preg_match('~^\$CATEGORY:~', $text)) {
            $newcategory = trim(substr($text, 10));

            // Build fake question to contain category.
            $question->qtype = 'category';
            $question->category = $newcategory;
            return $question;
        }

        $question->qtype = 'guessit';
        /*
        echo '$lines<pre>';
        print_r($lines);
        echo '</pre>';
        */
        echo '$text<pre>';
        print_r($text);
        echo '</pre>';
        
        // todo this relies on guessit questions being entered on ONE LINE only.
        // maybe revise to accept more lines?
        if (!$this->has_curly_braced_string($text, 'braceerror')) {
            return false;
        }
        $elements = $this->extract_elements($text);
        echo '$elements<pre>';
        print_r($elements);
        echo '</pre>';
        echo 'name = |' . $elements['name'] . '|<br>';
        //return false;
        // Get all the needed elements from $lines.
        
        // Now check syntax is OK.
        
        if ($this->check_element($elements['guessitgaps'], 'noguessitgaps', $text) == '') {
            return false;
        }
            return false;
        /*
        if (!$this->check_element($guessitgaps, 'noguessitgaps', $lines[1])) {
            return false;
        }
        */

        // Now complete the question elements.
        $name = ($name == '') ? $guessitgaps : $name;
        $question->name = $name;
        // If no description provided, use $guessitgaps for the question text.
        $questiontext = ($questiontext == '') ? $guessitgaps : $questiontext;

        // Add paragraph tags to separate the description from the gaps line.
        $question->questiontext = '<p>' . $questiontext . '</p>';

        $question->guessitgaps = $guessitgaps;
        if ($display == 'wordle') {
            $question->wordle = '1';
            // Set default if param is missing.
            $nbmax = ($nbmax == '') ? '10' : $nbmax;
            $question->nbmaxtrieswordle = $nbmax;
        } else {
            $question->wordle = '0';
            // Set default if param is missing.
            $nbmax = ($nbmax == '') ? '6' : $nbmax;
            $question->nbtriesbeforehelp = $nbmax;
            // Set default if param is missing.
            $display = ($display == '') ? 'gapsizegrow' : $display;
            $question->gapsizedisplay = $display;
        }
        $question->removespecificfeedback = $rmfb;
        $question->generalfeedback = $gfb;
        return false;
        return $question;
    }
}

