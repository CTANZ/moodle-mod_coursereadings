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
 * Course materials dashboard - flagged sources.
 *
 * Dashboard for managing course material sources which have been flagged on the main dashboard.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/flagged-sources.php');
$PAGE->set_pagelayout('base');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard  = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');
echo $OUTPUT->header();


$currenttab = 'flaggedsources';
include('./managetabs.php');
$buttons = $OUTPUT->pix_icon('i/valid', 'Approve', 'moodle', array('class'=>'btn-approve'));
$buttons .= $OUTPUT->pix_icon('i/edit', 'Edit', 'moodle', array('class'=>'btn-edit'));
$buttons .= $OUTPUT->pix_icon('i/flagged', 'Flag', 'moodle', array('class'=>'btn-flag'));
$buttons .= $OUTPUT->pix_icon('i/invalid', 'Delete', 'moodle', array('class'=>'btn-delete'));
$buttons = html_writer::tag('div', $buttons, array('class'=>'item-controls'));

$podcontent = html_writer::tag('h3', get_string('dashboard_flaggedsources', 'mod_coursereadings'));
$numflagged = $DB->count_records_select('coursereadings_queue', "type='source' AND notes IS NOT NULL");
$notflagged = $DB->count_records_select('coursereadings_queue', "type='source' AND notes IS NULL");
$sources = $DB->get_records_sql("	SELECT q.id AS queueid, q.notes, s.*
									FROM {coursereadings_source} s INNER JOIN {coursereadings_queue} q ON q.objectid=s.id
									WHERE q.type='source' AND q.notes IS NOT NULL");
$podcontent .= html_writer::tag('h4', 'Showing '.count($sources).' of '.$numflagged);
if ($notflagged) {
	$podcontent .= html_writer::tag('h4', '(<a href="'.$CFG->wwwroot.'/mod/coursereadings/manage/index.php">show '.$notflagged.' non-flagged new sources</a>)');
}
$list = '';
foreach ($sources as $source) {
	$classes = array('coursereadings-dashboard-item', 'coursereadings-source');
	$item = html_writer::start_tag('div', array('class'=>'dashboard-item-detail'));
	$item .= html_writer::tag('h4', $source->title);
	if (!empty($source->author)) {
        $item .= 'Author: ' . $source->author;
		$item .= html_writer::empty_tag('br');
    }
	if (!empty($source->editor)) {
        $item .= 'Editor: ' . $source->editor;
		$item .= html_writer::empty_tag('br');
    }
	if (!empty($source->publisher)) {
        $item .= 'Publisher: ' . $source->publisher;
		$item .= html_writer::empty_tag('br');
    }
	if (!empty($source->isbn)) {
        $item .= 'ISBN/ISSN: ' . $source->isbn;
		$item .= html_writer::empty_tag('br');
    }
	if ($source->type == 'journal') {
        $item .= 'Volume: ' . $source->volume . ', Edition: ' . $source->edition;
		$item .= html_writer::empty_tag('br');
    }
	$item .= $source->year . (intval($source->pages) > 0 ? '; ' . $source->pages . 'pp' : '');
	$item .= html_writer::end_tag('div');

	$uses = $DB->get_records('coursereadings_article', array('source'=>$source->id));
	$usecontent = html_writer::tag('h4', 'Articles:');
	if (count($uses)) {
		$uselist = '';
		foreach ($uses as $use) {
			$uselist .= html_writer::tag('li', html_writer::link('#', $use->id, array('data-articleid'=>$use->id)));
		}
		$usecontent .= html_writer::tag('ul', $uselist);
	} else {
		$usecontent .= html_writer::tag('p', 'Not currently in use.');
		$classes[] = 'coursereadings-deletable';
	}
	$item .= html_writer::tag('div', $usecontent, array('class'=>'dashboard-item-usage'));

	$notes = '';
	if (strlen($source->notes)) {
		$notes = html_writer::tag('h4', 'Notes:');
		$notes .= html_writer::tag('pre', $source->notes);
	}
	$item .= html_writer::tag('div', $notes, array('class'=>'dashboard-item-notes'));

	$item .= $buttons;
	$list .= html_writer::tag('li', $item, array('class'=>implode(' ', $classes), 'data-sourceid'=>$source->id, 'data-queueid'=>$source->queueid));
}
$podcontent .= html_writer::tag('ul', $list);

echo html_writer::tag('div', $podcontent, array('class'=>'coursereadings_dashboard coursereadings_flagged'));

$PAGE->requires->yui_module('moodle-mod_coursereadings-dashboard', 'M.mod_coursereadings.dashboard.init');
$PAGE->requires->strings_for_js(array('choosefile', 'title_of_article', 'source_type_editing', 'source_book', 'source_journal', 'source_other', 'source_subtype', 'source_subtypes', 'furtherinfo', 'source', 'title_of_source', 'author_of_periodical', 'author_of_source', 'editor_of_source', 'year_of_publication', 'publisher', 'isbn', 'volume_number', 'edition', 'page_range', 'pages', 'dashboard_mergesource'), 'mod_coursereadings');
$PAGE->requires->strings_for_js(array('servererror', 'savechanges', 'cancel', 'delete'), 'moodle');

echo $OUTPUT->footer();