<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';
MoodleQuickForm::registerElementType('coursereadings_article_chooser',
        $CFG->dirroot.'/mod/coursereadings/classes/articlechooser.class.php', 'MoodleQuickForm_coursereadings_article_chooser');

/**
 * Form for merging split articles.
 */
class coursereadings_mergesplitarticle_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('dashboard_articlestomerge', 'mod_coursereadings'));

        $intro = html_writer::tag('p', get_string('dashboard_articlestomerge_intro', 'mod_coursereadings'));
        $mform->addElement('html', $intro);
        $mform->addElement('coursereadings_article_chooser', 'articles', get_string('articles', 'mod_coursereadings'));
        $mform->addElement('text', 'newfilename', get_string('dashboard_newfilename', 'mod_coursereadings'));
        $mform->setType('newfilename', PARAM_FILE);
        $mform->addRule('newfilename', null, 'required');

        $this->add_action_buttons(true, get_string('dashboard_mergesplitarticle', 'coursereadings'));
    }
}