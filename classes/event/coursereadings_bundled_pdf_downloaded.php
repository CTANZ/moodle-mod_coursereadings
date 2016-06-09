<?php
namespace mod_coursereadings\event;
defined('MOODLE_INTERNAL') || die();
class coursereadings_bundled_pdf_downloaded extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'coursereadings';
    }

    public static function get_name() {
        return get_string('event_bundled_pdf_downloaded', 'mod_coursereadings');
    }

    public function get_description() {
        return "The user with id {$this->userid} downloaded a bundled pdf file.";
    }

    public function get_url() {
        return new \moodle_url('/mod/coursereadings/index.php', array('id' => $this->objectid));
    }

    public function get_legacy_logdata() {
        return array(
            $this->courseid,
            'coursereadings',
            'bundled pdf downloaded',
            "index.php?id=".$this->objectid,
            $this->objectid,
            $this->contextinstanceid
        );
    }
}