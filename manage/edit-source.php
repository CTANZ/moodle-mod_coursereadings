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
require_once('./source-form.php');

$id       = required_param('id', PARAM_INT); // Source ID

if (!$source = $DB->get_record('coursereadings_source', array('id'=>$id))) {
    print_error('invalidaccessparameter');
}

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/edit-article.php');
$PAGE->set_pagelayout('base');

// Check for form cancellation early, so we can redirect without an interstitial page.
$articleshtml = '';
$articles = $DB->get_records('coursereadings_article', array('source'=>$id), 'id, title, pagerange, totalpages');
foreach ($articles as $article) {
    $total = empty($article->totalpages)?'':" [{$article->totalpages} pages]";
    $articleshtml .= '<li><strong><a href="'.$CFG->wwwroot.'/mod/coursereadings/manage/edit-article.php?id='.$article->id.'">'.$article->title.'</a></strong> '.$article->pagerange.$total.'</li>';
}
if (strlen($articleshtml)) {
    $articleshtml = html_writer::tag('ul', $articleshtml);
} else {
    $articleshtml = '<strong>No articles are currently associated with this source.</strong>  <a href="'.$CFG->wwwroot.'/mod/coursereadings/manage/delete.php?type=source&id='.$id.'">Delete source</a>';
}
$customdata = array('articles'=>$articleshtml);
$mform = new coursereadings_editsource_form(null, $customdata);
$returnurl = new moodle_url('/mod/coursereadings/manage/find-source.php');
if ($mform->is_cancelled()) {
    redirect($returnurl);
    exit;
}

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard  = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('dashboard_findsource', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/find-source.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');
echo $OUTPUT->header();

$currenttab = 'findsource';
include('./managetabs.php');

if ($formdata = $mform->get_data()) {
    $formdata->modifiedby = $USER->id;
    $DB->update_record('coursereadings_source', $formdata);
    redirect($returnurl, "Source updated successfully!", 3);
} else {
    $mform->set_data($source);
    $mform->display();
    echo $OUTPUT->footer();
    die;
}