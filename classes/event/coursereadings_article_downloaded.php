<?php
namespace mod_coursereadings\event;
defined('MOODLE_INTERNAL') || die();
class coursereadings_article_downloaded extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'coursereadings';
    }

    public static function get_name() {
        return get_string('event_article_downloaded', 'mod_coursereadings');
    }

    public function get_description() {
        $fileLink = new \moodle_url("/pluginfile.php/".$this->contextid."/mod_coursereadings/articles/".$this->other['articleid']."/".$this->other['filename']."?forcedownload=1");
        return "The user with id {$this->userid} downloaded article <a href='{$fileLink}'>{$this->other['filename']}</a>.";
    }

    public function get_url() {
        return new \moodle_url("/pluginfile.php/".$this->contextid."/mod_coursereadings/articles/".$this->other['articleid']."/".$this->other['filename'], array('forcedownload' => '1'));
    }

    public function get_legacy_logdata() {
        return array(
            $this->courseid,
            'coursereadings',
            'article downloaded',
            "view.php?id=".$this->objectid,
            $this->other['filename'],
            $this->contextinstanceid
        );
    }
}