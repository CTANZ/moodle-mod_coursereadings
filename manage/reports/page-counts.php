<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Course materials Page Counts report.
 *
 * Provides a quick interface to specify total page counts for articles.
 *
 * @package mod_coursereadings
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$params = array();

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:managesite', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/reports/page-counts.php', $params);
$PAGE->set_pagelayout('base');
$PAGE->requires->jquery_plugin('coursereadings-stickytableheaders', 'mod_coursereadings');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('dashboard_reports', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/reports/index.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');

if (empty($format)) { // Not downloading - spit out page content.
    echo $OUTPUT->header();

    $currenttab = 'reports';
    include('../managetabs.php');

    $content  = html_writer::tag('h3', 'Articles Without Page Counts');
    echo html_writer::tag('div', $content, array('class'=>'coursereadings_dashboard'));
}

$fields = " DISTINCT a.id, f.filename,
            s.id as source, s.title AS sourceTitle, s.pages AS totalPages, s.isbn,
            a.title AS articleTitle, a.pagerange, a.totalpages AS scannedpages";
$from = "({coursereadings_article} a inner join {coursereadings_source} s on a.source=s.id)
            inner join {files} f on (f.component='mod_coursereadings' AND f.filearea='articles' AND f.itemid=a.id AND f.filename <> '.')";
$where = 'a.totalpages IS NULL';
$sqlparams = null;

class page_count_table extends table_sql {
    function col_sourcetitle($row) {
        global $OUTPUT;
        $url = new moodle_url('/mod/coursereadings/manage/edit-source.php', array('id' => $row->source));
        $icon = $OUTPUT->pix_icon('i/edit', get_string('editsource', 'mod_coursereadings'));
        return $row->sourcetitle . ' ' . html_writer::link($url, $icon, array('target'=>'_blank'));
    }
    function col_articletitle($row) {
        global $OUTPUT, $CFG;
        $pdfurl = $CFG->wwwroot . "/pluginfile.php/1/mod_coursereadings/articles/{$row->id}/{$row->filename}?forcedownload=1";
        $editurl = new moodle_url('/mod/coursereadings/manage/edit-article.php', array('id' => $row->id));
        $icon = $OUTPUT->pix_icon('i/edit', get_string('editarticle', 'mod_coursereadings'));
        return html_writer::link($pdfurl, $row->articletitle) . ' ' . html_writer::link($editurl, $icon, array('target'=>'_blank'));
    }
    function col_scannedpages($row) {
        $fields = html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'articleid', 'id'=>'articleid_'.$row->id, 'value'=>$row->id));
        $fields .= html_writer::empty_tag('input', array('type'=>'number', 'name'=>'scannedpages', 'id'=>'scannedpages_'.$row->id, 'style'=>'width:60px;'));
        $fields .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>'Save'));
        $fields = html_writer::div($fields);

        return html_writer::tag('form', $fields, array('onsubmit'=>'mod_coursereadings_save_pagecount(this);return false;'));
    }
}

$table = new page_count_table('mod_coursereadings-report_pagecounts');
$table->set_attribute('id', 'coursereadings-report-pagecounts');
$table->define_baseurl($PAGE->url);
$table->set_sql($fields, $from, $where, $sqlparams);
$table->define_columns(array('sourcetitle', 'isbn', 'totalpages', 'articletitle', 'pagerange', 'scannedpages'));
$table->define_headers(array('Source Title', 'ISBN/ISSN', 'Pages', 'Article Title', 'Page Range', 'Article page count'));

$table->out(100, false); // 100 per page, no initials bar.

?>
<script>
    $('#coursereadings-report-contentusage').stickyTableHeaders({fixedOffset: 66});
    function mod_coursereadings_save_pagecount(form) {
        var formData = new FormData(form);
        var xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    var result = JSON.parse(xhr.responseText);
                    if (result) {
                        if (result.error == 0) {
                            newclass = 'coursereadings_article_pagecount_success';
                        } else {
                            alert(result.error);
                            newclass = 'coursereadings_article_pagecount_error';
                        }
                        document.querySelector('#scannedpages_'+result.id).className=newclass;
                    }
                } else {
                    alert(M.util.get_string('servererror', 'moodle'));
                    document.querySelector('#scannedpages_'+result.id).className='coursereadings_article_pagecount_error';
                }
            }
        };

        formData.append('sesskey', M.cfg.sesskey);
        formData.append('t', 'pagecount');

        // Send the AJAX call
        xhr.open("POST", M.cfg.wwwroot + '/mod/coursereadings/manage/ajax.php', true);
        xhr.send(formData);
    }
</script>
<?php
echo $OUTPUT->footer();

