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
 * Course materials reporting periods (list thereof).
 *
 * Provides a list of reporting periods, which can be edited..
 *
 * @package mod_coursereadings
 * @copyright 2016 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
$params = array();

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managereportperiods', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/reportperiods.php', $params);
$PAGE->set_pagelayout('base');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', '', PARAM_INT);
if (!empty($action) && !empty($id)) {
    switch($action) {
        case 'move':
            $pos = required_param('pos', PARAM_INT);
            $record = $DB->get_record('coursereadings_reportperiod', array('id' => $id), 'id, sortorder');
            if ($record) {
                $DB->set_field('coursereadings_reportperiod', 'sortorder', $record->sortorder, array('sortorder' => $pos));
                $DB->set_field('coursereadings_reportperiod', 'sortorder', $pos, array('id' => $id));
            }
            break;
    }
}

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('dashboard_reportperiods', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/reportperiods.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');

echo $OUTPUT->header();

$currenttab = 'reportperiods';
include('./managetabs.php');

echo html_writer::tag('h3', get_string('dashboard_reportperiods', 'mod_coursereadings'));

$fields = "*, (SELECT MAX(sortorder) FROM {coursereadings_reportperiod}) AS last";
$from = "{coursereadings_reportperiod}";
$where = '1=1';
$sqlparams = null;
$table = new \mod_coursereadings\table\report_periods('mod_coursereadings-reportperiods');
$table->set_attribute('id', 'coursereadings-reportperiods');
$table->define_baseurl($PAGE->url);
$table->set_sql($fields, $from, $where, $sqlparams);
$table->define_columns(array('name', 'pattern', 'moveup', 'movedown', 'edit', 'delete'));
$table->define_headers(array('Name', 'Pattern', 'Move up', 'Move down', 'Edit', 'Delete'));
$table->sortable(false, 'sortorder');
$table->collapsible(false);

$table->out(100, false); // 100 per page, no initials bar.

echo html_writer::link(new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/reportperiod.php"), get_string('reportperiod_add_new', 'mod_coursereadings'));

echo $OUTPUT->footer();


