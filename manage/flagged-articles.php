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
 * Course materials dashboard - flagged articles.
 *
 * Dashboard for managing course materials which have been flagged on the main dashboard.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);

require_login();
$sortbycourse = optional_param('sortbycourse', 0, PARAM_INT);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/flagged-articles.php');
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

$currenttab = 'flaggedarticles';
include('./managetabs.php');
$buttons = $OUTPUT->pix_icon('i/valid', 'Approve', 'moodle', array('class'=>'btn-approve'));
$buttons .= $OUTPUT->pix_icon('i/edit', 'Edit', 'moodle', array('class'=>'btn-edit'));
$buttons .= $OUTPUT->pix_icon('i/flagged', 'Flag', 'moodle', array('class'=>'btn-flag'));
$buttons .= $OUTPUT->pix_icon('i/invalid', 'Delete', 'moodle', array('class'=>'btn-delete'));
$buttons = html_writer::tag('div', $buttons, array('class'=>'item-controls'));

$podcontent = html_writer::tag('h3', get_string('dashboard_flaggedarticles', 'mod_coursereadings'));
$numflagged = $DB->count_records_select('coursereadings_queue', "type='article' AND notes IS NOT NULL");
$notflagged = $DB->count_records_select('coursereadings_queue', "type='article' AND notes IS NULL");

