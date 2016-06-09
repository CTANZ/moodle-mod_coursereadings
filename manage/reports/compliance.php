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
 * Course materials Compliance report.
 *
 * Report on articles used in courses, which attempts to meet the column specification
 * for compliance reporting.
 *
 * @package mod_coursereadings
 * @copyright 2016 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$format = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('q', '', PARAM_CLEAN);
$course = optional_param('c', '', PARAM_INT);
$period = optional_param('period', 0, PARAM_INT);

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
$PAGE->set_url('/mod/coursereadings/manage/reports/compliance.php', $params);
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
    if (!empty($period)) {
        $periodname = $DB->get_field('coursereadings_reportperiod', 'name', array('id' => $period));
    }
    if (!empty($periodname)) {
        $periodname = ' - ' . $periodname;
    } else {
        $periodname = '';
    }
    $content  = html_writer::tag('h3', 'Compliance Report' . $periodname);
    $formcontent = html_writer::empty_tag('input', array('name'=>'q', 'type'=>'text', 'placeholder'=>'Enter course code'));
    $formcontent .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>'Search'));
    $formcontent = html_writer::div($formcontent);
    $content .= html_writer::tag('form', $formcontent, array('method'=>'get', 'class'=>'coursereadings-report-filter'));
    echo html_writer::tag('div', $content, array('class'=>'coursereadings_dashboard'));
    if (!empty($period) && !$DB->sql_regex_supported()) {
        echo $OUTPUT->notify_problem(get_string('sqlregexnotsupported', 'mod_coursereadings'));
    }
}

$moduleid = $DB->get_field('modules', 'id', array('name'=>'coursereadings'));

$fields = " DISTINCT CONCAT(c.id,'.',a.id) AS id, c.shortname AS course, c.fullname AS coursetitle,
            COALESCE(enr.enrolments, 0) AS enrolments,
            s.type, s.subtype, s.title AS sourceTitle, s.volume, s.edition, s.isbn,  s.pages AS totalPages, s.publisher,
            COALESCE(a.year, s.year) AS year, COALESCE(a.author, s.author) AS author,
            a.title AS articleTitle, a.pagerange, a.totalpages AS scannedpages";
$from = '((({coursereadings} i inner join {coursereadings_inst_article} ia on i.id=ia.instanceid)
            inner join ({coursereadings_article} a inner join {coursereadings_source} s on a.source=s.id) on ia.articleid=a.id)
            inner join {course} c on i.course = c.id) inner join {course_modules} cm on (cm.module=? and cm.course=c.id and cm.instance=i.id)
            LEFT join {coursereadings_enrolments} enr on c.id=enr.courseid';
$where = "c.visible=1 and cm.visible=1
            and enr.enrolments > 0
            and (a.externalurl IS NULL OR TRIM(a.externalurl) = '')
            and (a.doi IS NULL OR TRIM(a.doi) = '')";
$sqlparams = array($moduleid);

// Course ID or search - not both at once.
if (!empty($course)) {
    $where .= " AND c.id = ?";
    $sqlparams[] = $course;
} else if (!empty($search)) {
    $where .= " AND (c.shortname LIKE ? OR c.idnumber LIKE ?)";
    $sqlparams[] = '%'.$search.'%';
    $sqlparams[] = '%'.$search.'%';
} else if (!empty($period) && $DB->sql_regex_supported()) {
    // A reporting period has been selected, and the DB supports regex matching - add the filter.
    if ($record = $DB->get_record('coursereadings_reportperiod', array('id' => $period))) {
        $field = get_config('coursereadings', 'courseidfield');
        if (!in_array($field, array('idnumber', 'shortname'))) {
            // Default to "idnumber" field if setting not configured to a valid value.
            $field = 'idnumber';
        }
        $where .= " AND c.$field " . $DB->sql_regex() . " ?";
        $sqlparams[] = $record->pattern;
    }
}

$table = new \mod_coursereadings\table\compliance('mod_coursereadings-report_compliance');
$table->set_attribute('id', 'coursereadings-report-compliance');
$table->define_baseurl($PAGE->url);
$table->set_sql($fields, $from, $where, $sqlparams);
$table->define_columns(array('course', 'coursetitle', 'isbn', 'publisher', 'year', 'type', 'subtype', 'sourcetitle', 'volume', 'edition', 'articletitle', 'author', 'scannedpages', 'pagefrom', 'pageto', 'enrolments', 'totalpages'));
$table->define_headers(array('Course Code', 'Course Title', 'ISBN/ISSN', 'Publisher', 'Date of Publication', 'Major material type', 'Minor material type', 'Title of book/journal', 'Volume', 'Issue', 'Title of section copied (chapter/article)', 'Author (s)', 'Amount copied', 'Page No. From', 'Page No. To', 'Student Numbers', 'Total page count'));
$table->no_sorting('pagefrom');
$table->no_sorting('pageto');
$table->no_sorting('type');
$table->no_sorting('subtype');
$table->is_downloadable(true);
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));

if (!empty($format)) { // Downloading.
    \core\session\manager::write_close();
    // Boost memory and time limits, this can take a while.
    raise_memory_limit(MEMORY_EXTRA);
    core_php_time_limit::raise(300);
    $filename = 'course_material_compliance_' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
    $table->is_downloading($format, $filename);
    $table->out(0, false);
    exit();
}

$table->out(100, false); // 100 per page, no initials bar.

echo "<script>$('#coursereadings-report-compliance').stickyTableHeaders({fixedOffset: 66});</script>";

echo $OUTPUT->footer();