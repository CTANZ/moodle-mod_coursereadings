<?php

require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('../lib.php');

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:migratecontent', $syscontext);
$showflagged = optional_param('flagged', false, PARAM_BOOL);
$days = optional_param('days', 7, PARAM_INT);

$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/migrate-new-files.php');
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

$strresources    = get_string('modulenameplural', 'resource');
$strlastmodified = get_string('lastmodified');

$timewhere = '';
if ($days !== -1) {
	$timewhere = "f.timemodified > UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL $days DAY)) AND";
}

$sql = "SELECT DISTINCT f.id AS fileid, r.id AS resourceid, r.name, r.intro, r.introformat, r.revision,
				c.id AS courseid, c.shortname,
				f.filename, f.filepath, f.contenthash, f.timemodified,
				f.userid, u.firstname, u.lastname, u.username,
					ctx.id AS contextid,
					cm.id AS cmid
		FROM  {resource} r INNER JOIN {course} c ON (r.course=c.id AND c.visible=1)
			INNER JOIN {course_modules} cm ON (cm.module=18 AND cm.instance=r.id)
			INNER JOIN {context} ctx ON (ctx.contextlevel=70 AND ctx.instanceid=cm.id)
			INNER JOIN {files} f ON (f.contextid=ctx.id AND f.mimetype = 'application/pdf' AND f.sortorder=1)
			INNER JOIN {user} u ON f.userid = u.id
						LEFT JOIN {coursereadings_migrationnote} mn ON r.id=mn.resourceid
						LEFT JOIN {coursereadings_noncopyright} nc ON (r.id=nc.resourceid AND nc.timeflagged > f.timemodified)
		WHERE $timewhere nc.id IS NULL
			AND mn.id IS ".($showflagged?'NOT ':'')."NULL
		ORDER BY c.shortname ASC, f.timemodified ASC";

$resources = $DB->get_records_sql($sql);

$PAGE->requires->yui_module('moodle-mod_coursereadings-daysselect', 'M.mod_coursereadings.daysselect.init');

$fs = get_file_storage();

echo html_writer::tag('h3', 'Migrate new content');

$dayhtml = ' modified in the past ';
$dayhtml .= '<select id="coursereadings_newfiles_days">';
$dayhtml .= '<option value="1"'.($days==1?' selected':'').'>1 day</option>';
$dayhtml .= '<option value="7"'.($days==7?' selected':'').'>7 days</option>';
$dayhtml .= '<option value="14"'.($days==14?' selected':'').'>14 days</option>';
$dayhtml .= '<option value="30"'.($days==30?' selected':'').'>30 days</option>';
$dayhtml .= '<option value="90"'.($days==90?' selected':'').'>90 days</option>';
$dayhtml .= '<option value="730"'.($days==730?' selected':'').'>2 years</option>';
$dayhtml .= '<option value="-1"'.($days==-1?' selected':'').'>[No time limit]</option>';
$dayhtml .= '</select>';

if ($showflagged) {
	echo html_writer::tag('h4', 'Showing '.count($resources).' flagged items'.$dayhtml.'.  '.html_writer::link($CFG->wwwroot.'/mod/coursereadings/manage/migrate-new-files.php'.(($days == 7)?'':'?days='.$days), 'Show unflagged items'));
} else {
	echo html_writer::tag('h4', 'Showing '.count($resources).' unflagged items'.$dayhtml.'.  '.html_writer::link($CFG->wwwroot.'/mod/coursereadings/manage/migrate-new-files.php?flagged=1'.(($days == 7)?'':'&days='.$days), 'Show flagged items'));
}

