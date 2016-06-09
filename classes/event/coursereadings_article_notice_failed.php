<?php
namespace mod_coursereadings\event;
defined('MOODLE_INTERNAL') || die();
class coursereadings_article_notice_failed extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'coursereadings_article';
    }

    public static function get_name() {
        return get_string('event_article_notice_failed', 'mod_coursereadings');
    }

    public function get_description() {
        $fileLink = new \moodle_url("/pluginfile.php/".$this->contextid."/mod_coursereadings/articles/".$this->other['articleid']."/".$this->other['filename']."?forcedownload=1");
        return "The copyright warning notice could not be prepended to the file <a href='{$fileLink}'>{$this->other['filename']}</a> for the user with id {$this->userid}.";
    }

    public function get_url() {
        return new \moodle_url("/pluginfile.php/".$this->contextid."/mod_coursereadings/articles/".$this->other['articleid']."/".$this->other['filename'], array('forcedownload' => '1'));
    }

    public function get_legacy_logdata() {
        return array(
            $this->courseid,
            'coursereadings',
            'article warning notice failed',
            "view.php?id=".$this->objectid,
            $this->other['filename'],
            $this->contextinstanceid
        );
    }
}