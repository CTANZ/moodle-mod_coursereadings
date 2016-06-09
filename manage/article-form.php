<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Upload a CSV file with source information.
 */
class coursereadings_editarticle_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('editarticle', 'mod_coursereadings'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('text', 'title', get_string('title_of_article', 'mod_coursereadings'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addElement('text', 'periodicalAuthor', get_string('author_of_periodical', 'mod_coursereadings'));
        $mform->setType('periodicalAuthor', PARAM_TEXT);
        $mform->addElement('text', 'pagerange', get_string('page_range', 'mod_coursereadings'));
        $mform->setType('pagerange', PARAM_TEXT);
        $mform->addElement('text', 'totalpages', get_string('total_pages', 'mod_coursereadings'));
        $mform->setType('totalpages', PARAM_INT);
        $mform->addElement('text', 'externalurl', get_string('externalurl', 'mod_coursereadings'));
        $mform->setType('externalurl', PARAM_URL);
        $mform->addElement('text', 'doi', get_string('doi', 'mod_coursereadings'));
        $mform->setType('doi', PARAM_TEXT);
        $mform->addElement('hidden', 'source');
        $mform->setType('source', PARAM_TEXT);
        $mform->addElement('text', 'sourcedisplay', get_string('source', 'mod_coursereadings'));
        $mform->setType('sourcedisplay', PARAM_TEXT);

        $mform->addElement('filepicker', 'newfile', get_string('newfile', 'mod_coursereadings'));

        // Instances / usage.
        $label = html_writer::tag('label', html_writer::tag('strong', get_string('articleusage', 'mod_coursereadings')));
        $label = html_writer::tag('div', $label, array('class'=>'fitemtitle'));
        $content = html_writer::tag('div', $this->_customdata['instances'], array('class'=>'felement'));
        $mform->addElement('html', html_writer::tag('div', $label.$content, array('class'=>'fitem', 'style'=>'margin-top:20px;')));

        $label = html_writer::tag('div', '', array('class'=>'fitemtitle'));
        $articleid = required_param('id', PARAM_INT);
        $url = new moodle_url('/mod/coursereadings/manage/merge-article.php', array('id'=>$articleid, 'return'=>'find'));
        $content = html_writer::tag('div', html_writer::link($url, get_string('dashboard_mergearticle_link', 'mod_coursereadings')), array('class'=>'felement'));
        $mform->addElement('html', html_writer::tag('div', $label.$content, array('class'=>'fitem', 'style'=>'margin-top:20px;')));

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}