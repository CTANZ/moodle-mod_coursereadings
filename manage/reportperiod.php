<?php

/**
 * Course materials dashboard - add/edit report period.
 *
 * Tool for adding/editing reporting periods.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('./reportperiod-form.php');

$id = optional_param('id', 0, PARAM_INT); // Report period ID

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managereportperiods', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/reportperiod.php');
$PAGE->set_pagelayout('base');

// Check for form cancellation early, so we can redirect without an interstitial page.
$mform = new coursereadings_reportperiod_form();
$returnurl = new moodle_url('/mod/coursereadings/manage/reportperiods.php');
if ($mform->is_cancelled()) {
    redirect($returnurl);
    exit;
}

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard  = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('dashboard_reportperiod', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/reportperiod.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');
echo $OUTPUT->header();

$currenttab = 'reportperiods';
include('./managetabs.php');

if ($formdata = $mform->get_data()) {
    $data = new stdClass();
    $data->name = $formdata->name;
    $data->pattern = $formdata->pattern;

    if(empty($formdata->id)) {
        // New record - calculate sort order and insert.
        $sortorder = $DB->count_records('coursereadings_reportperiod');
        $data->sortorder = $sortorder;
        $data->id = $DB->insert_record('coursereadings_reportperiod', $data);
    } else {
        $data->id = $formdata->id;
        $data->sortorder = $formdata->sortorder;
        $DB->update_record('coursereadings_reportperiod', $data);
    }
    redirect($returnurl, "Reporting period saved successfully!", 3);
} else {
    if (!empty($id)) {
        if ($data = $DB->get_record('coursereadings_reportperiod', array('id' => $id))) {
            $mform->set_data($data);
        }
    }
    $mform->display();
    echo $OUTPUT->footer();
    die;
}