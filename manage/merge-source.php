<?php

/**
 * Course materials dashboard - edit source.
 *
 * Tool for editing sources.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('./merge-source-forms.php');

$duplicate = required_param('id', PARAM_INT);
$canonical = optional_param('target', 0,  PARAM_INT);
$returnaction = optional_param('return', 'dashboard', PARAM_ALPHANUM);

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/merge-source.php', array('id'=>$duplicate));
$PAGE->set_pagelayout('base');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard  = get_string('dashboard', 'coursereadings');
$strmergesource = get_string('dashboard_mergesource', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add($strmergesource);
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strmergesource);
$PAGE->set_cacheable(false);
$PAGE->set_button('&nbsp;');

switch($returnaction) {
    case 'find':
        $returnurl = new moodle_url("/mod/coursereadings/manage/find-source.php");
        break;

    case 'flagged':
        $returnurl = new moodle_url("/mod/coursereadings/manage/flagged-sources.php");
        break;

    case 'dashboard':
    default:
        $returnurl = new moodle_url("/mod/coursereadings/manage/index.php");
        break;
}

if (empty($canonical)) {
    // No canonical source provided - display form to search for one.
    echo $OUTPUT->header();
    $currenttab = 'findsource';
    include('./managetabs.php');

    $data = $DB->get_record('coursereadings_source', array('id'=>$duplicate));
    // Field 'isbn' is used in search, so we need a different name.
    $data->static_isbn = $data->isbn;
    unset($data->isbn);
    $data->return = $returnaction;

    $searchform = new coursereadings_mergesource_search_form();
    $searchform->set_data($data);
    $searchform->display();

    $PAGE->requires->yui_module('moodle-mod_coursereadings-mergesource', 'M.mod_coursereadings.mergesource.init');
} else {
    // Duplicate and canonical sources have been provided, allow an opportunity to edit canonical source details and perform merge.

    $mergeform = new coursereadings_mergesource_merge_form();

    if ($mergeform->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $mergeform->get_data()) {
        // Update canonical source's details.
        $source = new stdClass();
        $source->id = $data->target;
        $source->type = $data->type;
        $source->title = $data->title;
        $source->author = $data->author;
        $source->editor = $data->editor;
        $source->year = $data->year;
        $source->volume = $data->volume;
        $source->edition = $data->edition;
        $source->publisher = $data->publisher;
        $source->isbn = $data->isbn;
        $source->pages = $data->pages;
        $source->subtype = $data->subtype;
        $source->furtherinfo = $data->furtherinfo;
        $source->externalurl = $data->externalurl;
        $source->modifiedby = $USER->id;
        $DB->update_record('coursereadings_source', $source);

        // Update articles to point to canonical source.
        $DB->set_field('coursereadings_article', 'source', $canonical, array('source'=>$duplicate));

        // Remove duplicate source from "new sources" queue, if present.
        $DB->delete_records('coursereadings_queue', array('type'=>'source', 'objectid'=>$duplicate));

        // Remove duplicate source.
        $DB->delete_records('coursereadings_source', array('id'=>$duplicate));

        redirect($returnurl, get_string('dashboard_mergesource_complete', 'mod_coursereadings'));
    } else {
        // Display merge form.
        echo $OUTPUT->header();
        $currenttab = 'findsource';
        include('./managetabs.php');

        // Default data - both sources, but duplicate needs to be prefixed.
        $data = $DB->get_record('coursereadings_source', array('id'=>$canonical));
        $duplicatedata = $DB->get_record('coursereadings_source', array('id'=>$duplicate));
        foreach ($duplicatedata as $key => $value) {
            $data->{'static_'.$key} = $value;
        }
        // Source IDs should be in the same fields as in previous steps.
        $data->id = $duplicate;
        $data->target = $canonical;
        $data->return = $returnaction;

        $mergeform->set_data($data);

        $mergeform->display();
    }
}


echo $OUTPUT->footer();