if ($resources && count($resources)) {
	// We have resources to display - initialise Content Migration JS.
	$PAGE->requires->yui_module('moodle-mod_coursereadings-contentmigration', 'M.mod_coursereadings.contentmigration.init');
	$PAGE->requires->strings_for_js(array('articleuploadintro', 'choosefile', 'confirmRemoveArticle', 'addarticle', 'articlesearchintro', 'noresults', 'title_of_article', 'source_type', 'source_book', 'source_journal', 'source_other', 'source_subtype', 'source_subtypes', 'furtherinfo', 'journal_notice', 'title_of_source', 'author_of_periodical', 'author_of_source', 'editor_of_source', 'year_of_publication', 'publisher', 'isbn', 'volume_number', 'edition', 'page_range', 'total_pages', 'pages', 'sourceurl', 'externalurl', 'doi'), 'mod_coursereadings');
	$PAGE->requires->strings_for_js(array('upload', 'dndenabled_inbox', 'next', 'savechanges', 'closebuttontitle'), 'moodle');
	$PAGE->requires->strings_for_js(array('addnewnote'), 'notes');

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
	foreach ($resources as $resource) {
		$articleid = $DB->get_field('files', 'itemid', array('contenthash'=>$resource->contenthash, 'component'=>'mod_coursereadings', 'filearea'=>'articles', 'contextid'=>$resource->contextid), IGNORE_MISSING);
		if (!$articleid) {
			$articleid = -1;
		}
		$path = '/'.$resource->contextid.'/mod_resource/content/'.$resource->revision.$resource->filepath.$resource->filename;
		$fullurl = moodle_url::make_file_url('/pluginfile.php', $path, false);
		$links = html_writer::link($CFG->wwwroot.'/mod/coursereadings/manage/migrate-course-content.php?id='.$resource->courseid, $resource->shortname, array('target'=>'_blank'));
		$links .= ': ';
		$links .= html_writer::link($CFG->wwwroot.'/mod/resource/view.php?id='.$resource->cmid, $resource->name, array('target'=>'_blank'));
		$content = html_writer::tag('label', $links);
		$content .= html_writer::tag('div', format_module_intro('resource', $resource, $resource->cmid), array('class'=>'coursereadings_resource_intro'));
		$link = html_writer::link($fullurl.'?forcedownload=1', $resource->filename);
		$uploader = html_writer::link($CFG->wwwroot.'/user/profile.php?id='.$resource->userid, $resource->firstname.' '.$resource->lastname . ' ('.$resource->username.')');
		$uploaded = userdate($resource->timemodified, $strftimedate);
		$content .= html_writer::tag('span', 'Uploaded by ' . $uploader . ' on ' . $uploaded . '<br />' . $link, array('class'=>'coursereadings_resource_filename'));
		$buttons = html_writer::tag('button', 'Not Copyright', array('class'=>'not_copyright'));
   		$buttons .= html_writer::tag('button', 'Copyright', array('class'=>'is_copyright'));
   		$numnotes = $DB->count_records('coursereadings_migrationnote', array('resourceid'=>$resource->resourceid));
		$buttons .= html_writer::tag('button', ($numnotes?'View / ':'').'Add Notes', array('class'=>'add_notes'));
		$content .= html_writer::tag('div', $buttons, array('class'=>'migration_controls'));
		echo html_writer::tag('div', $content, array('id'=>'coursereadings_resource_instance_'.$resource->resourceid, 'class'=>'coursereadings_resource_instance', 'data-instanceid'=>$resource->resourceid, 'data-fileid'=>$resource->fileid, 'data-file-url'=>$fullurl, 'data-index'=>$i++, 'data-articleid'=>$articleid ));
	}
	echo html_writer::end_tag('div');
	$preview = html_writer::tag('object', '<p>It appears you do not have a PDF plugin for this browser.  No biggie... you can <a href="myfile.pdf">click here to download the PDF file.</a></p>', array('data'=>'myfile.pdf', 'type'=>'application/pdf', 'width'=>'100%', 'height'=>'100%', 'style'=>'display:none;'));
	echo html_writer::tag('div', $preview, array('class'=>'coursereadings_content_migration_preview'));
	echo html_writer::end_tag('div');
} else {
	echo html_writer::tag('p', 'No matching files could be found.  Please use the options above to adjust your filters and try again.');
}

echo $OUTPUT->footer();