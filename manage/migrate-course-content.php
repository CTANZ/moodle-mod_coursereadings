<?php

require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('../lib.php');

$courseid        = optional_param('id', 0, PARAM_INT);                     // Course ID.

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:migratecontent', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/index.php');
$PAGE->set_pagelayout('base');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard  = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('contentmigration', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/migrate-courses.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');
echo $OUTPUT->header();

if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('Course is misconfigured');
} else {
	// Single-course mode - find file resources containing PDFs.

	$strresources    = get_string('modulenameplural', 'resource');
	$strsectionname  = get_string('sectionname', 'format_'.$course->format);
	$strlastmodified = get_string('lastmodified');

	if (!$resources = get_all_instances_in_course('resource', $course)) {
	    notice(get_string('thereareno', 'moodle', $strresources), "$CFG->wwwroot/course/view.php?id=$course->id");
	} else {
        $PAGE->requires->yui_module('moodle-mod_coursereadings-contentmigration', 'M.mod_coursereadings.contentmigration.init');
        $PAGE->requires->strings_for_js(array('articleuploadintro', 'choosefile', 'confirmRemoveArticle', 'addarticle', 'articlesearchintro', 'noresults', 'title_of_article', 'source_type', 'source_book', 'source_journal', 'source_other', 'source_subtype', 'source_subtypes', 'furtherinfo', 'journal_notice', 'title_of_source', 'author_of_periodical', 'author_of_source', 'editor_of_source', 'year_of_publication', 'publisher', 'isbn', 'volume_number', 'edition', 'page_range', 'total_pages', 'pages'), 'mod_coursereadings');
        $PAGE->requires->strings_for_js(array('upload', 'dndenabled_inbox', 'next', 'savechanges', 'closebuttontitle'), 'moodle');
        $PAGE->requires->strings_for_js(array('addnewnote'), 'notes');

		$fs = get_file_storage();

		echo html_writer::tag('h3', html_writer::link($CFG->wwwroot.'/course/view.php?id='.$course->id, $course->shortname, array('target'=>'_blank')));
		echo html_writer::start_tag('div', array('class'=>'coursereadings_content_migration'));
		$content = html_writer::tag('div', '', array('class'=>'instance_meta'));
		$content .= html_writer::start_tag('div', array('class'=>'instance_filematch')) .
					html_writer::tag('h3', 'File match detected!') .
					html_writer::tag('div', '', array('class'=>'coursereadings-article')) .
					html_writer::tag('button', 'Use this article') .
					html_writer::end_tag('div');
		$content .= html_writer::tag('div', '', array('class'=>'instance_form'));
        $content .= html_writer::start_tag('div', array('class'=>'yui3-widget-ft')) .
                    html_writer::start_tag('span', array('class'=>'yui3-widget-buttons')) .
		            html_writer::tag('button', 'Save', array('class'=>'save_btn yui3-button', 'disabled'=>true)) .
                    html_writer::tag('button', 'Cancel', array('class'=>'cancel_btn yui3-button')) .
                    html_writer::end_tag('span') .
                    html_writer::end_tag('div');
		echo html_writer::tag('div', $content, array('class'=>'coursereadings_content_migration_form yui3-panel-content'));
		echo html_writer::start_tag('div', array('class'=>'coursereadings_content_migration_list'));
		$i = 0;
   		$strftimedate = get_string("strftimedate");
   		$users = array();
		foreach ($resources as $resource) {
    		$cm = get_coursemodule_from_instance('resource', $resource->id, $resource->course, false, MUST_EXIST);
			$context = context_module::instance($cm->id);
			$files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
			if (count($files) < 1) {
			    continue;
			} else {
			    $file = reset($files);
			    unset($files);
			}
			if (strtolower(substr($file->get_filename(), -4)) !== '.pdf') {
				// Not a PDF - we're not interested.
				continue;
			}
			if ($DB->count_records_select('coursereadings_noncopyright', 'resourceid = :resourceid AND timeflagged > :timemodified', array('resourceid'=>$resource->id, 'timemodified'=>$resource->timemodified))) {
				// Flagged as not subject to copyright license.  Skip.
				continue;
			}
			$articleid = $DB->get_field('files', 'itemid', array('contenthash'=>$file->get_contenthash(), 'component'=>'mod_coursereadings', 'filearea'=>'articles'), IGNORE_MISSING);
			if (!$articleid) {
				$articleid = -1;
			}
			$userid = $file->get_userid();
			if (!array_key_exists($userid, $users)) {
				$users[$userid] = $DB->get_record('user', array('id'=>$userid));
			}
			$uploader = $users[$userid]->firstname.' '.$users[$userid]->lastname.' ('.$users[$userid]->username.')';
			$uploader = html_writer::link($CFG->wwwroot.'/user/profile.php?id='.$userid, $uploader);
			$uploaded = userdate($file->get_timemodified(), $strftimedate);
			$path = '/'.$context->id.'/mod_resource/content/'.$resource->revision.$file->get_filepath().$file->get_filename();
    		$fullurl = moodle_url::make_file_url('/pluginfile.php', $path, false);
			$content = html_writer::tag('label', html_writer::link($CFG->wwwroot.'/mod/resource/view.php?id='.$cm->id.'&skippopup=1', $resource->name, array('target'=>'_blank')));
			$content .= html_writer::tag('div', format_module_intro('resource', $resource, $cm->id), array('class'=>'coursereadings_resource_intro'));
			$link = html_writer::link($fullurl.'?forcedownload=1', $file->get_filename());
			$content .= html_writer::tag('span', 'Uploaded by ' . $uploader . ' on ' . $uploaded . '<br />' . $link, array('class'=>'coursereadings_resource_filename'));
			$buttons = html_writer::tag('button', 'Not Copyright', array('class'=>'not_copyright'));
	   		$buttons .= html_writer::tag('button', 'Copyright', array('class'=>'is_copyright'));
	   		$numnotes = $DB->count_records('coursereadings_migrationnote', array('resourceid'=>$resource->id));
			$buttons .= html_writer::tag('button', ($numnotes?'View / ':'').'Add Notes', array('class'=>'add_notes'));
			$content .= html_writer::tag('div', $buttons, array('class'=>'migration_controls'));
			echo html_writer::tag('div', $content, array('id'=>'coursereadings_resource_instance_'.$resource->id, 'class'=>'coursereadings_resource_instance', 'data-instanceid'=>$resource->id, 'data-fileid'=>$file->get_id(), 'data-file-url'=>$fullurl, 'data-index'=>$i++, 'data-articleid'=>$articleid ));
		}
		echo html_writer::end_tag('div');
		$preview = html_writer::tag('object', '<p>It appears you do not have a PDF plugin for this browser.  No biggie... you can <a href="myfile.pdf">click here to download the PDF file.</a></p>', array('data'=>'myfile.pdf', 'type'=>'application/pdf', 'width'=>'100%', 'height'=>'100%', 'style'=>'display:none;'));
		echo html_writer::tag('div', $preview, array('class'=>'coursereadings_content_migration_preview'));
		echo html_writer::end_tag('div');
	}
}

echo $OUTPUT->footer();