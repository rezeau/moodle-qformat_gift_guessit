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
 * Strings for component 'qformat_gift_guessit', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qformat_gift_guessit
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['braceerror'] = 'Could not find a pair of {...} around word(s) to be guessed -> {$a}';
$string['bracketserror'] = 'Incorrectly matched square brackets in this question -> {$a}';
$string['giftnovalidquestion'] = 'There is an error in your Guessit question formatting. Check the documentation.';
$string['giftqtypenotset'] = 'Question type is not set';
$string['nbtrieserror'] = 'Number of tries {$a->nbtries} not in correct range: 6, 8, 10, 12, 14 -> {$a->line}';
$string['nodescriptionprovided'] = 'No description provided for question n°{$a->questionnumber} -> {$a->questionname}';
$string['noguessitgaps'] = 'Could not find word(s) to be guessed in question -> {$a}';
$string['nohandler'] = 'No handler for question type {$a}';
$string['noname'] = 'No name provided or badly formatted colons for this question -> {$a}';
$string['pluginname'] = 'GIFT to guessit format';
$string['pluginname_help'] = 'GIFT to guessit format enables guessit questions to be imported from a text file.';
$string['pluginname_link'] = 'qformat/gift_guessit';
$string['privacy:metadata'] = 'The GIFT to guessit question format plugin does not store any personal data.';
$string['wordlecapitalsonly'] = 'ERROR! In the Wordle option, You must type a single word and only use UPPERCASE LETTERS (A-Z) and no accents -> {$a}';
$string['wordletoolong'] = 'Too long! ERROR! In the Wordle option, words are limited to 8 characters. -> {$a}';
