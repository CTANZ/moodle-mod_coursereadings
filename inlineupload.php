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
 * Starting point for drag and drop course uploads
 *
 * @package    core
 * @subpackage lib
 * @copyright  2012 Davo smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/coursereadings/lib.php');
require_once($CFG->dirroot.'/mod/coursereadings/dnduploadlib.php');

$articleid = optional_param('articleid', 0, PARAM_INT);
$fieldname = required_param('fieldname', PARAM_TEXT);
$filename = optional_param('filename', '', PARAM_FILE);

if (!$articleid) {
    // New article - add it.
    $source = new stdClass();
    $source->id = optional_param('sourceid', 0, PARAM_INT);
    if (!$source->id) {
        // New source - add it.
        $source = new stdClass();
        $source->type = required_param('source_type', PARAM_TEXT);
        $source->title = required_param('title_of_source', PARAM_TEXT);
        $source->author = optional_param('author_of_source', '', PARAM_TEXT);
        $source->year = required_param('year_of_publication', PARAM_TEXT);
        $source->publisher = optional_param('publisher', '', PARAM_TEXT);
        $source->isbn = required_param('isbn', PARAM_TEXT);
        $source->pages = optional_param('pages', 0, PARAM_INT);
        $source->editor = optional_param('editor_of_source', '', PARAM_TEXT);
        $source->volume = optional_param('volume_number', '', PARAM_TEXT);
        $source->edition = optional_param('edition', '', PARAM_TEXT);
        $source->createdby = $USER->id;
        $source->id = $DB->insert_record('coursereadings_source', $source);
        coursereadings_add_to_queue('source', $source->id);
    }
    $article = new stdClass();
    $article->title = required_param('title_of_article', PARAM_TEXT);
    $article->externalurl = required_param('externalurl', PARAM_TEXT);
    $article->doi = required_param('doi', PARAM_TEXT);
    if (substr($article->doi, 0, 4) === 'doi:') {
        $article->doi = substr($article->doi, 4);
    }
    $article->pagerange = optional_param('page_range', '', PARAM_TEXT);
    $article->author = optional_param('author_of_periodical', '', PARAM_TEXT);
    $article->source = $source->id;
    $article->createdby = $USER->id;
    $article->id = $DB->insert_record('coursereadings_article', $article);
    $articleid = $article->id;
    coursereadings_add_to_queue('article', $article->id);

    // Save uploaded file from draft area.
    $draftitemid = optional_param('draftitemid', 0, PARAM_INT);
    if (!empty($draftitemid)) {
        $data = new stdClass;
        $data->articleid = $article->id;
        $data->filename = $filename;
        coursereadings_save_file($data, $draftitemid);
    }

}

// Return information required to add article to list in article chooser.
$result = $DB->get_record_sql('SELECT a.id, a.title, a.pagerange, a.author, s.author AS sourceauthor, s.title AS sourcetitle, s.year FROM {coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source = s.id WHERE a.id = :articleid', array('articleid'=>$articleid));
if ($result && $result->id) {
    $result->error = 0;
} else {
    $result = new stdClass;
    $result->error = 1;
}
$result->fieldname = $fieldname;
header('Content-type: application/json');
echo json_encode($result);
exit;