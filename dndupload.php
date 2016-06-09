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
 * Starting point for drag and drop Course Material uploads
 *
 * @package    mod_coursereadings
 * @copyright  2014 Paul Nicholls
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/coursereadings/dnduploadlib.php');

$courseid = required_param('course', PARAM_INT);
$section = required_param('section', PARAM_INT);
$type = required_param('type', PARAM_TEXT);
$modulename = required_param('module', PARAM_PLUGIN);
$displayname = optional_param('displayname', null, PARAM_TEXT);
$contents = optional_param('contents', null, PARAM_RAW); // It will be up to each plugin to clean this data, before saving it.

$metadata = new stdClass();
$metadata->draftitemid = optional_param('draftitemid', 0, PARAM_INT);
$metadata->articleid = optional_param('articleid', 0, PARAM_INT);
if ($modulename === 'coursereadings' && empty($metadata->articleid)) {
    // New article - we need metadata.
    $metadata->sourceid = optional_param('sourceid', 0, PARAM_INT);
    $metadata->source_type = required_param('source_type', PARAM_TEXT);
    $metadata->title_of_source = required_param('title_of_source', PARAM_TEXT);
    $metadata->author_of_periodical = optional_param('author_of_periodical', '', PARAM_TEXT);
    $metadata->author_of_source = optional_param('author_of_source', '', PARAM_TEXT);
    $metadata->year = required_param('year_of_publication', PARAM_TEXT);
    $metadata->isbn = required_param('isbn', PARAM_TEXT);
    $metadata->pages = optional_param('pages', 0, PARAM_INT);
    $metadata->volume_number = optional_param('volume_number', '', PARAM_TEXT);
    $metadata->editor_of_source = optional_param('editor_of_source', '', PARAM_TEXT);
    $metadata->edition = optional_param('edition', '', PARAM_TEXT);
    $metadata->publisher = optional_param('publisher', '', PARAM_TEXT);
    $metadata->title_of_article = required_param('title_of_article', PARAM_TEXT);
    $metadata->page_range = optional_param('page_range', '', PARAM_TEXT);
    $metadata->subtype = optional_param('subtype', '', PARAM_TEXT);
    $metadata->furtherinfo = optional_param('furtherinfo', '', PARAM_TEXT);
    $metadata->externalurl = optional_param('externalurl', '', PARAM_TEXT);
    $metadata->doi = optional_param('doi', '', PARAM_TEXT);
}

$dndproc = new coursereadings_dndupload_ajax_processor($courseid, $section, $type, $modulename, $metadata);
$dndproc->process($displayname, $contents);
