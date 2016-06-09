<?php

require('../../config.php');
require_once('lib.php');

$courseid = required_param('id', PARAM_INT); // Course ID.
$page = optional_param('page', 0, PARAM_INT);
$config = get_config('coursereadings');
// =========================================================================
// Security checks START - teachers edit; students view.
// =========================================================================

if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('Course is misconfigured');
}

$url = new moodle_url('/mod/coursereadings/index.php', array('id' => $courseid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');

require_course_login($course, true);

$context = context_course::instance($course->id);
require_capability('mod/coursereadings:view', $context);

if (!$instances = coursereadings_course_articles($course->id)) {
    print_error('No files specified');
}
// =========================================================================
// Security checks  END.
// =========================================================================

$event = \mod_coursereadings\event\course_module_instance_list_viewed::create(array(
    'context' => $context
));
$event->trigger();

coursereadings_add_fake_block('viewall');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strmaterial  = get_string('modulename', 'coursereadings');

$PAGE->set_url('/mod/coursereadings/index.php', array('id' => $course->id));
$PAGE->set_title("$course->shortname: $strmaterials");
$PAGE->set_heading($course->fullname);
$PAGE->set_button('&nbsp;');
echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox mod-coursereadings-content');
echo $OUTPUT->box(format_text("<h2>" . $course->fullname . " $strmaterials</h2>", FORMAT_MOODLE, null, $course->id));
echo $OUTPUT->spacer(array('width'=>10, 'height'=>10));

$url = new moodle_url('/mod/coursereadings/download.php');
echo html_writer::start_tag('form', array('action'=>$url));
echo html_writer::start_tag('div', array('style'=>'display:none;'));
echo html_writer::empty_tag('input', array('type'=>"hidden", 'name'=>"id", 'value'=>$courseid));
echo html_writer::end_tag('div');

// Figure out how many pages we'll need.
$articlecount = 0;
$numpages = 1;
  foreach($instances as $instance) {
    $instcount = count($instance->articles);
    // If it's a large instance and we already have a bunch of articles on this page, add another page for it via shortcut operator.
    if (($articlecount > 10 && $instcount > 15 && $numpages++) || (($articlecount += $instcount) > 20 && $articlecount > $instcount)) {
        $numpages++;
        $articlecount = 0;
      }
}

$params = array('page' => $page, 'id' => $courseid);
$baseurl = new moodle_url('/mod/coursereadings/index.php', $params);
$pagingbar = $OUTPUT->paging_bar($numpages, $page, 1, $baseurl);
echo $pagingbar;

$articlecount = 0;
$pageno = 0;
foreach($instances as $instance) {
    $instcount = count($instance->articles);
    if (($articlecount > 10 && $instcount > 15) || (($articlecount += $instcount) > 20 && $articlecount > $instcount)) {
        $pageno++;
        $articlecount = $instcount;
    }

    if ($pageno == $page) {
        $url = new moodle_url('/mod/coursereadings/view.php', array('id'=>$instance->cmid));
        echo html_writer::link($url, "<h3>".$instance->name."</h3>");
        echo html_writer::start_tag('ul', array("class"=>"coursereadings_course_articles"));
        foreach($instance->articles as $article) {
            $author = empty($article->author)?$article->sourceauthor:$article->author;
            $year = empty($article->year)?'':" ({$article->year})";
            echo html_writer::tag('li', html_writer::checkbox("articles[]", $instance->id.','.$article->id).coursereadings_get_article_link($article, $context).html_writer::empty_tag('br').$author." ".html_writer::tag('em',$article->sourcetitle).$year);
        }
        echo html_writer::end_tag('ul');
    }

    if ($pageno > $page) {
        // We've already spat out the page we're after, so we should stop iterating over instances.
        break;
    }
}
echo $pagingbar;

echo html_writer::empty_tag('br');
echo html_writer::tag('h4', get_string('download_selected_as', 'mod_coursereadings'));
echo html_writer::empty_tag('input', array('type'=>"submit", 'name'=>"mode", 'value'=>get_string('download_as_zip', 'mod_coursereadings')));
if($config->enablecombined) {
    echo html_writer::empty_tag('br');
    echo html_writer::empty_tag('input', array('type'=>"submit", 'name'=>"mode", 'value'=>get_string('download_as_pdf', 'mod_coursereadings')));
}
echo html_writer::end_tag('form');

echo $OUTPUT->box_end();

echo $OUTPUT->footer($course);