<?php

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once('importlib.php');
require_once('import-form.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

@set_time_limit(60*60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/import-sources.php');

admin_externalpage_setup('coursematerialsourceimport');

$errorstr                   = get_string('error');
$stryes                     = get_string('yes');
$strno                      = get_string('no');
$stryesnooptions = array(0=>$strno, 1=>$stryes);

$returnurl = new moodle_url('/mod/coursereadings/manage/import-sources.php');

$today = time();
$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

// array of all valid fields for validation
$STD_FIELDS = array('Title', 'Author', 'Editor', 'Year', 'Publisher', 'ISBN', 'Pages');

if (empty($iid)) {
    $mform1 = new coursereadings_importsources_form1();

    if ($formdata = $mform1->get_data()) {
        $iid = csv_import_reader::get_new_iid('uploadsource');
        $cir = new csv_import_reader($iid, 'uploadsource');

        $content = $mform1->get_file_content('sourcefile');

        $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
        unset($content);

        if ($readcount === false) {
            print_error('csvloaderror', '', $returnurl);
        } else if ($readcount == 0) {
            print_error('csvemptyfile', 'error', $returnurl);
        }
        // test if columns ok
        $filecolumns = coursereadings_validate_source_upload_columns($cir, $STD_FIELDS, $returnurl);
        // continue to form2

    } else {
        echo $OUTPUT->header();

        echo $OUTPUT->heading_with_help(get_string('uploadsources', 'mod_coursereadings'), 'uploadsources', 'mod_coursereadings');

        $mform1->display();
        echo $OUTPUT->footer();
        die;
    }
} else {
    $cir = new csv_import_reader($iid, 'uploadsource');
    $filecolumns = coursereadings_validate_source_upload_columns($cir, $STD_FIELDS, $returnurl);
}

$mform2 = new coursereadings_importsources_form2(null, array('columns'=>$filecolumns, 'data'=>array('iid'=>$iid, 'previewrows'=>$previewrows)));

// If a file has been uploaded, then process it
if ($formdata = $mform2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);

} else if ($formdata = $mform2->get_data()) {
    // Print the header
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploadsourcesresult', 'mod_coursereadings'));

    // verification moved to two places: after upload and into form2
    $sourcesnew      = 0;
    $sourceserrors   = 0;

    // init csv import helper
    $cir->init();
    $linenum = 1; //column header is first line
    $importtime = time();
    while ($line = $cir->next()) {
        $linenum++;

        $source = new stdClass();
        foreach ($line as $keynum => $value) {
            if (!isset($filecolumns[$keynum])) {
                // This should not happen.
                continue;
            }
            $key = $filecolumns[$keynum];
            $source->$key = $value;
        }

        // Save the new source to the database.
        $source->timemodified = $importtime;
        $source->timecreated  = $importtime;
        $source->type = 'book';
        try {
            $source->id = $DB->insert_record('coursereadings_source', $source);
            $sourcesnew++;
        } catch (Exception $x) {
            $sourceserrors++;
        }
    }

    $cir->close();
    $cir->cleanup(true);

    echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
    echo '<p>';
    echo get_string('sourcescreated', 'mod_coursereadings').': '.$sourcesnew.'<br />';
    echo get_string('errors', 'mod_coursereadings').': '.$sourceserrors.'</p>';
    echo $OUTPUT->box_end();

    echo $OUTPUT->continue_button($returnurl);
    echo $OUTPUT->footer();
    die;
}

// Print the header
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('uploadsourcespreview', 'mod_coursereadings'));

// NOTE: this is JUST csv processing preview, we must not prevent import from here if there is something in the file!!
//       this was intended for validation of csv formatting and encoding, not filtering the data!!!!
//       we definitely must not process the whole file!

// preview table data
$data = array();
$cir->init();
$linenum = 1; //column header is first line
$noerror = true; // Keep status of any error.
while ($linenum <= $previewrows and $fields = $cir->next()) {
    $linenum++;
    $rowcols = array();
    $rowcols['line'] = $linenum;
    foreach($fields as $key => $field) {
        $rowcols[$filecolumns[$key]] = s($field);
    }
    $data[] = $rowcols;
}
if ($fields = $cir->next()) {
    $data[] = array_fill(0, count($fields) + 1, '...');
}
$cir->close();

$table = new html_table();
$table->id = "sourceimportpreview";
$table->attributes['class'] = 'generaltable';
$table->tablealign = 'center';
$table->summary = get_string('uploadsourcespreview', 'mod_coursereadings');
$table->head = array();
$table->data = $data;

$table->head[] = get_string('csvline', 'mod_coursereadings');
foreach ($filecolumns as $column) {
    $table->head[] = $column;
}

echo html_writer::tag('div', html_writer::table($table), array('class'=>'flexible-wrap'));

// Print the form if valid values are available
if ($noerror) {
    $mform2->display();
}
echo $OUTPUT->footer();
die;

