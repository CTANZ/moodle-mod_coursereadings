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
require_once('./merge-split-article-form.php');

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/merge-split-article.php');
$PAGE->set_pagelayout('base');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard  = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('dashboard_mergesplitarticle', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/merge-split-article.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');
echo $OUTPUT->header();


$currenttab = 'mergesplitarticle';
include('./managetabs.php');

$errorstr                   = get_string('error');
$stryes                     = get_string('yes');
$strno                      = get_string('no');
$stryesnooptions = array(0=>$strno, 1=>$stryes);

$returnurl = new moodle_url('/mod/coursereadings/manage/merge-split-article.php');

$mform = new coursereadings_mergesplitarticle_form();
if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    // Merge specified articles.
    $articles = explode(',', $data->articles);
    if (count($articles) < 2) {
        // We need at least two articles, if we're going to do anything useful.
        print_error('dashboard_notenoughfiles', 'mod_coursereadings', $returnurl);
    }
    $articleid = $articles[0];

    $filename = $data->newfilename;
    if (substr($filename, -4) !== '.pdf') {
        $filename .= '.pdf';
    }

    // Include the main TCPDF library and FPDI importer.
    require_once($CFG->dirroot.'/mod/coursereadings/lib/tcpdf/tcpdf.php');
    require_once($CFG->dirroot.'/mod/coursereadings/lib/tcpdf/tcpdi.php');

    // Create new PDF document.
    $pdf = new TCPDI(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information.
    $pdf->SetCreator(PDF_CREATOR);

    // Remove default header/footer.
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false);

    // Fetch and merge all selected PDFs into the new one.
    foreach($articles as $id) {
        $file = coursereadings_get_article_storedfile($id, false);
        if (empty($file)) {
            continue;
        }
        try {
            $pagecount = $pdf->setSourceData($file->get_content());
            for ($i = 1; $i <= $pagecount; $i++) {
                $tplidx = $pdf->importPage($i);
                $size = $pdf->getTemplatesize($tplidx);
                $orientation = ($size['w'] > $size['h']) ? 'L' : 'P';
                $pdf->AddPage($orientation);
                $pdf->useTemplate($tplidx);
            }
        } catch(Exception $x) {
            print_object($x);exit;
        }
    }

    $pdfcontent = $pdf->Output($filename, 'S');

    if (!empty($pdfcontent)) {
        // PDFs merged successfully - save merged PDF over old one.
        $fs = get_file_storage();

        // Get list of existing files from first part (should only be one).
        $oldfiles = $fs->get_area_files($syscontext->id, 'mod_coursereadings', 'articles', $articleid);

        // Add combined PDF file.
        $filerecord = new stdClass();
        $filerecord->contextid = $syscontext->id;
        $filerecord->component = 'mod_coursereadings';
        $filerecord->filearea = 'articles';
        $filerecord->itemid = $articleid;
        $filerecord->filepath = '/';
        $filerecord->filename = $filename;
        $newfile = $fs->create_file_from_string($filerecord, $pdfcontent);

        // Purge old files, if new file saved successfully.
        if ($newfile && !empty($pdfcontent) && count($oldfiles)) {
            foreach ($oldfiles as $hash=>$oldfile) {
                $oldfile->delete();
            }
        }

        // Delete article with copyright warning notice, if present.
        $withnotice = coursereadings_get_article_storedfile($articleid, true);
        if (!empty($withnotice)) {
            $withnotice->delete();
        }

        // Fetch all article usage, so we can tidy it up.
        $usage = $DB->get_records_list('coursereadings_inst_article', 'articleid', $articles);
        $instances = array();
        foreach ($usage as $record) {
            if (!array_key_exists($record->instanceid, $instances)) {
                $instances[$record->instanceid] = array();
            }
            $instances[$record->instanceid][] = $record->articleid;
        }
        foreach ($instances as $id => $instance) {
            if (!in_array($articleid, $instance)) {
                // Newly-merged article is not in this instance, replace the first selected part with it.
                $conditions = array('instanceid' => $id, 'articleid' => $instance[0]);
                $DB->set_field('coursereadings_inst_article', 'articleid', $articleid, $conditions);
            }
            if (count($instance) > 1) {
                // More than one merged article in this instance - remove the extras.
                $select = 'instanceid = ? AND articleid <> ?';
                $DB->delete_records_select('coursereadings_inst_article', $select, array($id, $articleid));
            }
        }

        // Remove the surplus parts, now that we've shifted all usage out of them.
        foreach ($articles as $article) {
            if ($article === $articleid) {
                // Don't delete the newly-merged article!
                continue;
            }
            // Delete files.
            $file = coursereadings_get_article_storedfile($article, false);
            if (!empty($file)) {
                $file->delete();
            }
            $filewithnotice = coursereadings_get_article_storedfile($article, true);
            if (!empty($filewithnotice)) {
                $filewithnotice->delete();
            }
            // Remove article from "new articles" queue, if present.
            $DB->delete_records('coursereadings_queue', array('type'=>'article', 'objectid'=>$article));

            // Remove article from database.
            $DB->delete_records('coursereadings_article', array('id'=>$article));
        }

        // Send user to "edit article" page to tweak merged article as necessary.
        $editurl = new moodle_url('/mod/coursereadings/manage/edit-article.php', array('id' => $articleid));
        redirect($editurl, get_string('dashboard_mergesplitarticle_complete', 'mod_coursereadings'));
    } else {
        // Something's gone wrong - we don't have a successful merge.
        print_error('dashboard_mergesplitarticle_failed', 'mod_coursereadings', $returnurl);
    }

} else {
    $mform->display();
}

echo $OUTPUT->footer();