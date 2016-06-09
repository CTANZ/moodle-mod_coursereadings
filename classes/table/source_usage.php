<?php
namespace mod_coursereadings\table;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

class source_usage extends \table_sql {
    function col_title($row) {
        global $CFG;
        $download = $this->is_downloading();

        if (!empty($download) && $download !== 'xhtml') {
            // Plain text for downloads.
            return $row->title;
        }

        $url = new \moodle_url('/mod/coursereadings/manage/edit-source.php', array('id'=> $row->id));
        return \html_writer::link($url, $row->title);
    }
    function col_shortnames($row) {
        $download = $this->is_downloading();

        if (!empty($download) && $download !== 'xhtml') {
            // Plain text for downloads.
            return $row->shortnames;
        }

        return $row->courses;
    }
}