<?php

namespace mod_coursereadings\table;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

class report_periods extends \table_sql {
    function col_moveup($row) {
        global $OUTPUT;
        if ($row->sortorder == 0) {
            return '';
        }
        $newpos = $row->sortorder - 1;
        $url = new \moodle_url('/mod/coursereadings/manage/reportperiods.php', array('id' => $row->id, 'action' => 'move', 'pos' => $newpos));
        $icon = $OUTPUT->pix_icon('i/up', get_string('moveup'));
        return \html_writer::link($url, $icon);
    }
    function col_movedown($row) {
        global $OUTPUT;
        if ($row->sortorder == $row->last) {
            return '';
        }
        $newpos = $row->sortorder + 1;
        $url = new \moodle_url('/mod/coursereadings/manage/reportperiods.php', array('id' => $row->id, 'action' => 'move', 'pos' => $newpos));
        $icon = $OUTPUT->pix_icon('i/down', get_string('movedown'));
        return \html_writer::link($url, $icon);
    }
    function col_edit($row) {
        global $OUTPUT;
        $url = new \moodle_url('/mod/coursereadings/manage/reportperiod.php', array('id' => $row->id));
        $icon = $OUTPUT->pix_icon('i/edit', get_string('edit'));
        return \html_writer::link($url, $icon);
    }
    function col_delete($row) {
        global $OUTPUT;
        $url = new \moodle_url('/mod/coursereadings/manage/reportperiods.php', array('id' => $row->id, 'action' => 'delete'));
        $icon = $OUTPUT->pix_icon('i/delete', get_string('delete'));
        return \html_writer::link($url, $icon);
    }
    public function get_sql_sort() {
        return 'sortorder ASC';
    }
}
