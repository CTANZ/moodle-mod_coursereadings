<?php

require('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/completionlib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$edit = optional_param('edit', -1, PARAM_BOOL); // Edit mode.
$config = get_config('coursereadings');

// =========================================================================
// Security checks START - teachers edit; students view.
// =========================================================================
if (!$cm = get_coursemodule_from_id('coursereadings', $id)) {
    print_error('Course Module ID was incorrect');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('Course is misconfigured');
}

if (!$coursereadings = $DB->get_record('coursereadings', array('id' => $cm->instance))) {
    print_error('Course module is incorrect');
}

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/coursereadings:view', $context);

if (!($articles = $DB->get_records('coursereadings_inst_article', array('instanceid' => $cm->instance), 'id ASC'))) {
    print_error('No files specified');
}

// Add fake block early, if we have new-style articles associated with the instance.
if (sizeof($articles)) {
    coursereadings_add_fake_block();
}

$allowedit = has_capability('mod/coursereadings:edit', $context);

if ($allowedit) {
    if ($edit != -1) {
        $USER->editing = $edit;
    } else {
        if (isset($USER->editing)) {
            $edit = $USER->editing;
        } else {
            $edit = 0;
        }
    }
} else {
    $edit = 0;
}

// =========================================================================
// Security checks  END.
// =========================================================================

$event = \mod_coursereadings\event\course_module_viewed::create(array(
    'objectid' => $coursereadings->id,
    'context' => $context
));
$event->trigger();

// Update 'viewed' state if required by completion system.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strmaterial  = get_string('modulename', 'coursereadings');

$buttons = $allowedit ? '<table cellspacing="0" cellpadding="0"><tr><td>'.update_module_button($cm->id, $course->id, $strmaterial).'</td></tr></table>' : '&nbsp;';

$PAGE->set_url('/mod/coursereadings/view.php', array('id' => $coursereadings->id));
$PAGE->set_title("$course->shortname: $coursereadings->name");
$PAGE->set_heading($course->fullname);
$PAGE->set_button($buttons);
echo $OUTPUT->header();

$OUTPUT->box_start("center", "70%", "", '0', 'generalbox mod-coursereadings-content');

$formatoptions = new object();
$formatoptions->noclean = true;
$formatoptions->para = false; // MDL-12061, <p> in html editor breaks xhtml strict.

if (trim(strip_tags($coursereadings->name))) {
    echo '<h2 style="margin-top:0;">'.trim(strip_tags($coursereadings->name))."</h2>";
}

if (trim(strip_tags($coursereadings->intro))) {
    echo $OUTPUT->box(format_text($coursereadings->intro, $coursereadings->introformat, $formatoptions, $course->id), "center");
    echo $OUTPUT->spacer(array('width'=>10, 'height'=>10));
}

if (count($articles)) {
    echo "<ul>";
    foreach($articles as $article) {
        $details = $DB->get_record_sql('SELECT a.*, a.id AS articleid, s.title AS sourcetitle, s.author AS sourceAuthor, s.year FROM {coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source = s.id WHERE a.id = :articleid', array('articleid'=>$article->articleid));
        if ($details) {
            $author = empty($details->author) ? $details->sourceauthor : $details->author;
            $year = empty($details->year) ? '' : " ({$details->year})";
            echo "<li>".coursereadings_get_article_link($details, $context)."<br>$author <em>$details->sourcetitle</em>$year</li>";
        }
    }
    echo "</ul>";
}

if (count($articles) > 1) {
    echo html_writer::tag('h4', get_string('download_instance_as', 'mod_coursereadings'));
    $zipstr = get_string('download_as_zip', 'mod_coursereadings');
    $zipurl = new moodle_url('/mod/coursereadings/download.php', array('id' => $course->id, 'cm' => $cm->id, 'mode' => $zipstr));
    echo $OUTPUT->single_button($zipurl, $zipstr, 'get');
    if($config->enablecombined) {
        $pdfstr = get_string('download_as_pdf', 'mod_coursereadings');
        $pdfurl = new moodle_url('/mod/coursereadings/download.php', array('id' => $course->id, 'cm' => $cm->id, 'mode' => $pdfstr));
        echo $OUTPUT->single_button($pdfurl, $pdfstr, 'get');
    }
}

$OUTPUT->box_end();

echo $OUTPUT->footer($course);