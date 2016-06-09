<?php

/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */

function coursereadings_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

function coursereadings_add_instance($coursereadings) {
    global $DB;
    // Given an object containing all the necessary data,
    // (defined by the form in mod.html) this function
    // will create a new instance and return the id number
    // of the new instance.

    $coursereadings->timecreated = time();
    $coursereadings->timemodified = $coursereadings->timecreated;
    $coursereadings->folder = $coursereadings->course;

    if(! $coursereadings->id = $DB->insert_record('coursereadings', $coursereadings)) {
        return false;
    }

    if(!coursereadings_process_form($coursereadings)) return false;

    return $coursereadings->id;
}

function coursereadings_add_ajax_instance($coursereadings, $articleid, $draftitemid) {
    global $DB;
    // Given an object containing all the necessary data,
    // (defined by the form in mod.html) this function
    // will create a new instance and return the id number
    // of the new instance.

    $coursereadings->timecreated = time();
    $coursereadings->timemodified = $coursereadings->timecreated;
    $coursereadings->folder = $coursereadings->course;

    if(! $coursereadings->id = $DB->insert_record('coursereadings', $coursereadings)) {
        return false;
    }

    $data = new stdClass;
    $data->instanceid = $coursereadings->id;
    $data->articleid = $articleid;

    if(! $data->id = $DB->insert_record('coursereadings_inst_article', $data)) {
        return false;
    }

    if (!empty($draftitemid)) {
        coursereadings_save_file($data, $draftitemid);
    }

    return $coursereadings->id;
}

function coursereadings_process_form($form) {
    global $DB;

    $articleIds = explode(',', $form->articles);
    $DB->delete_records('coursereadings_inst_article', array('instanceid'=>$form->id));
    for($i=0;$i<count($articleIds);$i++) {
        $article = new stdClass();
        $article->instanceid=$form->id;
        $article->articleid=$articleIds[$i];
        if(! $DB->insert_record('coursereadings_inst_article', $article)) {
            return false;
        }
    }
    return true;
}

function coursereadings_update_instance($coursereadings) {
    global $DB;
    // Given an object containing all the necessary data,
    // (defined by the form in mod.html) this function
    // will update an existing instance with new data.

    $coursereadings->timemodified = time();
    $coursereadings->id = $coursereadings->instance;

    if(!coursereadings_process_form($coursereadings)) return false;

    return $DB->update_record('coursereadings', $coursereadings);
}

