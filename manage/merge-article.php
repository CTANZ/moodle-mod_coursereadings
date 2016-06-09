<?php

/**
 * Course materials dashboard - merge article.
 *
 * Tool for merging articles.
 *
 * @package mod_coursereadings
 * @copyright 2016 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/coursereadings/lib.php');
require_once($CFG->dirroot . '/mod/coursereadings/manage/merge-article-forms.php');

$duplicate = required_param('id', PARAM_INT);
$canonical = optional_param('target', 0,  PARAM_INT);
$returnaction = optional_param('return', 'dashboard', PARAM_ALPHANUM);

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/merge-article.php', array('id'=>$duplicate));
$PAGE->set_pagelayout('base');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard  = get_string('dashboard', 'coursereadings');
$strmergearticle = get_string('dashboard_mergearticle', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add($strmergearticle);
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strmergearticle);
$PAGE->set_cacheable(false);
$PAGE->set_button('&nbsp;');

switch($returnaction) {
    case 'find':
        $returnurl = new moodle_url("/mod/coursereadings/manage/find-article.php");
        break;

    case 'flagged':
        $returnurl = new moodle_url("/mod/coursereadings/manage/flagged-articles.php");
        break;

    case 'dashboard':
    default:
        $returnurl = new moodle_url("/mod/coursereadings/manage/index.php");
        break;
}

if (empty($canonical)) {
    // No canonical article provided - display form to search for one.
    echo $OUTPUT->header();
    $currenttab = 'findarticle';
    include('./managetabs.php');

    $article = $DB->get_record('coursereadings_article', array('id'=>$duplicate));
    $source = $DB->get_record('coursereadings_source', array('id'=>$article->source));
    $data = new stdClass();
    // Some fields are used in search, so we need different names.
    $data->static_articletitle = $article->title;
    $data->static_pagerange = $article->pagerange;
    $data->static_sourcetitle = $source->title;
    $data->static_isbn = $source->isbn;
    $data->static_pages = $source->pages;

    $data->id = $article->id;
    $data->return = $returnaction;

    $searchform = new coursereadings_mergearticle_search_form();
    $searchform->set_data($data);
    $searchform->display();

    $PAGE->requires->yui_module('moodle-mod_coursereadings-mergearticle', 'M.mod_coursereadings.mergearticle.init');
} else {
    // Duplicate and canonical articles have been provided, allow an opportunity to edit canonical article details and perform merge.

    $mergeform = new coursereadings_mergearticle_merge_form();

    if ($mergeform->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $mergeform->get_data()) {
        // Update canonical article's details.
        $article = new stdClass();
        $article->id = $data->target;
        $article->title = $data->title;
        $article->author = $data->periodicalAuthor;
        $article->pagerange = $data->pagerange;
        $article->totalpages = $data->totalpages;
        $article->externalurl = $data->externalurl;
        $article->doi = $data->doi;
        $article->source = $data->source;
        $article->modifiedby = $USER->id;
        $DB->update_record('coursereadings_article', $article);

        $duparticle = new stdClass();
        $duparticle->id = $duplicate;
        if ($data->keepfile === 'canonical') {
            $dfile = coursereadings_get_article_storedfile($duplicate);
            if (!empty($dfile)) {
                // Delete duplicate article's file - we're keeping the canonical.
                $dfile->delete();
                // Delete file with warning notice (if present), too.
                $filewithnotice = coursereadings_get_article_storedfile($duplicate, true);
                if (!empty($filewithnotice)) {
                    $filewithnotice->delete();
                }
            }
        } else if ($data->keepfile === 'duplicate') {
            $cfile = coursereadings_get_article_storedfile($canonical);
            $dfile = coursereadings_get_article_storedfile($duplicate);
            if (!empty($cfile)) {
                // Delete canonical article's file - we're replacing it with the duplicate's.
                $cfile->delete();
                // Delete files with warning notice (if present) - will be regenerated on next download.
                $cfilewithnotice = coursereadings_get_article_storedfile($canonical, true);
                if (!empty($cfilewithnotice)) {
                    $cfilewithnotice->delete();
                }
                $dfilewithnotice = coursereadings_get_article_storedfile($duplicate, true);
                if (!empty($dfilewithnotice)) {
                    $dfilewithnotice->delete();
                }
            }
            // Update duplicate article's file record to point to canonical article.
            $DB->set_field('files', 'itemid', $canonical, array('id'=>$dfile->get_id()));
        }
        // If keepfile is 'none', neither article has a file - so no need to check and do anything.

        // Update instances to point to canonical article.
        $DB->set_field('coursereadings_inst_article', 'articleid', $canonical, array('articleid'=>$duplicate));

        // Remove duplicate article from "new articles" queue, if present.
        $DB->delete_records('coursereadings_queue', array('type'=>'article', 'objectid'=>$duplicate));

        // Remove duplicate article.
        $DB->delete_records('coursereadings_article', array('id'=>$duplicate));

        redirect($returnurl, get_string('dashboard_mergearticle_complete', 'mod_coursereadings'));
    } else {
        // Display merge form.
        echo $OUTPUT->header();
        $currenttab = 'findarticle';
        include('./managetabs.php');

        // Default data - both articles, but duplicate needs to be prefixed.
        $data = $DB->get_record('coursereadings_article', array('id'=>$canonical));
        $duplicatedata = $DB->get_record('coursereadings_article', array('id'=>$duplicate));
        foreach ($duplicatedata as $key => $value) {
            $data->{'static_'.$key} = $value;
        }
        $canonicalsource = $DB->get_record('coursereadings_source', array('id'=>$data->source));
        $duplicatesource = $DB->get_record('coursereadings_source', array('id'=>$duplicatedata->source));
        $data->sourcedisplay = $canonicalsource->title . ' (' . $canonicalsource->year . ')';
        $data->periodicalAuthor = $data->author;
        $data->static_sourcedisplay = $duplicatesource->title . ' (' . $duplicatesource->year . ')';
        $data->static_periodicalAuthor = $duplicatedata->author;

        // Files (where applicable).
        $cfile = coursereadings_get_article_storedfile($canonical);
        $dfile = coursereadings_get_article_storedfile($duplicate);

        $ctx = context_system::instance();
        $data->keepfile = 'canonical'; // Default is to keep canonical file.

        // Possible combinations of canonical and duplicate files (other than two separate files).
        $nofile = (empty($cfile) && empty($dfile));
        $samefile = (!empty($cfile) && !empty($dfile) && ($cfile->get_contenthash() === $dfile->get_contenthash()));
        $cfileonly = (empty($dfile) && !empty($cfile));
        $dfileonly = (empty($cfile) && !empty($dfile));

        if ($nofile) {
            // No file in either article record - display a message saying so.
            $data->keepfile = 'none';
            $data->cfile = get_string('dashboard_mergearticle_nofile', 'mod_coursereadings');
            $data->dfile = '';
        } else if ($samefile || $cfileonly) {
            // Articles have identical files attached, or only canonical has a file.
            // Only show the canonical file - no option to choose what to keep.
            $cfurl = coursereadings_get_article_download_url($canonical, $cfile->get_filename(), $ctx);
            $data->cfile = html_writer::link($cfurl, $cfile->get_filename(), array('target' => '_blank'));
            $data->dfile = '';
        } else if ($dfileonly) {
            // Only duplicate has a file - show it with no option to choose what to keep.
            // File displayed as cfile for consistent single-file display.
            $data->keepfile = 'duplicate';
            $dfurl = coursereadings_get_article_download_url($duplicate, $dfile->get_filename(), $ctx);
            $data->cfile = html_writer::link($dfurl, $dfile->get_filename(), array('target' => '_blank'));
            $data->dfile = '';
        } else {
            // Articles have different files - show both, with buttons to choose which to keep.
            $cbutton = html_writer::tag('button', get_string('dashboard_mergearticle_usefile', 'mod_coursereadings'), array('class' => 'usefile', 'disabled' => 'disabled'));
            $cfurl = coursereadings_get_article_download_url($canonical, $cfile->get_filename(), $ctx);
            $data->cfile = $cbutton . ' ' . html_writer::link($cfurl, $cfile->get_filename(), array('target' => '_blank'));
            $data->cfile = html_writer::div($data->cfile, 'keepfile keepfile-canonical keepfile-selected');
            $dbutton = html_writer::tag('button', get_string('dashboard_mergearticle_usefile', 'mod_coursereadings'), array('class' => 'usefile', 'disabled' => 'disabled'));
            $dfurl = coursereadings_get_article_download_url($duplicate, $dfile->get_filename(), $ctx);
            $data->dfile = $dbutton . ' ' . html_writer::link($dfurl, $dfile->get_filename(), array('target' => '_blank'));
            $data->dfile = html_writer::div($data->dfile, 'keepfile keepfile-duplicate');
        }
        // Article IDs should be in the same fields as in previous steps.
        $data->id = $duplicate;
        $data->target = $canonical;
        $data->return = $returnaction;

        $mergeform->set_data($data);

        $mergeform->display();
        $PAGE->requires->yui_module('moodle-mod_coursereadings-editarticle', 'M.mod_coursereadings.editarticle.init');
        $PAGE->requires->yui_module('moodle-mod_coursereadings-pickfile', 'M.mod_coursereadings.pickfile.init');
    }
}


echo $OUTPUT->footer();