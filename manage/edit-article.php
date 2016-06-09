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
require_once('./article-form.php');

$id       = required_param('id', PARAM_INT); // Article ID

if (!$article = $DB->get_record('coursereadings_article', array('id'=>$id))) {
    print_error('invalidaccessparameter');
}
if (!$source = $DB->get_record('coursereadings_source', array('id'=>$article->source), 'title, year')) {
    print_error('invalidaccessparameter');
}

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/edit-article.php');
$PAGE->set_pagelayout('base');

$moduleid = $DB->get_field('modules', 'id', array('name'=>'coursereadings'));
$instanceshtml = '';
$instances = $DB->get_records_sql(' SELECT DISTINCT cm.id, c.id AS course, c.shortname, i.name AS title
                                    FROM (({coursereadings} i INNER JOIN {course_modules} cm ON i.id=cm.instance AND cm.module=:modid)
                                            INNER JOIN {course} c ON i.course=c.id)
                                            INNER JOIN {coursereadings_inst_article} ia ON ia.instanceid=i.id
                                    WHERE ia.articleid=:article', array('article'=>$id, 'modid'=>$moduleid));
foreach ($instances as $instance) {
    $instanceshtml .= '<li><strong><a href="'.$CFG->wwwroot.'/course/view.php?id='.$instance->course.'">'.$instance->shortname.'</a>:</strong> <a href="'.$CFG->wwwroot.'/mod/coursereadings/view.php?id='.$instance->id.'"> '.$instance->title.'</a></li>';
}
if (strlen($instanceshtml)) {
    $instanceshtml = html_writer::tag('ul', $instanceshtml);
} else {
    $instanceshtml = '<strong>No instances are currently using this article.</strong>  <a href="'.$CFG->wwwroot.'/mod/coursereadings/manage/delete.php?type=article&id='.$id.'">Delete article</a>';
}
$customdata = array('instances'=>$instanceshtml);

// Check for form cancellation early, so we can redirect without an interstitial page.
$mform = new coursereadings_editarticle_form(null, $customdata);
$returnurl = new moodle_url('/mod/coursereadings/manage/find-article.php');
if ($mform->is_cancelled()) {
    redirect($returnurl);
    exit;
}

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

if ($formdata = $mform->get_data()) {
    $data = new stdClass();
    $data->id = $formdata->id;
    $data->title = $formdata->title;
    $data->pagerange = $formdata->pagerange;
    $data->totalpages = empty($formdata->totalpages) ? null : $formdata->totalpages;
    $data->author = $formdata->periodicalAuthor;
    $data->externalurl = $formdata->externalurl;
    $data->doi = $formdata->doi;
    $data->source = $formdata->source;
    $data->modifiedby = $USER->id;
    $DB->update_record('coursereadings_article', $data);
    $tempname = $mform->save_temp_file('newfile');
    $filename = $mform->get_new_filename('newfile');
    if (!empty($tempname) && !empty($filename)) {
        // New file submitted - overwrite existing file

        $fs = get_file_storage();
        $oldfiles = $fs->get_area_files($syscontext->id, 'mod_coursereadings', 'articles', $id);

        $file = new stdClass();

        $file->contextid = $syscontext->id;
        $file->component = 'mod_coursereadings';
        $file->filearea = 'articles';
        $file->itemid = $id;
        $file->filepath = '/';
        $file->filename = $filename;

        $newfile = $fs->create_file_from_pathname($file, $tempname);
        if (!empty($newfile) && count($oldfiles)) {
            foreach ($oldfiles as $hash=>$oldfile) {
                $oldfile->delete();
            }
        }
    }
    redirect($returnurl, "Article updated successfully!", 3);
} else {
    $article->sourcedisplay = $source->title . ' (' . $source->year . ')';
    $article->periodicalAuthor = $article->author;
    $mform->set_data($article);
    $mform->display();
    $PAGE->requires->yui_module('moodle-mod_coursereadings-editarticle', 'M.mod_coursereadings.editarticle.init');
    echo $OUTPUT->footer();
    die;
}