function coursereadings_delete_instance($id) {
    global $DB;
    // Given an ID of an instance of this module,
    // this function will permanently delete the instance
    // and any data that depends on it.

    if (! $coursereadings = $DB->get_record('coursereadings', array('id'=>$id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records('coursereadings_inst_article', array('instanceid'=>$coursereadings->id))) {
        $result = false;
    }

    if($result) {
        if (! $DB->delete_records('coursereadings', array('id'=>$coursereadings->id))) {
            $result = false;
        }
    }

    return $result;
}

/**
 * Add the Course Materials fake block to the first region available
 */
function coursereadings_add_fake_block($active='') {
    global $OUTPUT, $PAGE;

    $bc = new block_contents();
    $bc->title = get_string('modulenameplural', 'mod_coursereadings');
    $bc->attributes['class'] = 'block block_coursereadings_fakeblock';
    $bc->content = '';

    $contents = array();

    if ($active == 'viewall') {
        $contents[] = html_writer::tag('li', get_string('viewcoursematerial', 'mod_coursereadings'));
    } else {
        $url = new moodle_url('/mod/coursereadings/index.php', array('id'=>$PAGE->course->id));
        $contentlink = html_writer::link($url, get_string('viewcoursematerial', 'mod_coursereadings'));
        $contents[] = html_writer::tag('li', $contentlink);
    }

    $bc->content = html_writer::tag('ol', implode('', $contents), array('class' => 'list'));

    $regions = $PAGE->blocks->get_regions();
    $firstregion = reset($regions);
    $PAGE->blocks->add_fake_block($bc, $firstregion);
}

function coursereadings_course_articles($courseid) {
    $modinfo = get_fast_modinfo($courseid);
    $cminfo = $modinfo->get_instances_of('coursereadings');
    $instances = array();

    foreach ($cminfo as $cm) {
        $instances[] = coursereadings_get_cm_instance($cm);
    }

    return $instances;
}

function coursereadings_get_cm_instance($cm) {
    global $DB;

    $sql  = 'SELECT ia.id AS mooid, a.*, s.id AS sourceid, s.title AS sourcetitle, s.author AS sourceAuthor, s.year ';
    $sql .= 'FROM   ({coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source=s.id) ';
    $sql .= '       INNER JOIN {coursereadings_inst_article} ia ON ia.articleid=a.id ';
    $sql .= 'WHERE ia.instanceid = :instanceid ';
    $sql .= 'ORDER BY ia.id ASC';

    $instance = new stdClass;
    $instance->id = $cm->instance;
    $instance->cmid = $cm->id;
    $instance->name = $cm->name;
    $instance->articles = $DB->get_records_sql($sql, array('instanceid'=>$instance->id));

    return $instance;
}

function coursereadings_get_article_download_url($id, $file, $context, $skipnotice=false) {
    global $CFG;
    $filearea = 'articles';
    if ($skipnotice) {
        $filearea = 'plainarticles';
    }
    return $CFG->wwwroot . "/pluginfile.php/".$context->id."/mod_coursereadings/$filearea/$id/$file?forcedownload=1";
}

function coursereadings_cron () {
    // Function to be run periodically according to the moodle cron
    // This function searches for things that need to be done, such
    // as sending out mail, toggling flags etc ...
    global $CFG;
    return true;
}

function coursereadings_get_article_file($article, $context) {
    $fs = get_file_storage();
    $files = $fs->get_area_files(context_system::instance()->id, 'mod_coursereadings', 'articles', $article->id, 'sortorder', false);
    return reset($files);
}

function coursereadings_get_article_link($article, $context, $file=null, $skipnotice=false) {
    $linkurl = '';
    $external = false;
    $config = get_config('coursereadings');

    if (empty($file)) {
        $file = coursereadings_get_article_file($article, $context);
    }

    if (!empty($article->doi)) {
        $linkurl = $config->doiresolver.$article->doi;
        $external = true;
    } elseif (!empty($article->externalurl)) {
        $linkurl = $article->externalurl;
        if (!preg_match('/^(ht|f)tps?:\/\//', $linkurl)) {
            $linkurl = 'http://' . $linkurl;
        }
        $external = true;
    } elseif (!empty($file)) {
        $linkurl = coursereadings_get_article_download_url($article->id, $file->get_filename(), $context, $skipnotice);
    }

    if (empty($linkurl)) {
        return $article->title;
    }

    $attributes = array();
    if ($external) {
        $attributes['target'] = '_blank';
    }
    $link = html_writer::link($linkurl, $article->title, $attributes);
    if (!$external) {
        $link .= " (".coursereadings_filesize($file->get_filesize()).")";
    }
    return $link;
}

function coursereadings_filesize($size) {
    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 1).$units[$i];
}

function coursereadings_add_to_queue($type, $id, $notes=null) {
    global $DB;

    $data = new stdClass();
    $data->type = $type;
    $data->objectid = $id;
    if (!empty($notes)) {
        $data->notes = $notes;
    }
    $DB->insert_record('coursereadings_queue', $data);
}

function coursereadings_get_article_storedfile($id, $notice=false) {
    $config = get_config('coursereadings');
    $fs = get_file_storage();
    if ($notice && !empty($config->copyrightnoticearticle) && is_readable($config->copyrightnoticearticle)) {
        $files = $fs->get_area_files(context_system::instance()->id, 'mod_coursereadings', 'articleswithnotice', $id, 'sortorder', false);
        if (!count($files)) {
            // We don't seem to have a copy of this article with the copyright notice prepended - make one.
            $files = $fs->get_area_files(context_system::instance()->id, 'mod_coursereadings', 'articles', $id, 'sortorder', false);
            if (count($files)) {
                $basefile = reset($files);
                return coursereadings_prepend_notice($basefile, $config->copyrightnoticearticle, $id);
            }
        }
    } else {
        $files = $fs->get_area_files(context_system::instance()->id, 'mod_coursereadings', 'articles', $id, 'sortorder', false);
    }
    return reset($files);
}

function coursereadings_prepend_notice($file, $notice, $articleid) {
    global $CFG, $DB;
    // Include the main TCPDF library and FPDI importer.
    define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
    require_once($CFG->dirroot.'/mod/coursereadings/lib/tcpdf/tcpdf.php');
    require_once($CFG->dirroot.'/mod/coursereadings/lib/tcpdf/tcpdi.php');
    $fs = get_file_storage();
    $article = $DB->get_record('coursereadings_article', array('id'=>$articleid));
    $source = $DB->get_record('coursereadings_source', array('id'=>$article->source));

    try {
        // Create new PDF document.
        $pdf = new TCPDI(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information.
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(empty($article->author) ? $source->author : $article->author);
        $pdf->SetTitle($article->title);

        // Remove default header/footer.
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);

        $pdf->SetDisplayMode('fullpage');
        $pdf->setStartingPageNumber(1); // Make sure numbering starts with the cover being 1.  Can't go negative to shunt first article to pg1.

        $pdf->AddPage();
        $pdf->setSourceFile($notice);
        $idx = $pdf->importPage(1);
        $pdf->useTemplate($idx);

        $pagecount = $pdf->setSourceData($file->get_content());
        for ($i = 1; $i <= $pagecount; $i++) {
            $tplidx = $pdf->importPage($i, '/MediaBox');
            $size = $pdf->getTemplatesize($tplidx);
            $orientation = ($size['w'] > $size['h']) ? 'L' : 'P';

            $dims = array($size['w'], $size['h']);
            $pdf->AddPage($orientation, $dims);
            $pdf->setPageFormatFromTemplatePage($i, $orientation);

            $pdf->useTemplate($tplidx);
            $pdf->importAnnotations($i);
        }

        $pdfdata = $pdf->Output($article->title, 'S');
        $filerecord = array(
            'contextid' => $file->get_contextid(),
            'component' => $file->get_component(),
            'filearea' => 'articleswithnotice',
            'itemid' => $file->get_itemid(),
            'filepath' => $file->get_filepath(),
            'filename' => $file->get_filename(),
            'userid' => $file->get_userid(),
            'mimetype' => $file->get_mimetype(),
            'source' => $file->get_source(),
            'author' => $file->get_author(),
            'license' => $file->get_license()
        );

        $newfile = $fs->create_file_from_string($filerecord, $pdfdata);
    } catch (Exception $x) {
        // Failed to prepend notice - log failure and return original file.
        $event = \mod_coursereadings\event\coursereadings_article_notice_failed::create(array(
            'objectid' => $articleid,
            'context' => context_system::instance(),
            'other' => array(
                'filename' => $file->get_filename(),
                'articleid' => $articleid
            )
        ));
        $event->trigger();
        $newfile = $file;
    }

    return $newfile;
}

function coursereadings_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if (!($article = $DB->get_record('coursereadings_article', array('id'=>$args[0]))) || !has_capability('mod/coursereadings:view', $context)) {
        send_file_not_found();
        die;
    }

    // Should we serve a copy with the copyright warning notice, if available/applicable?
    $notice = true;
    if ($filearea == 'plainarticles') {
        // Plain (no warning notice) version specifically requested.
        $notice = false;
    }

    $file = coursereadings_get_article_storedfile($article->id, $notice);

    if (empty($file)) {
        send_file_not_found();
        die;
    }

    if (!isset($_SERVER['HTTP_RANGE'])) {
        // Chrome (and others?) re-issues the request with Range headers to get it in chunks.
        // We only want to log the initial request, not each chunk.
        $event = \mod_coursereadings\event\coursereadings_article_downloaded::create(array(
            'objectid' => $cm->id,
            'context' => $context,
            'other' => array(
                'filename' => $file->get_filename(),
                'articleid' => $article->id
            )
        ));
        $event->trigger();
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * List of view style log actions
 * @return array
 */
function coursereadings_get_view_actions() {
    return array('view','view all');
}

/**
 * List of update style log actions
 * @return array
 */
function coursereadings_get_post_actions() {
    return array('update', 'add');
}

/**
 * Return use outline
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $resource
 * @return object|null
 */
function coursereadings_user_outline($course, $user, $mod, $resource) {
    global $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'coursereadings',
                                              'action'=>'view', 'info'=>$resource->id), 'time ASC')) {

        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new stdClass();
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;

        return $result;
    }
    return NULL;
}

