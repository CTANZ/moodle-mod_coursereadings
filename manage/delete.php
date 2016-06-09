<?php

/**
 * Course materials dashboard - delete article/source.
 *
 * Script to delete unused articles and sources.
 *
 * @package mod_coursereadings
 * @copyright 2015 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$type     = required_param('type', PARAM_CLEAN); // Object type.
$id       = required_param('id', PARAM_INT); // Object ID.
$confirm  = optional_param('confirm', 0, PARAM_BOOL);

require_login();

$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/edit-article.php');
$PAGE->set_pagelayout('base');

switch ($type) {
    case 'article':
        if (!$object = $DB->get_record('coursereadings_article', array('id'=>$id))) {
            throw new dml_missing_record_exception('coursereadings_article');
        }
        break;
    case 'source':
        if (!$object = $DB->get_record('coursereadings_source', array('id'=>$id))) {
            throw new dml_missing_record_exception('coursereadings_source');
        }
        break;
    default:
        throw new invalid_parameter_exception();
}


$a = new stdClass();
$a->type = $type;
$a->title = $object->title;

if (!$confirm) {
    $strmaterials = get_string('modulenameplural', 'coursereadings');
    $strdashboard  = get_string('dashboard', 'coursereadings');
    $strdelete = get_string('dashboard_delete'.$type, 'coursereadings');
    $PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
    $PAGE->navbar->add($strdelete);
    $PAGE->set_title($strmaterials);
    $PAGE->set_heading($strdashboard);
    $PAGE->set_cacheable(true);
    $PAGE->set_button('&nbsp;');
    echo $OUTPUT->header();

    $continue = new moodle_url('/mod/coursereadings/manage/delete.php', array('type'=>$type, 'id'=>$id, 'confirm'=>true));
    $cancel = new moodle_url("/mod/coursereadings/manage/edit-$type.php", array('id'=>$id));
    echo $OUTPUT->confirm(get_string('confirmdelete', 'mod_coursereadings', $a), $continue, $cancel);
} else {
    require_sesskey();
    $DB->delete_records('coursereadings_'.$type, array('id'=>$id));
    if ($type === 'article') {
        $DB->delete_records('files', array('component'=>'mod_coursereadings', 'itemid'=>$id));
    }
    $url = new moodle_url("/mod/coursereadings/manage/index.php");
    redirect($url, get_string('deleted', 'mod_coursereadings', $a), 3);
}