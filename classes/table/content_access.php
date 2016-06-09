<?php
namespace mod_coursereadings\table;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

class content_access extends \table_sql {
    function col_eventname($row) {
        global $CFG;
        $name = $row->eventname;

        switch($row->eventname) {
            case '\mod_coursereadings\event\coursereadings_article_downloaded':
                $name = get_string('event_article_downloaded', 'mod_coursereadings');
                break;
            case '\mod_coursereadings\event\coursereadings_bundled_pdf_downloaded':
                $name = get_string('event_bundled_pdf_downloaded', 'mod_coursereadings');
                break;
            case '\mod_coursereadings\event\coursereadings_bundled_zip_downloaded':
                $name = get_string('event_bundled_zip_downloaded', 'mod_coursereadings');
                break;
            case '\mod_url\event\course_module_viewed':
                $name = get_string('url_resource_viewed', 'mod_coursereadings');
                break;
        }

        return $name;
    }
}