$extrafrom = '';
$extraselect = '';
if ($sortbycourse) {
	$extraselect = ", firstcourse";
	$extrafrom = "LEFT JOIN (SELECT ia.articleid, MIN(c.shortname) AS firstcourse FROM (({coursereadings_inst_article} ia INNER JOIN {coursereadings} i ON ia.instanceid=i.id)
										INNER JOIN {course} c ON c.id=i.course) GROUP BY ia.articleid) t ON a.id=t.articleid";
}
$articles = $DB->get_records_sql("	SELECT q.id AS queueid, q.notes, a.id, a.title, a.pagerange, a.author AS author, a.externalurl, a.doi,
										s.title AS sourcetitle, s.author AS sourceauthor, s.year, s.pages $extraselect, sq.id AS sqid,
										u.firstname, u.lastname, u.username, a.createdby
									FROM (({coursereadings_article} a INNER JOIN
											({coursereadings_source} s LEFT JOIN {coursereadings_queue} sq ON s.id=sq.objectid and sq.type='source')
										ON a.source = s.id)
										INNER JOIN {coursereadings_queue} q ON q.objectid=a.id) $extrafrom
										LEFT JOIN {user} u ON a.createdby = u.id
									WHERE q.type='article' AND q.notes IS NOT NULL
									ORDER BY ".($sortbycourse ? 'firstcourse' : 'queueid')." ASC");
$baseurl = $CFG->wwwroot.'/mod/coursereadings/manage/flagged-articles.php';
$podcontent .= html_writer::tag('h4', 'Showing '.count($articles).' of '.$numflagged.', sorted by: '.($sortbycourse?'<a href="'.$baseurl.'">date</a> &nbsp;<strong>course</strong>':'<strong>date</strong> &nbsp;<a href="'.$baseurl.'?sortbycourse=1">course</a>'));
if ($notflagged) {
	$podcontent .= html_writer::tag('h4', '(<a href="'.$CFG->wwwroot.'/mod/coursereadings/manage/index.php">show '.$notflagged.' non-flagged new articles</a>)');
}
$list = '';
$modid = $DB->get_field('modules', 'id', array('name'=>'coursereadings'));
foreach ($articles as $article) {
	$file = coursereadings_get_article_file($article, $syscontext);
	$classes = array('coursereadings-dashboard-item', 'coursereadings-article');
	$item = html_writer::start_tag('div', array('class'=>'dashboard-item-detail'));
    if (!empty($article->doi)) {
        $item .= $OUTPUT->pix_icon('doi', 'DOI', 'mod_coursereadings', array('class' => 'iconsmall smallicon'));
    } else if (!empty($article->externalurl)) {
        $item .= $OUTPUT->pix_icon('world_link', 'External URL', 'mod_coursereadings', array('class' => 'iconsmall smallicon'));
    }
	$item .= html_writer::tag('strong', coursereadings_get_article_link($article, $syscontext, $file, true));
	$item .= html_writer::empty_tag('br');
	$item .= 'pages: ' . $article->pagerange;
	if (!empty($article->author)) {
        $item .= '; ' . $article->author;
    }
    $item .= html_writer::empty_tag('br');
    $attrs = array();
    if (empty($article->sqid)) {
    	$attrs['class'] = 'source-approved';
    }
	$item .= html_writer::tag('em', $article->sourcetitle, $attrs);
	$item .= html_writer::empty_tag('br');
	$item .= $article->sourceauthor . ' (' . $article->year . (intval($article->pages) > 0 ? '; ' . $article->pages . 'pp' : '') . ')';

	if (!empty($article->createdby)) {
		$item .= html_writer::empty_tag('br');
		$item .= 'Added by ' . html_writer::link($CFG->wwwroot.'/user/profile.php?id='.$article->createdby, $article->firstname.' '.$article->lastname . ' ('.$article->username.')');
	}
	if (!empty($file)) {
		$item .= html_writer::empty_tag('br');
		$item .= 'Uploaded on ' . userdate($file->get_timecreated()) . ' ';
	}
	$item .= html_writer::end_tag('div');

	$uses = $DB->get_records_sql("	SELECT i.id, i.course, c.shortname, cm.id AS cmid
									FROM (({coursereadings} i INNER JOIN {coursereadings_inst_article} ia ON i.id=ia.instanceid)
										INNER JOIN {course} c ON c.id=i.course) INNER JOIN (
											SELECT id, instance
											FROM {course_modules}
											WHERE module=:modid
										) cm ON cm.instance=i.id
									WHERE ia.articleid = :articleid
									ORDER BY c.shortname ASC", array('articleid'=>$article->id, 'modid'=>$modid));
	$usecontent = html_writer::tag('h4', 'Usage:');
	if (count($uses)) {
		$uselist = '';
		foreach ($uses as $use) {
			$uselist .= html_writer::tag('li', html_writer::link($CFG->wwwroot.'/mod/coursereadings/view.php?id='.$use->cmid, $use->shortname, array('target'=>'_blank')));
		}
		$usecontent .= html_writer::tag('ul', $uselist);
	} else {
		$usecontent .= html_writer::tag('p', 'Not currently in use.');
		$classes[] = 'coursereadings-deletable';
	}
	$item .= html_writer::tag('div', $usecontent, array('class'=>'dashboard-item-usage'));

	$notes = '';
	if (strlen($article->notes)) {
		$notes = html_writer::tag('h4', 'Notes:');
		$notes .= html_writer::tag('pre', $article->notes);
	}
	$item .= html_writer::tag('div', $notes, array('class'=>'dashboard-item-notes'));

	$item .= $buttons;
	$list .= html_writer::tag('li', $item, array('class'=>implode(' ', $classes), 'data-articleid'=>$article->id, 'data-queueid'=>$article->queueid));
}
$podcontent .= html_writer::tag('ul', $list);

echo html_writer::tag('div', $podcontent, array('class'=>'coursereadings_dashboard coursereadings_flagged'));

$PAGE->requires->yui_module('moodle-mod_coursereadings-dashboard', 'M.mod_coursereadings.dashboard.init');
$PAGE->requires->strings_for_js(array('choosefile', 'title_of_article', 'source_type_editing', 'source_book', 'source_journal', 'source_other', 'source_subtype', 'source_subtypes', 'furtherinfo', 'source', 'title_of_source', 'author_of_periodical', 'author_of_source', 'editor_of_source', 'year_of_publication', 'publisher', 'isbn', 'volume_number', 'edition', 'page_range', 'total_pages', 'pages', 'dashboard_mergearticle'), 'mod_coursereadings');
$PAGE->requires->strings_for_js(array('servererror', 'savechanges', 'cancel', 'delete'), 'moodle');

echo $OUTPUT->footer();