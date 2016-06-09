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
 * Course materials Tool Usage report.
 *
 * Provies information on the number of various types of tools (resources) in each course.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

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
$PAGE->set_url('/mod/coursereadings/manage/reports/tool-usage.php', $params);
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

    $content  = html_writer::tag('h3', 'Course Tool Usage Report');
    $formcontent = html_writer::empty_tag('input', array('name'=>'q', 'type'=>'text', 'placeholder'=>'Enter course code'));
    $formcontent .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>'Search'));
    $formcontent = html_writer::div($formcontent);
    $content .= html_writer::tag('form', $formcontent, array('method'=>'get', 'class'=>'coursereadings-report-filter'));
    echo html_writer::tag('div', $content, array('class'=>'coursereadings_dashboard'));
}

$fields = " c.shortname,
			(select count(*) from {resource} where course=c.id) AS fileresources,
			(select count(*) from ({course_modules} rcm  inner join {context} rctx on (rcm.instance=rctx.instanceid and rctx.contextlevel=70 and rcm.module=18))
								inner join {files} rf on rf.component='mod_resource' and rf.contextid=rctx.id where rcm.course=c.id) AS filepdfs,
			(select count(*) from {url} where course=c.id and externalurl like '%ezproxy.canterbury.ac.nz%') AS ezproxyurls,
			(select count(*) from {url} where course=c.id and externalurl not like '%ezproxy.canterbury.ac.nz%') AS otherurls,
			(select count(*) from {coursereadings} where course=c.id) AS coursematerials,
			(select count(*) from {coursereadings_inst_article} ia inner join {coursereadings} i on ia.instanceid=i.id where i.course=c.id) AS articles,
			(select count(*) from {folder} where course=c.id) AS folders,
			(select count(*) from ({course_modules} rcm  inner join {context} rctx on (rcm.instance=rctx.instanceid and rctx.contextlevel=70 and rcm.module=9))
								inner join {files} rf on rf.component='mod_resource' and rf.contextid=rctx.id where rcm.course=c.id) AS folderpdfs";
$from = '{course} c';
$where = 'c.visible=1';
$sqlparams = null;

// Course ID or search - not both at once.
if (!empty($course)) {
    $where = "c.id = :id";
    $sqlparams = array('id'=>$course);
} else if (!empty($search)) {
    $where .= " AND (c.shortname LIKE ? OR c.idnumber LIKE ?)";
    $sqlparams = array('%'.$search.'%', '%'.$search.'%');
}

$table = new table_sql('mod_coursereadings-report_contentusage');
$table->set_attribute('id', 'coursereadings-report-toolusage');
$table->define_baseurl($PAGE->url);
$table->set_sql($fields, $from, $where, $sqlparams);
$table->define_columns(array('shortname', 'fileresources', 'filepdfs', 'otherurls', 'ezproxyurls', 'coursematerials', 'articles', 'folders', 'folderpdfs'));
$table->define_headers(array('Course', 'File resources', 'PDFs (File)', 'Non-ezproxy URLs', 'ezproxy URLs', 'Course Materials', 'Articles', 'Folders', 'PDFs (Folder)'));
$table->is_downloadable(true);
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));

if (!empty($format)) { // Downloading.
    \core\session\manager::write_close();
    // Boost memory and time limits, this can take a while.
    raise_memory_limit(MEMORY_EXTRA);
    core_php_time_limit::raise(300);
    $filename = 'course_tool_usage_' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
    $table->is_downloading($format, $filename);
    $table->out(0, false);
    exit();
}

$table->out(100, false); // 100 per page, no initials bar.

echo "<script>$('#coursereadings-report-toolusage').stickyTableHeaders({fixedOffset: 66});</script>";

echo $OUTPUT->footer();