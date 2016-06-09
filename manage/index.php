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
 * Course materials dashboard.
 *
 * Dashboard for managing course materials.
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
$PAGE->set_url('/mod/coursereadings/manage/index.php');
$PAGE->set_pagelayout('base');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');
echo $OUTPUT->header();

$currenttab = 'dashboard';
include('./managetabs.php');
$content = '';
$buttons = $OUTPUT->pix_icon('i/valid', 'Approve', 'moodle', array('class'=>'btn-approve'));
$buttons .= $OUTPUT->pix_icon('i/edit', 'Edit', 'moodle', array('class'=>'btn-edit'));
$buttons .= $OUTPUT->pix_icon('i/flagged', 'Flag', 'moodle', array('class'=>'btn-flag'));
$buttons = html_writer::tag('div', $buttons, array('class'=>'item-controls'));

$flagged = $DB->count_records_select('coursereadings_queue', "type='article' AND notes IS NOT NULL");
$articles = $DB->get_records_sql("  SELECT q.id AS queueid, a.id, a.title, a.pagerange, a.author AS author, a.doi, a.externalurl,
                                            s.title AS sourcetitle, s.author AS sourceauthor, s.year, s.pages, sq.id AS sqid,
                                            u.firstname, u.lastname, u.username, a.createdby,
                                            (SELECT GROUP_CONCAT(DISTINCT c.shortname)
                                                FROM ({coursereadings_inst_article} ia INNER JOIN {coursereadings} i ON ia.instanceid=i.id)
                                                    INNER JOIN {course} c ON i.course = c.id
                                                WHERE ia.articleid = a.id) AS courses
                                    FROM ({coursereadings_article} a INNER JOIN
                                            ({coursereadings_source} s LEFT JOIN {coursereadings_queue} sq ON s.id=sq.objectid and sq.type='source')
                                        ON a.source = s.id)
                                        INNER JOIN {coursereadings_queue} q ON q.objectid=a.id
                                        LEFT JOIN {user} u ON a.createdby = u.id
                                    WHERE q.type='article' AND q.notes IS NULL");

$podcontent = html_writer::tag('h3', count($articles).' '.get_string('dashboard_newarticles', 'mod_coursereadings'));
if ($flagged) {
    $podcontent .= html_writer::tag('h4', '(<a href="'.$CFG->wwwroot.'/mod/coursereadings/manage/flagged-articles.php">show '.$flagged.' flagged articles</a>)');
}
$list = '';
foreach ($articles as $article) {
    $file = coursereadings_get_article_file($article, $syscontext);
    $item = '';
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
    if (!empty($article->sourceauthor) || !empty($article->year) || !empty($article->pages)) {
        $item .= html_writer::empty_tag('br');
        $item .= $article->sourceauthor;
        if (!empty($article->year) || !empty($article->pages)) {
            $item .= ' (' . $article->year . (intval($article->pages) > 0 ? '; ' . $article->pages . 'pp' : '') . ')';
        }
    }
    $item .= html_writer::empty_tag('br');
    if (!empty($article->courses)) {
        $item .= $article->courses . ' ';
    }
    if (!empty($article->createdby)) {
        $item .= html_writer::empty_tag('br');
        $item .= 'Added by ' . html_writer::link($CFG->wwwroot.'/user/profile.php?id='.$article->createdby, $article->firstname.' '.$article->lastname . ' ('.$article->username.')');
    }
    if (!empty($file)) {
        $item .= html_writer::empty_tag('br');
        $item .= 'Uploaded on ' . userdate($file->get_timecreated()) . ' ';
    }
    $item .= $buttons;
    $list .= html_writer::tag('li', $item, array('class'=>'coursereadings-dashboard-item coursereadings-article', 'data-articleid'=>$article->id, 'data-queueid'=>$article->queueid));
}
$podcontent .= html_writer::tag('ul', $list);
$content .= html_writer::tag('div', $podcontent, array('class'=>'coursereadings_dashboard_pod'));

$flagged = $DB->count_records_select('coursereadings_queue', "type='source' AND notes IS NOT NULL");
$sources = $DB->get_records_sql("SELECT q.id AS queueid, s.*,
                                        u.firstname, u.lastname, u.username
                                FROM {coursereadings_queue} q
                                        INNER JOIN {coursereadings_source} s ON q.objectid=s.id
                                        LEFT JOIN {user} u ON s.createdby = u.id
                                WHERE q.type='source' AND q.notes IS NULL");
$podcontent = html_writer::tag('h3', count($sources).' '.get_string('dashboard_newsources', 'mod_coursereadings'));
if ($flagged) {
    $podcontent .= html_writer::tag('h4', '(<a href="'.$CFG->wwwroot.'/mod/coursereadings/manage/flagged-sources.php">show '.$flagged.' flagged sources</a>)');
}
$list = '';
foreach ($sources as $source) {
    $item = html_writer::tag('strong', $source->title);
    $item .= html_writer::empty_tag('br');
    $item .= $source->author . ' (' . $source->year . (intval($source->pages) > 0 ? '; ' . $source->pages . 'pp' : '') . ')';
    $item .= html_writer::empty_tag('br');
    $item .= $source->publisher;
    $item .= html_writer::empty_tag('br');
    $item .= $source->isbn;
    if (!empty($source->volume) || !empty($source->edition)) {
        $item .= html_writer::empty_tag('br');
        if (!empty($source->volume)) {
            $item .= 'vol ' . $source->volume;
            if (!empty($source->edition)) {
                $item .= '; ';
            }
        }
        if (!empty($source->edition)) {
            $item .= 'ed ' . $source->edition;
        }
    }
    if (!empty($source->createdby)) {
        $item .= html_writer::empty_tag('br');
        $item .= 'Added by ' . html_writer::link($CFG->wwwroot.'/user/profile.php?id='.$source->createdby, $source->firstname.' '.$source->lastname . ' ('.$source->username.')');
    }
    $item .= $buttons;
    $list .= html_writer::tag('li', $item, array('class'=>'coursereadings-dashboard-item coursereadings-source', 'data-sourceid'=>$source->id, 'data-queueid'=>$source->queueid));
}
$podcontent .= html_writer::tag('ul', $list);
$content .= html_writer::tag('div', $podcontent, array('class'=>'coursereadings_dashboard_pod'));

$mooid = $DB->sql_concat("c.id", "'.'", "s.id");
$sortbycourse = optional_param('sortbycourse', 0, PARAM_INT);
if ($sortbycourse) {
    $breachorder = 'c.shortname ASC';
} else {
    $breachorder = 'lastadded DESC';
}
$breaches = $DB->get_records_sql("  SELECT  $mooid AS mooid, c.id AS courseid, c.shortname, s.title AS sourcetitle, s.id AS sourceid, s.pages, COUNT(distinct a.id) AS numArticles,
                                            COUNT(caa.id) AS numApproved, ca.blanketapproval, MAX(i.timemodified) AS lastadded, COUNT(distinct bn.id) AS numNotes, SUM(a.totalpages) AS articlePages
                                    FROM (((({coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source=s.id)
                                        INNER JOIN ({coursereadings} i INNER JOIN {coursereadings_inst_article} ia ON i.id=ia.instanceid) ON ia.articleid=a.id)
                                        INNER JOIN {course} c ON i.course = c.id)
                                        LEFT JOIN ({coursereadings_approval} ca INNER JOIN {coursereadings_appr_article} caa ON ca.id=caa.approvalid) ON ca.courseid = c.id AND caa.articleid = a.id)
                                        LEFT JOIN {coursereadings_breach_note} bn ON (bn.courseid=c.id AND bn.sourceid=s.id)
                                    WHERE s.type = 'book' AND c.visible = 1
                                    GROUP BY s.id, c.id
                                    HAVING  (numArticles > 1 OR
                                                (numArticles = 1 AND articlePages IS NOT NULL AND s.pages IS NOT NULL AND articlePages > (0.1*s.pages))
                                            ) AND (numApproved < numArticles AND (blanketapproval IS NULL OR blanketapproval = 0))
                                    ORDER BY $breachorder");
$podcontent = html_writer::tag('h3', count($breaches).' '.get_string('dashboard_possiblebreaches', 'mod_coursereadings'));
$baseurl = $CFG->wwwroot.'/mod/coursereadings/manage/index.php';
$podcontent .= html_writer::tag('h4', 'Sort by: '.($sortbycourse?'<a href="'.$baseurl.'">date</a> &nbsp;<strong>course</strong>':'<strong>date</strong> &nbsp;<a href="'.$baseurl.'?sortbycourse=1">course</a>'));
$list = '';
$buttons = $OUTPUT->pix_icon('i/edit', 'Manage', 'moodle', array('class'=>'btn-edit'));
$buttons .= $OUTPUT->pix_icon('i/manual_item', 'Add note', 'moodle', array('class'=>'btn-addnote'));
$buttons = html_writer::tag('div', $buttons, array('class'=>'item-controls'));
foreach ($breaches as $breach) {
    $item = html_writer::tag('strong', $breach->shortname);
    $item .= html_writer::empty_tag('br');
    $item .= $breach->sourcetitle . (intval($breach->pages) > 0 ? ' (' . $breach->pages . 'pp' . ')' : '');
    $item .= html_writer::empty_tag('br');
    $item .= $breach->numarticles . ' articles used; ' . userdate($breach->lastadded, get_string('strftimedatefullshort', 'langconfig'));
    if ($breach->numapproved) {
        $item .= ' (' . $breach->numapproved . ' already approved)';
    }
    if ($breach->numnotes > 0) {
        $item .= html_writer::empty_tag('br');
        $item .= html_writer::tag('span', ($breach->numnotes == 1) ? 'One note' : $breach->numnotes . ' notes', array('class'=>'coursereadings-breach-notetoggle'));
        $item .= html_writer::tag('div', '', array('class'=>'coursereadings-breach-notes'));
    }
    $item .= $buttons;
    $list .= html_writer::tag('li', $item, array('class'=>'coursereadings-dashboard-item coursereadings-breach', 'data-sourceid'=>$breach->sourceid, 'data-courseid'=>$breach->courseid));
}
$podcontent .= html_writer::tag('ul', $list, array('class'=>'coursereadings-dashboard-breaches'));
$content .= html_writer::tag('div', $podcontent, array('class'=>'coursereadings_dashboard_pod coursereadings_dashboard_pod_last'));

echo html_writer::tag('div', $content, array('class'=>'coursereadings_dashboard'));

$PAGE->requires->yui_module('moodle-mod_coursereadings-dashboard', 'M.mod_coursereadings.dashboard.init');
$strings = array(
    'choosefile', 'source_type_editing', 'source_book', 'source_journal', 'source_other', 'source_subtype', 'source_subtypes',
    'title_of_article', 'source', 'author_of_periodical', 'page_range', 'total_pages', 'externalurl', 'doi',
    'title_of_source', 'author_of_source', 'editor_of_source', 'year_of_publication', 'publisher', 'isbn', 'volume_number', 'edition', 'pages', 'furtherinfo',
    'scanned', 'scanned_notall', 'approve', 'approve_within_limits', 'approve_with_notes',
    'dashboard_mergesource', 'dashboard_mergearticle'
);
$PAGE->requires->strings_for_js($strings, 'mod_coursereadings');
$PAGE->requires->strings_for_js(array('servererror', 'savechanges', 'cancel', 'delete'), 'moodle');
$PAGE->requires->strings_for_js(array('addnewnote'), 'notes');

echo $OUTPUT->footer();