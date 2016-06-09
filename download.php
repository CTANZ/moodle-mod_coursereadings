<?php

require('../../config.php');
require_once('lib.php');

$courseid = required_param('id', PARAM_INT); // Course ID.
$cmid = optional_param('cm', 0, PARAM_INT); // Course module ID, to download one instance.
$requested = optional_param_array('articles', array(), PARAM_SEQUENCE); // Array of instanceid,articleid pairs to zip.
$mode = required_param('mode', PARAM_CLEAN); // Submit button value.
$config = get_config('coursereadings');
$scope = empty($cmid) ? 'course' : 'cm'; // Scope of request - course or module instance.
// =========================================================================
// Security checks START - teachers edit; students view.
// =========================================================================

if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('Course is misconfigured');
}

require_course_login($course, true);

if ($scope === 'course') {
    // Capability check at course level, fetch all instances in course.
    $context = context_course::instance($course->id);
    require_capability('mod/coursereadings:view', $context);

    if (!$instances = coursereadings_course_articles($course->id)) {
        print_error('No files specified');
    }
} else {
    // Capability check at CM level, fetch individual instance details.
    $cminfo = get_fast_modinfo($course);
    if (!$cm = $cminfo->get_cm($cmid)) {
        print_error('Course is misconfigured');
    }
    $context = context_module::instance($cm->id);
    require_capability('mod/coursereadings:view', $context);

    $instances = array();
    if (!$instance = coursereadings_get_cm_instance($cm)) {
        print_error('No files specified');
    }
    $instances[] = $instance;
}

// =========================================================================
// Security checks  END.
// =========================================================================

$downloadaspdf = get_string('download_as_pdf', 'mod_coursereadings');

$fs = get_file_storage();
$contents = array();
$requested = array_flip($requested);
$titles = array();
$externalurls = array();
$externaltitles = array();

foreach($instances as $instance) {
    $folder = substr(clean_param($instance->name, PARAM_FILE), 0, 30); // We want to allow spaces, but clean out anything nasty.
    foreach($instance->articles as $article) {
        if (($scope === 'course') && !array_key_exists($instance->id.','.$article->id, $requested)) {
            // Article not requested - skip it.
            continue;
        }
        if (!empty($article->externalurl) || !empty($article->doi)) {
            // External resource - compile a list which we'll handle separately.
            if (!empty($article->doi)) {
                $externalurls[] = $config->doiresolver . $article->doi;
            } else {
                $externalurls[] = $article->externalurl;
            }
            $externaltitles[] = $article->title;
            continue;
        }
        $notice = true;
        if ($mode == $downloadaspdf) {
            $notice = false;
        }
        $file = coursereadings_get_article_storedfile($article->id, $notice);
        if (empty($file)) {
            continue;
        }
        $contents[$folder.'/'.$file->get_filename()] = $file;
        $titles[$folder.'/'.$file->get_filename()] = $article->title;
    }
}

