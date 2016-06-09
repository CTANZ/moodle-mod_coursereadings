<?php

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/coursereadings/lib.php');
require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->dirroot.'/repository/upload/lib.php');

// Add the file to a draft file area.
$draftitemid = optional_param('draftitemid', file_get_unused_draft_itemid(), PARAM_INT);
$maxbytes = get_max_upload_file_size($CFG->maxbytes);
$repo = repository::get_instances(array('type' => 'upload'));
if (empty($repo)) {
    throw new moodle_exception('errornouploadrepo', 'moodle');
}
$repo = reset($repo); // Get the first (and only) upload repo.
$details = $repo->process_upload(null, $maxbytes, array('.pdf'), '/', $draftitemid);

$details['error'] = 0;

// Check database for matching files.
$articleid = coursereadings_find_matching_file($draftitemid);
if (!empty($articleid)) {
    $details['articleid'] = $articleid;
    $details['articletitle'] = $DB->get_field('coursereadings_article', 'title', array('id' => $articleid));
}

echo $OUTPUT->header();
echo json_encode($details);
die();