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
 * Course materials reporting dashboard.
 *
 * Provides access to the various reports offered by the plugin.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/index.php');
$PAGE->set_pagelayout('base');

$action = optional_param('action', '', PARAM_ALPHANUMEXT);

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('dashboard_reports', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/reports/index.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');


if ($action === 'enrolmentreset') {
	if ($DB->sql_regex_supported()) {
		$pid = required_param('period', PARAM_INT);
		if ($period = $DB->get_record('coursereadings_reportperiod', array('id' => $pid))) {
			$confirm = optional_param('confirm', 0, PARAM_BOOL);
			$reportsurl = new moodle_url('/mod/coursereadings/manage/reports/index.php');
			if ($confirm) {
				// Reset the selected period's tracked enrolments.
        		$field = get_config('coursereadings', 'courseidfield');
		        $where = "courseid IN (SELECT id FROM {course} WHERE $field " . $DB->sql_regex() . " ?)";
		        $params = array($period->pattern);
				$DB->set_field_select('coursereadings_enrolments', 'enrolments', 0, $where, $params);
				$DB->set_field_select('coursereadings_enrolments', 'lastreset', time(), $where, $params);
				redirect($reportsurl, get_string('trackedenrolments_reset_completed', 'mod_coursereadings'), 3);
			} else {
				$params = array('action' => $action, 'period' => $pid, 'confirm' => 1);
				$continue = new moodle_url('/mod/coursereadings/manage/reports/index.php', $params);
				$prompt = get_string('trackedenrolments_reset_intro', 'mod_coursereadings', $period->name);
				$prompt .= html_writer::empty_tag('br');
				$prompt .= get_string('trackedenrolments_reset_warning', 'mod_coursereadings');
				$prompt = $OUTPUT->notify_problem($prompt);
				echo $OUTPUT->header();
				echo html_writer::tag('h3', get_string('trackedenrolments_reset', 'mod_coursereadings'), array('style' => 'text-align:center;'));
				echo $OUTPUT->confirm($prompt, $continue, $reportsurl);
				echo $OUTPUT->footer();
			}
			exit;
		}
	} else {
		echo $OUTPUT->notify_problem(get_string('sqlregexnotsupported_reset', 'mod_coursereadings'));
	}
}


echo $OUTPUT->header();

$currenttab = 'reports';
include('../managetabs.php');

$resources = $DB->get_record_sql("SELECT 	(SELECT COUNT(*) FROM mdl_coursereadings) AS readings,
											(SELECT COUNT(*) FROM mdl_coursereadings_noncopyright) AS noncopyright,
											(SELECT COUNT(*) FROM mdl_coursereadings_article) AS articles,
											(SELECT COUNT(*) FROM mdl_coursereadings_source) AS sources,
											(SELECT COUNT(*) FROM mdl_url WHERE externalurl LIKE '%ezproxy.canterbury.ac.nz%') AS ezproxyurls,
											(SELECT COUNT(*) FROM mdl_url WHERE externalurl NOT LIKE '%ezproxy.canterbury.ac.nz%') AS otherurls,
											(SELECT COUNT(*) FROM mdl_url) AS totalurls");

$content  = '<ul>';
$content .= '<li><h3><a href="page-counts.php">Articles without page counts</a></h3></li>';
$options = '';
$periods = $DB->get_records('coursereadings_reportperiod', null, 'sortorder ASC');
foreach ($periods as $period) {
	$options .= '<option value="'.$period->id.'">'.$period->name.'</option>';
}
$content .= '<li>
				<h3>Content usage report</h3>
				<form action="content-usage.php" method="get">
					<div style="text-align:center;">
						<select name="period">
							<option value="">All</option>
							'.$options.'
						</select>
						<input type="submit" value="Generate report">
					</div>
				</form>
			 </li>';
$content .= '<li>
				<h3>Compliance report</h3>
				<form action="compliance.php" method="get">
					<div style="text-align:center;">
						<select name="period">
							<option value="">All</option>
							'.$options.'
						</select>
						<input type="submit" value="Generate report">
					</div>
				</form>
				<hr>
				<h3>Reset tracked enrolment figures</h3>
				<div class="alert alert-error" style="max-width:500px;margin:10px auto;">
					' . get_string('trackedenrolments_reset_warning', 'mod_coursereadings') . '
				</div>
				<form action="index.php" method="get">
					<div style="text-align:center;">
						<input type="hidden" name="action" value="enrolmentreset">
						<select name="period">
							'.$options.'
						</select>
						<input type="submit" value="Reset enrolments">
					</div>
				</form>
			 </li>';
$content .= '<li><h3><a href="source-usage.php">Most-used sources report</a></h3></li>';
$content .= '<li><h3><a href="tool-usage.php">Course tool usage report</a></h3></li>';
$content .= '<li>
				<h3>Learn Resources</h3>
				<table class="flexible generaltable">
					<thead>
						<tr>
							<th class="header">Course Readings instances</th>
							<th class="header">Sources</th>
							<th class="header">Articles</th>
							<th class="header">Non-Copyright PDFs</th>
							<th class="header">ezproxy URLs</th>
							<th class="header">Other URLs</th>
							<th class="header">All URLs</th>
						</tr>
					</thead>
					<tbody>
						<tr class="r0">
							<td class="cell">'.$resources->readings.'</td>
							<td class="cell">'.$resources->sources.'</td>
							<td class="cell">'.$resources->articles.'</td>
							<td class="cell">'.$resources->noncopyright.'</td>
							<td class="cell">'.$resources->ezproxyurls.'</td>
							<td class="cell">'.$resources->otherurls.'</td>
							<td class="cell">'.$resources->totalurls.'</td>
						</tr>
					</tbody>
				</table>
			 </li>';

$firstday = new DateTime('midnight first day of this month');
$selected = ' selected';
$options = '';
for ($i=1; $i<=6; $i++) {
	$monthname = $firstday->format('F Y');
	$options .= '<option value="'.$firstday->getTimestamp().'">'.$monthname.'</option>';
	$firstday->modify('- 1 day');
	$firstday = new DateTime('midnight first day of '.$firstday->format('F Y'));
}
$content .= '<li>
				<h3>Content download/view report</h3>
				<form action="content-access.php" method="get">
					<div style="text-align:center;">
						<select name="start">
							'.$options.'
						</select>
						<input type="submit" value="Get report">
					</div>
				</form>
			</li>';
$content .= '</ul>';

echo html_writer::tag('div', $content, array('class'=>'coursereadings_dashboard'));

echo $OUTPUT->footer();