/**
 * Return use complete
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $resource
 */
function coursereadings_user_complete($course, $user, $mod, $resource) {
    global $CFG, $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'coursereadings',
                                              'action'=>'view', 'info'=>$resource->id), 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo "$strnumviews - $strmostrecently ".userdate($lastlog->time);

    } else {
        print_string('neverseen', 'resource');
    }
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function coursereadings_dndupload_register() {
    global $PAGE;
    $PAGE->requires->yui_module('moodle-mod_coursereadings-dndupload', 'M.mod_coursereadings.dndupload.init');
    $PAGE->requires->strings_for_js(array('dndupload_resource', 'dndupload_coursereadings', 'title_of_article', 'source_type', 'source_book', 'source_journal', 'source_other', 'source_subtype', 'source_subtypes', 'furtherinfo', 'journal_notice', 'title_of_source', 'author_of_periodical', 'author_of_source', 'editor_of_source', 'year_of_publication', 'publisher', 'isbn', 'volume_number', 'edition', 'page_range', 'pages', 'externalurl', 'doi', 'sourceurl', 'loading'), 'mod_coursereadings');
    return array('files' => array(
                     array('extension' => 'pdf', 'message' => get_string('dnduploadresource', 'mod_coursereadings'))
                 ));
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function coursereadings_dndupload_handle($uploadinfo) {
    global $DB, $USER;
    $draftitemid = 0;

    if (!empty($uploadinfo->metadata->articleid)) {
        $article = $DB->get_record('coursereadings_article', array('id'=>$uploadinfo->metadata->articleid));
    } else {
        // New article - add or retrieve source first.
        if (!empty($uploadinfo->metadata->sourceid)) {
            $source = $DB->get_record('coursereadings_source', array('id'=>$uploadinfo->metadata->sourceid));
        } else {
            // Add source to database
            $source = new stdClass();
            $source->type = $uploadinfo->metadata->source_type;
            $source->title = $uploadinfo->metadata->title_of_source;
            $source->author = $uploadinfo->metadata->author_of_source;
            $source->year = $uploadinfo->metadata->year;
            if (empty($source->year)) {
                // Prevent storage of "0" as year.
                unset($source->year);
            }
            $source->publisher = $uploadinfo->metadata->publisher;
            $source->isbn = $uploadinfo->metadata->isbn;
            $source->pages = $uploadinfo->metadata->pages;
            $source->editor = $uploadinfo->metadata->editor_of_source;
            $source->volume = $uploadinfo->metadata->volume_number;
            $source->edition = $uploadinfo->metadata->edition;
            if ($source->type == 'other') {
                $source->subtype = $uploadinfo->metadata->subtype;
                $source->furtherinfo = $uploadinfo->metadata->furtherinfo;
            }
            $source->createdby = $USER->id;
            $source->id = $DB->insert_record('coursereadings_source', $source);
            coursereadings_add_to_queue('source', $source->id);
        }

        // Add article reading to database.
        $article = new stdClass();
        $article->title = $uploadinfo->metadata->title_of_article;
        $article->pagerange = $uploadinfo->metadata->page_range;
        $article->author = $uploadinfo->metadata->author_of_periodical;
        $article->externalurl = $uploadinfo->metadata->externalurl;
        $article->doi = $uploadinfo->metadata->doi;
        $article->source = $source->id;
        $article->createdby = $USER->id;
        $article->id = $DB->insert_record('coursereadings_article', $article);
        coursereadings_add_to_queue('article', $article->id);
        $draftitemid = $uploadinfo->draftitemid;
    }
    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $article->title;//$uploadinfo->displayname;
    $data->intro = '';
    $data->introformat = FORMAT_HTML;
    $data->coursemodule = $uploadinfo->coursemodule;
    //$data->files = $uploadinfo->draftitemid;

    return coursereadings_add_ajax_instance($data, $article->id, $draftitemid);
}

function coursereadings_find_matching_file($draftitemid, $filename = '') {
    global $DB;
    // Check database for matching files.
    $articleid = 0;
    $params = array(
        'component' => 'user',
        'filearea' => 'draft',
        'itemid' => $draftitemid,
        'mimetype' => 'application/pdf'
    );
    if (!empty($filename)) {
        $params['filename'] = $filename;
    }
    $contenthash = $DB->get_field('files', 'contenthash', $params);
    if ($contenthash) {
        $params = array(
            'component' => 'mod_coursereadings',
            'filearea' => 'articles',
            'contenthash' => $contenthash,
            'mimetype' => 'application/pdf'
        );
        $matchid = $DB->get_field('files', 'itemid', $params, IGNORE_MULTIPLE);
        if ($matchid && is_numeric($matchid)) {
            $articleid = intval($matchid);
        }
    }

    return $articleid;
}

function coursereadings_save_file($data, $draftitemid) {
    global $DB;
    $fs = get_file_storage();

    $context = context_system::instance();
    if ($draftitemid) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_coursereadings', 'articles', $data->articleid, array('subdirs'=>false));
    }
    $files = $fs->get_area_files($context->id, 'mod_coursereadings', 'articles', $data->articleid, 'sortorder', false);
    if (count($files) == 1) {
        // only one file attached, set it as main file automatically
        $file = reset($files);
        file_set_sortorder($context->id, 'mod_coursereadings', 'articles', $data->articleid, $file->get_filepath(), $file->get_filename(), 1);
    } elseif (!empty($data->filename)) {
        foreach ($files as $file) {
            if ($file->get_filename() == $data->filename) {
                // File is the main one we want
                file_set_sortorder($context->id, 'mod_coursereadings', 'articles', $data->articleid, $file->get_filepath(), $file->get_filename(), 1);
            } else {
                // File is not the one chosen for the article - user changed their mind after choosing/uploading.
                $file->delete();
            }
        }
    }
}

function coursereadings_extend_settings_navigation($settings, $modulenode) {
    global $CFG, $PAGE;
    $syscontext = context_system::instance();
    if (has_capability('mod/coursereadings:migratecontent', $syscontext)) {
        $url = $CFG->wwwroot.'/mod/coursereadings/manage/migrate-course-content.php?id='.$PAGE->course->id;
        $modulenode->add('Migrate content', $url, settings_navigation::TYPE_SETTING, null, 'migrate', new pix_icon('i/settings', ''));
    }
}