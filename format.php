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

        // Use regex to extract the required values// Use regex to extract the required values
        if (preg_match('/^::([^:]+)::([^{}]+)\{\s*([\s\S]+?)\s*\[([^\]]+)\]\[(\d+)\]\[(\d+)\](?:\s*####(.*?))?\s*\}$/', $text, $matches)) {
            $name = trim($matches[1] ?? "");
            $questiontext = trim($matches[2] ?? "");
            $guessitgaps = trim($matches[3] ?? "");
            $display = trim($matches[4] ?? "");
            $nbmax = trim($matches[5] ?? "");
            $rmfb = trim($matches[6] ?? "");
            $gfb = isset($matches[7]) ? trim($matches[7]) : ""; // Now properly captures $gfb
        } else {
            echo "ERROR ERROR ERROR";
        }
        $question->qtype = 'guessit';
        $question->name = $name;
        $question->questiontext = $questiontext;
        $question->guessitgaps = $guessitgaps;
        $question->nbtriesbeforehelp = $nbmax;
        $question->nbmaxtrieswordle = $nbmax;
        if ($display == 'wordle') {
            $question->wordle = '1';
        } else {
            $question->wordle = '0';
            $question->gapsizedisplay = $display;
        }
        $question->removespecificfeedback = $rmfb;
        $question->generalfeedback = $gfb;
        echo '$question<pre>';
        print_r($question);
        echo '</pre>';
        //die;
        return $question;
        }
    }

