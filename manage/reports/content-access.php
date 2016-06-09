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
 * Course materials Content Access report.
 *
 * Monthly report on content views/downloads.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$format   = optional_param('download', '', PARAM_ALPHA);
$start    = required_param('start', PARAM_INT);

$params = array('start'=>$start);

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/reports/content-usage.php', $params);
$PAGE->set_pagelayout('base');

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

    $content  = html_writer::tag('h3', 'Content Access Report');
    echo html_writer::tag('div', $content, array('class'=>'coursereadings_dashboard'));
}
$date = new DateTime();
$date->setTimestamp($start);
$date->modify('+ 1 month');
$end = $date->getTimestamp();

$fields = "eventname, count(*) AS value";
$from = '{logstore_standard_log}';
$where = 'eventname IN (:event1, :event2, :event3, :event4) AND timecreated BETWEEN :start AND :end';
$groupby = ' GROUP BY eventname';
$sqlparams = array(
        'start'=>$start, 'end'=>$end,
        'event1'=>'\mod_coursereadings\event\coursereadings_article_downloaded',
        'event2'=>'\mod_coursereadings\event\coursereadings_bundled_pdf_downloaded',
        'event3'=>'\mod_coursereadings\event\coursereadings_bundled_zip_downloaded',
        'event4'=>'\mod_url\event\course_module_viewed');
$countsql = "SELECT COUNT(DISTINCT eventname) FROM $from WHERE $where";

$table = new \mod_coursereadings\table\content_access('mod_coursereadings-report_contentaccess');
$table->define_baseurl($PAGE->url);
$table->set_sql($fields, $from, $where . $groupby, $sqlparams);
$table->set_count_sql($countsql, $sqlparams);
$table->define_columns(array('eventname', 'value'));
$table->define_headers(array('Action', 'Downloads / Views'));

$table->out(100, false); // 100 per page, no initials bar.

echo $OUTPUT->footer();