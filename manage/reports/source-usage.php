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
 * Course materials Most-Used Sources report.
 *
 * Provides information on which sources are used in the most courses.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$format   = optional_param('download', '', PARAM_ALPHA);
$search   = optional_param('q', '', PARAM_CLEAN);
$course   = optional_param('c', '', PARAM_INT);

$params = array();
if (!empty($search)) {
    $params['q'] = $search;
}
if (!empty($course)) {
    $params['c'] = $course;
}

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/reports/source-usage.php', $params);
$PAGE->set_pagelayout('base');
$PAGE->requires->jquery_plugin('coursereadings-stickytableheaders', 'mod_coursereadings');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('dashboard_reports', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/reports/index.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');

if (empty($format)) { // Not downloading - spit out page content.
    echo $OUTPUT->header();

    $currenttab = 'reports';
    include('../managetabs.php');

    $content  = html_writer::tag('h3', 'Most-Used Sources');
    echo html_writer::tag('div', $content, array('class'=>'coursereadings_dashboard'));
}

$basepath = preg_replace('|https?://[^/]+/|', '/', $CFG->wwwroot);

$fields = " s.id, s.title, s.author, IF(STRCMP(s.type,'other')=0,CONCAT('other (',COALESCE(s.subtype,'unknown'),')'),s.type) AS type,
			s.isbn, s.year, s.pages, s.volume, s.edition, COUNT(DISTINCT c.id) AS numcourses,
			GROUP_CONCAT(DISTINCT CONCAT('<a href=\"$basepath/course/view.php&#63;id=', c.id, '\">', c.shortname, '</a>') ORDER BY c.shortname SEPARATOR ', ') AS courses,
			GROUP_CONCAT(DISTINCT c.shortname ORDER BY c.shortname SEPARATOR '; ') AS shortnames";
$from = '(({coursereadings} i inner join {coursereadings_inst_article} ia on i.id=ia.instanceid)
			inner join ({coursereadings_article} a inner join {coursereadings_source} s on a.source=s.id) on ia.articleid=a.id)
			inner join {course} c on i.course = c.id';
$where = 'c.visible=1';
$sqlparams = null;

$where .= " GROUP BY s.id HAVING COUNT(DISTINCT c.id) > 1";

$table = new \mod_coursereadings\table\source_usage('mod_coursereadings-report_contentusage');
$table->set_attribute('id', 'coursereadings-report-sourceusage');
$table->define_baseurl($PAGE->url);
$table->set_sql($fields, $from, $where, $sqlparams);
if (empty($format) || $format === 'xhtml') {
	// Displayed on screen - include links to sources and courses.
	$table->define_columns(array('title', 'author', 'type', 'volume', 'edition', 'isbn', 'pages', 'year', 'numcourses', 'shortnames'));
	$table->define_headers(array('Title', 'Author', 'Type', 'Volume', 'Edition', 'ISBN/ISSN', 'Pages', 'Year', 'Number of Courses', 'Courses'));
} else {
	// Being downloaded - exclude links to sources and courses.
	$table->define_columns(array('id', 'title', 'author', 'type', 'volume', 'edition', 'isbn', 'pages', 'year', 'numcourses', 'shortnames'));
	$table->define_headers(array('Source ID', 'Title', 'Author', 'Type', 'Volume', 'Edition', 'ISBN/ISSN', 'Pages', 'Year', 'Number of Courses', 'Courses'));
}
$table->is_downloadable(true);
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));

// It's not a slow query, so just wrap it to get a sane count.  The grouping etc makes it more complicated to count without running the whole query.
$table->set_count_sql('SELECT COUNT(id) FROM (SELECT '.$fields.' FROM '.$from.' WHERE '.$where.') t');

if (!empty($format)) { // Downloading.
    \core\session\manager::write_close();
    // Boost memory and time limits, this can take a while.
    raise_memory_limit(MEMORY_EXTRA);
    core_php_time_limit::raise(300);
    $filename = 'source_usage_' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
    $table->is_downloading($format, $filename);
    $table->out(0, false);
    exit();
}

$table->out(100, false); // 100 per page, no initials bar.

echo "<script>$('#coursereadings-report-sourceusage').stickyTableHeaders({fixedOffset: 66});</script>";

echo $OUTPUT->footer();