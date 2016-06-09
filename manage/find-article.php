<?php

/**
 * Course materials dashboard - edit article.
 *
 * Tool for editing articles.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('./find-article-form.php');

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/find-article.php');
$PAGE->set_pagelayout('base');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard  = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('dashboard_findarticle', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/find-article.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');
echo $OUTPUT->header();


$currenttab = 'findarticle';
include('./managetabs.php');

$errorstr                   = get_string('error');
$stryes                     = get_string('yes');
$strno                      = get_string('no');
$stryesnooptions = array(0=>$strno, 1=>$stryes);

$returnurl = new moodle_url('/mod/coursereadings/manage/find-article.php');

// Form should always be shown - it shouldn't be submitted.  YUI module displays search results.
$mform = new coursereadings_findarticle_form();
$mform->display();



$PAGE->requires->yui_module('moodle-mod_coursereadings-findarticle', 'M.mod_coursereadings.findarticle.init');
echo $OUTPUT->footer();