if($mode == $downloadaspdf) {
    // PDF requested; build it.

    $semestercodes = array('S1' => 'Semester 1',
        'S2' => 'Semester 2',
        'W' => 'Whole Year',
        'CY' => 'Cross Year',
        'B1' => 'Bridging 1',
        'B2' => 'Bridging 2',
        'B3' => 'Bridging 3',
        'FY' => 'Full Year',
        'M1' => 'MBA 1',
        'M2' => 'MBA 2',
        'M3' => 'MBA 3',
        'M4' => 'MBA Four',
        'YB' => 'Full Year B',
        'YB1' => 'Year B First Half',
        'YB2' => 'Year B Second Half',
        'YC' => 'Full Year C',
        'YC1' => 'Year C First Half',
        'YC2' => 'Year C Second Half',
        'YD' => 'Full Year D',
        'YD1' => 'Year D First Half',
        'YD2' => 'Year 2 Second Half',
        'SU1' => 'Summer (January start)',
        'SU2' => 'Summer (November start)',
        'T1' => 'Term 1',
        'T2' => 'Term Two',
        'T3' => 'Term Three',
        'T4' => 'Term Four',
        'X' => 'General non-calendar based',
        'A' => 'Any Time Start'
    );

    $coursecodes = explode(',', $course->idnumber);
    $maincoursecode = $coursecodes[0];
    $extracoursecodes = array();
    if (count($coursecodes) > 1) {
        for ($i=1;$i<(min(5, count($coursecodes)));$i++) {
            $extracoursecodes[] = $coursecodes[$i];
        }
    }
    $extracoursecodes = implode(', ', $extracoursecodes);
    $semester = '';
    $matches = array();
    if (preg_match('/^[a-z]{4}[0-9]{3}(.+)$/i', $maincoursecode, $matches)) {
        $semester = $semestercodes[$matches[1]];
    }
    $category = $DB->get_record('course_categories', array('id'=>$course->category), 'id, name, path');
    $path = explode('/', $category->path);
    $logofile = $CFG->dirroot.'/mod/coursereadings/templates/college-logos/'.$path[1].'.png';

    // Include the main TCPDF library and FPDI importer.
    require_once($CFG->dirroot.'/mod/coursereadings/lib/tcpdf/tcpdf.php');
    require_once($CFG->dirroot.'/mod/coursereadings/lib/tcpdf/tcpdi.php');

    // Create new PDF document.
    $pdf = new TCPDI(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information.
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('University of Canterbury');
    $pdf->SetTitle('Course Readings for '.$course->shortname);
    $pdf->SetSubject($course->shortname);
    $pdf->SetKeywords($course->shortname);

    // Remove default header/footer.
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false);

    $pdf->SetDisplayMode('fullpage');
    $pdf->setStartingPageNumber(1); // Make sure numbering starts with the cover being 1.  Can't go negative to shunt first article to pg1.

    $pdf->AddPage();

    // Import cover page template.
    $pdf->setSourceFile($CFG->dirroot.'/mod/coursereadings/templates/blank-cover.pdf');
    $idx = $pdf->importPage(1);
    $pdf->useTemplate($idx);

    $pdf->setFont('profilepro', '', 36);
    $pdf->Text(19, 16, $maincoursecode);

    $pdf->setFont('profilepro', '', 28);
    $pdf->Text(19, 28, $extracoursecodes);

    $pdf->setFont('profilepro', '', 20);
    $pdf->Text(19, 70, $course->fullname);

    $pdf->setFont('profilepro', '', 16);
    if ($scope === 'cm') {
        $pdf->Text(19, 79, $cm->name);
    }
    $pdf->Text(19, 98, $semester);
    $pdf->Text(19, 104, $category->name);

    $pdf->Text(35.5, 219, fullname($USER));
    $pdf->Text(46, 238, $USER->idnumber);

    $pdf->setFont('profilepro', '', 30);
    $pdf->Text(180, 273, date('Y'));

    $pdf->setFont('profilepro', '', 10);
    $pdf->Text(19, 262, 'Generated on '.date('d/m/Y'));

    if (file_exists($logofile)) {
        $pdf->Image($logofile, 20, 276, 45);
    }

    // Copyright page.
    $pdf->AddPage(); // Blank page between cover and copyright page, in case printed double-sided.
    $tocpage = 3;
    if (!empty($config->copyrightnoticecombined) && is_readable($config->copyrightnoticecombined)) {
        $tocpage = 5;
        $pdf->AddPage();
        $pdf->setSourceFile($config->copyrightnoticecombined);
        $idx = $pdf->importPage(1);
        $pdf->useTemplate($idx);
        $pdf->AddPage(); // Blank page after copyright page, in case printed double-sided.
    }
    $pdf->AddPage(); // Blank page after TOC, which will be inserted later.
    $pageno = 1;

    foreach($contents as $path => $file) {
        try {
            $pagecount = @$pdf->setSourceData($file->get_content());
            for ($i = 1; $i <= $pagecount; $i++) {
                $tplidx = $pdf->importPage($i, '/MediaBox');
                $size = $pdf->getTemplatesize($tplidx);
                $orientation = ($size['w'] > $size['h']) ? 'L' : 'P';

                $dims = array($size['w'], $size['h']);
                $pdf->AddPage($orientation, $dims);

                $pdf->setPageFormatFromTemplatePage($i, $orientation);

                $pdf->useTemplate($tplidx);
                $pdf->importAnnotations($i);

                if ($i == 1) {
                    // Add bookmark for first page of article.
                    $pdf->setBookmark($titles[$path], 0, 0, null, '', null, 0);
                }

                // Page number at bottom of page
                $pageno = $pdf->PageNo() + 1;
                $pdf->setFont('profilepro', '', 14);
                if ($orientation == 'P') {
                    $pdf->Text(100, $size['h'] - 10, $pageno);
                } else {
                    // Rotate 270 degress about the point at which we will be adding the page number.
                    $pdf->StartTransform();
                    $pdf->Rotate(270, 10, 100);
                    $pdf->Text(10, 100, $pageno);
                    $pdf->StopTransform();
                }
            }
        } catch(Exception $x) {
            $pdf->AddPage();
            $pdf->setFont('profilepro', '', 20);
            $pdf->Text(18, 20, get_string('articlenotincluded', 'mod_coursereadings'));
            $pdf->Text(18, 30, get_string('downloadindividually', 'mod_coursereadings'));
            $pdf->Text(100, 287 , $pageno);
            $pdf->setBookmark($titles[$path], 0, 0, null, '', null, 0);
        }
    }

    if (count($externalurls)) {
        $externalhtml = '<h2>Online resources</h2>';
        $externalhtml .= '<ol>';
        for($i=0; $i < count($externalurls); $i++) {
            $externalhtml .= '<li><b>'.$externaltitles[$i].'</b><br>';
            $externalhtml .= '<i><small><a href="' . $externalurls[$i] . '">' . $externalurls[$i] . '</a></small></i>';
        }
        $externalhtml .= '</ol>';

        $pdf->AddPage('P');
        $pdf->setFont('helvetica');
        $pdf->setBookmark('Online resources', 0, 0, null, '', null, 0);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->writeHTML($externalhtml, true, false, true, false, '');
    }

    // Add table of contents.
    $pdf->addTOCPage(PDF_PAGE_ORIENTATION, PDF_PAGE_FORMAT);

    $pdf->setFont('profilepro', '', 24);
    $pdf->MultiCell(0, 0, 'Table Of Contents', 0, 'C', 0, 1, '', '', true, 0);
    $pdf->Ln();

    $pdf->setFont('profilepro', '', 12);
    $pdf->addTOC($tocpage, 'profilepro', '.', 'Table of Contents');
    $pdf->endTOCPage();

    $event = \mod_coursereadings\event\coursereadings_bundled_pdf_downloaded::create(array(
        'objectid' => $course->id,
        'context' => $context
    ));
    $event->trigger();

    $pdf->Output("Course Readings - $maincoursecode.pdf", 'D');
} else {
    // Default to Zip archive.
    require_once($CFG->libdir.'/filestorage/zip_packer.php');

    // Create list of external resources if required.
    if (count($externalurls)) {
        // Include the main TCPDF library and FPDI importer.
        require_once($CFG->dirroot.'/mod/coursereadings/lib/tcpdf/tcpdf.php');

        // Create new PDF document.
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information.
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('University of Canterbury');
        $pdf->SetTitle('Online resources for '.$course->shortname);
        $pdf->SetSubject($course->shortname);
        $pdf->SetKeywords($course->shortname);

        // Remove default header/footer.
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);

        $pdf->SetDisplayMode('fullpage');

        $externalhtml = '<h2>Online resources</h2>';
        $externalhtml .= '<ol>';
        for($i=0; $i < count($externalurls); $i++) {
            $externalhtml .= '<li><b>'.$externaltitles[$i].'</b><br>';
            $externalhtml .= '<i><small><a href="' . $externalurls[$i] . '">' . $externalurls[$i] . '</a></small></i>';
        }
        $externalhtml .= '</ol>';

        $pdf->AddPage('P');
        $pdf->setFont('helvetica');
        $pdf->setBookmark('Online resources', 0, 0, null, '', null, 0);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->writeHTML($externalhtml, true, false, true, false, '');

        $externalpdf = $pdf->Output("Online resources - {$course->shortname}.pdf", 'S');
        $contents["Online resources - {$course->shortname}.pdf"] = array($externalpdf);
    }

    //create path for new zip file.
    $tempzip = tempnam($CFG->tempdir.'/', 'coursereadings_');
    // Zip files.
    $zipper = new zip_packer();
    if ($zipper->archive_to_pathname($contents, $tempzip)) {
        $event = \mod_coursereadings\event\coursereadings_bundled_zip_downloaded::create(array(
            'objectid' => $course->id,
            'context' => $context
        ));
        $event->trigger();

        send_temp_file($tempzip, 'Course Readings - '.clean_param($course->shortname, PARAM_FILE).'.zip');
    } else {
        echo "Uh-oh!";
    }
}

