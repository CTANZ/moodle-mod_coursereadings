<?php

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/mod/coursereadings/lib.php');
require_once($CFG->dirroot.'/course/lib.php');

$type = required_param('t', PARAM_TEXT);

// Require valid sesskey and relevant capability.
require_sesskey();
$syscontext = context_system::instance();

switch ($type) {
    case 'article':
    case 'addarticle':
    case 'notcopyright':
    case 'migrate':
    case 'migrationnotes':
    case 'addmigrationnote':
        // Migration-related action.
        require_capability('mod/coursereadings:migratecontent', $syscontext);
        break;

    case 'pagecount':
    case 'source':
    case 'breach':
    case 'approveitem':
    case 'editsource':
    case 'editarticle':
    case 'flag':
    case 'delete':
    case 'deletearticle':
    case 'approvebreach':
    case 'breachnotes':
    case 'addbreachnote':
        // Non-migration-related action.
        require_capability('mod/coursereadings:managesite', $syscontext);
        break;
}


$results = array();
switch ($type) {
    case 'article':
        $query = required_param('q', PARAM_TEXT);
        if ($article = $DB->get_record_sql('SELECT a.id, a.title, a.pagerange, a.totalpages, a.author AS periodicalAuthor, a.externalurl, a.doi, s.id AS source, s.title AS sourcetitle, s.author AS sourceauthor, s.editor, s.year FROM {coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source = s.id WHERE a.id=:articleid', array('articleid'=>$query))) {
            $results = $article;
            $results->usage = $DB->count_records('coursereadings_inst_article', array('articleid'=>$article->id));
            $results->error = 0;
            $results->link = coursereadings_get_article_link($results, context_system::instance());
        } else {
            $results->error = "Couldn't retrieve article from database.  Has it been deleted?  Please refresh the page and try again.";
        }
        break;
    case 'pagecount':
        // Set article page count.
        $pagecount = required_param('scannedpages', PARAM_INT);
        if ($pagecount < 1) {
            $results['error'] = "Please enter a positive number.";
        } elseif ($article = $DB->get_record('coursereadings_article', array('id'=>required_param('articleid', PARAM_INT)))) {
            $article->totalpages = $pagecount;
            $DB->update_record('coursereadings_article', $article);
            $results['id'] = $article->id;
            $results['error'] = 0;
        } else {
            $results['error'] = "Couldn't retrieve article from database.  Has it been deleted?  Please refresh the page and try again.";
        }
        break;
    case 'source':
        $query = required_param('q', PARAM_TEXT);
        if ($source = $DB->get_record('coursereadings_source', array('id'=>$query))) {
            $results = $source;
            $results->usage = $DB->count_records('coursereadings_article', array('source'=>$source->id));
            $results->error = 0;
            if (empty($results->furtherinfo)) {
                $results->furtherinfo = '';
            }
        } else {
            $results->error = "Couldn't retrieve source from database.  Has it been deleted?  Please refresh the page and try again.";
        }
        break;
    case 'breach':
        $courseid = required_param('c', PARAM_INT);
        $sourceid = required_param('s', PARAM_INT);
        if ($source = $DB->get_record('coursereadings_source', array('id'=>$sourceid))) {
            $results = new stdClass;
            $results->source = $source;
            $results->course = $DB->get_record('course', array('id'=>$courseid), 'id, shortname, fullname');
            $results->articles = $DB->get_records_sql(' SELECT a.*, i.id AS instance, ca.id AS approvalid, ca.withinlimits
                                                        FROM {coursereadings_article} a
                                                            INNER JOIN ({coursereadings_inst_article} ia INNER JOIN {coursereadings} i ON i.id=ia.instanceid) ON a.id=ia.articleid
                                                            LEFT JOIN ({coursereadings_approval} ca INNER JOIN {coursereadings_appr_article} caa ON ca.id=caa.approvalid) ON ca.courseid=i.course AND caa.articleid=a.id
                                                        WHERE i.course = :courseid
                                                            AND a.source = :sourceid
                                                        ', array('courseid'=>$courseid, 'sourceid'=>$sourceid));
            foreach ($results->articles as $article) {
                $article->link = coursereadings_get_article_link($article, context_system::instance());
                $article->approved = empty($article->approvalid) ? false : true;
                $article->withinlimits = empty($article->withinlimits) ? false : true;
            }
            $results->error = 0;
        } else {
            $results->error = "Couldn't retrieve information from database.  Has something been deleted?  Please refresh the page and try again.";
        }
        break;
    case 'notcopyright':
        $resourceid = required_param('q', PARAM_INT);
        $flag = new stdClass();
        $flag->resourceid = $resourceid;
        $flag->timeflagged = time();
        $flag->flaggedby = $USER->id;
        $DB->insert_record('coursereadings_noncopyright', $flag);
        $results['error'] = 0;
        break;
    case 'migrate':
        $articleid = required_param('q', PARAM_INT);
        $resourceid = required_param('r', PARAM_INT);
        $resource = $DB->get_record('resource', array('id'=>$resourceid));
        $moduleid = $DB->get_field('modules', 'id', array('name'=>'coursereadings'));
        $oldcm = get_coursemodule_from_instance('resource', $resource->id, $resource->course, false, MUST_EXIST);
        $mod = new stdClass;
        $mod->course = $resource->course;
        $mod->name = $resource->name;
        $mod->intro = $resource->intro;
        $mod->introformat = $resource->introformat;
        $mod->timecreated = time();
        $mod->timemodified = $mod->timecreated;
        $mod->folder = $mod->course; // Not actually used any more, but field is NOT NULL.
        if(!$mod->id = $DB->insert_record('coursereadings', $mod)) {
            $results['error'] = 'Unable to create the module instance.';
            break;
        }

        $article = new stdClass();
        $article->instanceid = $mod->id;
        $article->articleid = intval($articleid);
        if (!$DB->insert_record('coursereadings_inst_article', $article)) {
            $results['error'] = "Couldn't attach article to module instance.";
            break;
        }

        $cm = new stdClass();
        $cm->course = $resource->course;
        $cm->section = $oldcm->section; // Will be updated by course_add_cm_to_section() below.
        $cm->module = $moduleid;
        $cm->modulename = 'coursereadings';
        $cm->instance = $mod->id;
        $cm->visible = $oldcm->visible;
        $cm->groupmode = $oldcm->groupmode;
        $cm->groupingid = $oldcm->groupingid;
        $cm->completion = $oldcm->completion;
        $cm->added = time();
        if (!$cm->id = $DB->insert_record("course_modules", $cm)) {
            $results['error'] = 'Unable to create the course module.';
            break;
        }

        // Add module to section, placing it before old one.
        $section = $DB->get_record("course_sections", array('id'=>$oldcm->section));
        course_add_cm_to_section($resource->course, $cm->id, $section->section, $oldcm->id);

        // Delete old module.
        course_delete_module($oldcm->id);

        $results['error'] = 0;
        break;
    case 'migrationnotes':
        $id = required_param('q', PARAM_INT);
        $notes = $DB->get_records_sql('SELECT mn.id, notes, mn.timecreated, firstname, lastname FROM {coursereadings_migrationnote} mn INNER JOIN {user} u ON mn.userid=u.id WHERE resourceid=:resourceid', array('resourceid'=>$id));
        $results['html'] = '';
        foreach ($notes as $recordid=>$record) {
            $results['html'] .= html_writer::start_tag('div') .
                                html_writer::tag('h4', fullname($record) . ', ' . userdate($record->timecreated) . ':') .
                                html_writer::tag('pre', $record->notes) .
                                html_writer::end_tag('div');
        }
        $results['error'] = 0;
        break;
    case 'addmigrationnote':
        $id = required_param('q', PARAM_INT);
        $note = new stdClass();
        $note->resourceid = $id;
        $note->notes = required_param('notes', PARAM_TEXT);
        $note->timecreated = time();
        $note->userid = $USER->id;
        $DB->insert_record('coursereadings_migrationnote', $note);
        $results['error'] = 0;
        break;
    case 'addarticle':
        $fileid = required_param('fileid', PARAM_TEXT);

        // Add source if not already in database.
        $sourceid = optional_param('sourceid', 0, PARAM_INT);
        if (empty($sourceid)) {
            $source = new stdClass();
            $source->type = required_param('source_type', PARAM_TEXT);
            $source->title = required_param('title_of_source', PARAM_TEXT);
            $source->author = optional_param('author_of_source', '', PARAM_TEXT);
            $source->year = required_param('year_of_publication', PARAM_TEXT);
            $source->isbn = required_param('isbn', PARAM_TEXT);
            $source->pages = optional_param('pages', 0, PARAM_INT);
            $source->publisher = optional_param('publisher', '', PARAM_TEXT);
            $source->editor = optional_param('editor_of_source', '', PARAM_TEXT);
            $source->volume = optional_param('volume_number', '', PARAM_TEXT);
            $source->edition = optional_param('edition', '', PARAM_TEXT);
            $source->createdby = $USER->id;

            $sourceid = $DB->insert_record('coursereadings_source', $source);
        }

        // Add article if not already in database.
        $articleid = optional_param('articleid', 0, PARAM_INT);
        if (empty($articleid)) {
            $article = new stdClass();
            $article->title = required_param('title_of_article', PARAM_TEXT);
            $article->author = optional_param('author_of_periodical', '', PARAM_TEXT);
            $article->pagerange = optional_param('page_range', '', PARAM_TEXT);
            $article->totalpages = optional_param('total_pages', null, PARAM_TEXT);
            $article->externalurl = optional_param('externalurl', null, PARAM_URL);
            $article->doi = optional_param('doi', null, PARAM_TEXT);
            $article->source = $sourceid;
            $article->createdby = $USER->id;

            $articleid = $DB->insert_record('coursereadings_article', $article);
            coursereadings_add_to_queue('article', $articleid);
        }

        // Copy file into mod_coursereadings filearea.
        $fs = get_file_storage();
        $filerecord = new stdClass();
        $context = context_system::instance();
        $filerecord->contextid = $context->id;
        $filerecord->component = 'mod_coursereadings';
        $filerecord->filearea = 'articles';
        $filerecord->itemid = $articleid;
        $filerecord->filepath = '/';
        $fs->create_file_from_storedfile($filerecord, $fileid);

        $results['articleid'] = $articleid;
        $results['error'] = 0;
        break;
    case 'approveitem':
        $id = required_param('q', PARAM_INT);
        $DB->delete_records('coursereadings_queue', array('id'=>$id));
        $results['error'] = 0;
        break;
    case 'editsource':
        $queueid = required_param('q', PARAM_INT);

        // Update source details.
        $source = new stdClass();
        $source->id = required_param('sourceid', PARAM_INT);
        $source->type = required_param('source_type', PARAM_TEXT);
        $source->title = required_param('title_of_source', PARAM_TEXT);
        $source->author = optional_param('author_of_source', '', PARAM_TEXT);
        $source->year = required_param('year_of_publication', PARAM_TEXT);
        $source->isbn = required_param('isbn', PARAM_TEXT);
        $source->pages = optional_param('pages', 0, PARAM_INT);
        $source->volume = optional_param('volume_number', '', PARAM_TEXT);
        $source->editor = optional_param('editor_of_source', '', PARAM_TEXT);
        $source->edition = optional_param('edition', '', PARAM_TEXT);
        $source->publisher = optional_param('publisher', '', PARAM_TEXT);
        $DB->update_record('coursereadings_source', $source);

        // Remove source from "new sources" queue.
        $DB->delete_records('coursereadings_queue', array('id'=>$queueid));

        $results['error'] = 0;
        break;
    case 'editarticle':
        $queueid = optional_param('q', -1, PARAM_INT);

        // Update article details.
        $article = new stdClass();
        $article->id = required_param('articleid', PARAM_INT);
        $article->title = required_param('title', PARAM_TEXT);
        $article->author = optional_param('author', '', PARAM_TEXT);
        $article->pagerange = optional_param('page_range', '', PARAM_TEXT);
        $article->totalpages = optional_param('total_pages', null, PARAM_TEXT);
        $article->externalurl = optional_param('externalurl', null, PARAM_URL);
        $article->doi = optional_param('doi', null, PARAM_TEXT);
        $article->source = required_param('source', PARAM_INT);

        $DB->update_record('coursereadings_article', $article);

        if ($queueid > -1) {
            // Remove article from "new articles" queue.
            $DB->delete_records('coursereadings_queue', array('id'=>$queueid));
        }

        $results['articleid'] = $article->id;
        $results['sourceid'] = $article->source;
        $results['error'] = 0;
        break;
    case 'flag':
        $id = required_param('q', PARAM_INT);
        $item = new stdClass();
        $item->id = $id;
        $item->notes = optional_param('notes', '', PARAM_TEXT);
        $DB->update_record('coursereadings_queue', $item);
        $results['notes'] = $item->notes;
        $results['error'] = 0;
        break;
    case 'delete':
        $queueid = required_param('q', PARAM_INT);
        $queueitem = $DB->get_record('coursereadings_queue', array('id'=>$queueid), '*', MUST_EXIST);
        $DB->delete_records('coursereadings_'.$queueitem->type, array('id'=>$queueitem->objectid));
        $DB->delete_records('coursereadings_queue', array('id'=>$queueid));
        $results['type'] = $queueitem->type;
        $results['objid'] = $queueitem->objectid;

        $results['error'] = 0;
        break;
    case 'deletearticle':
        $articleid = required_param('q', PARAM_INT);

        $DB->delete_records('coursereadings_article', array('id'=>$articleid));

        $results['error'] = 0;
        break;
    case 'approvebreach':
        $approval = new stdClass();
        $approval->courseid = required_param('c', PARAM_INT);
        $approval->sourceid = required_param('s', PARAM_INT);
        $approval->withinlimits = optional_param('w', false, PARAM_BOOL);
        $approval->blanketapproval = optional_param('b', false, PARAM_BOOL);
        $approval->notes = optional_param('notes', '', PARAM_TEXT);
        $approvalid = optional_param('id', 0, PARAM_INT);
        if ($approvalid) {
            $approval->id = $approvalid;
            $DB->update_record('coursereadings_approval', $approval);
        } else {
            $approval->id = $DB->insert_record('coursereadings_approval', $approval);
        }
        $articles = required_param_array('a', PARAM_INT);
        foreach ($articles as $article) {
            $data = new stdClass();
            $data->approvalid = $approval->id;
            $data->articleid = $article;
            $DB->insert_record('coursereadings_appr_article', $data);
        }
        $results['error'] = 0;
        break;
    case 'breachnotes':
        $courseid = required_param('c', PARAM_INT);
        $sourceid = required_param('s', PARAM_INT);
        $notes = $DB->get_records_sql('SELECT n.id, notes, n.timecreated, firstname, lastname FROM {coursereadings_breach_note} n INNER JOIN {user} u ON n.userid=u.id WHERE courseid=:courseid AND sourceid=:sourceid', array('courseid'=>$courseid,'sourceid'=>$sourceid));
        $results['html'] = '';
        foreach ($notes as $recordid=>$record) {
            $results['html'] .= html_writer::start_tag('div') .
                                html_writer::tag('h4', fullname($record) . ', ' . userdate($record->timecreated) . ':') .
                                html_writer::tag('pre', $record->notes) .
                                html_writer::end_tag('div');
        }
        $results['error'] = 0;
        break;
    case 'addbreachnote':
        $courseid = required_param('c', PARAM_INT);
        $sourceid = required_param('s', PARAM_INT);
        $note = new stdClass();
        $note->courseid = $courseid;
        $note->sourceid = $sourceid;
        $note->notes = required_param('notes', PARAM_TEXT);
        $note->timecreated = time();
        $note->userid = $USER->id;
        $DB->insert_record('coursereadings_breach_note', $note);
        $results['error'] = 0;
        break;
    default:
        $results[] = 'Error - incorrect type specified';
}

header('Content-type: application/json');
echo json_encode($results);
exit;