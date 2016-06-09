<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Upload a CSV file with source information.
 */
class coursereadings_editsource_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('editsource', 'mod_coursereadings'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $radioarray=array();
        $radioarray[] =& $mform->createElement('radio', 'type', '', get_string('source_book', 'mod_coursereadings'), 'book');
        $radioarray[] =& $mform->createElement('radio', 'type', '', get_string('source_journal', 'mod_coursereadings'), 'journal');
        $radioarray[] =& $mform->createElement('radio', 'type', '', get_string('source_other', 'mod_coursereadings'), 'other');
        $mform->addGroup($radioarray, 'radioar', get_string('source_type_editing', 'mod_coursereadings'), array(' '), false);
        $mform->addElement('text', 'title', get_string('title_of_source', 'mod_coursereadings'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addElement('text', 'author', get_string('author_of_source', 'mod_coursereadings'));
        $mform->setType('author', PARAM_TEXT);
        $mform->addElement('text', 'editor', get_string('editor_of_source', 'mod_coursereadings'));
        $mform->setType('editor', PARAM_TEXT);
        $mform->addElement('text', 'year', get_string('year_of_publication', 'mod_coursereadings'));
        $mform->setType('year', PARAM_TEXT);
        $mform->addElement('text', 'volume', get_string('volume_number', 'mod_coursereadings'));
        $mform->setType('volume', PARAM_TEXT);
        $mform->addElement('text', 'edition', get_string('edition', 'mod_coursereadings'));
        $mform->setType('edition', PARAM_TEXT);
        $mform->addElement('text', 'publisher', get_string('publisher', 'mod_coursereadings'));
        $mform->setType('publisher', PARAM_TEXT);
        $mform->addElement('text', 'isbn', get_string('isbn', 'mod_coursereadings'));
        $mform->setType('isbn', PARAM_TEXT);
        $mform->addElement('text', 'pages', get_string('pages', 'mod_coursereadings'));
        $mform->setType('pages', PARAM_TEXT);
        $subtypes = explode(',', get_string('source_subtypes', 'mod_coursereadings'));
        $options = array('' => 'Please select');
        foreach ($subtypes as $subtype) {
            $options[$subtype] = $subtype;
        }
        $mform->addElement('select', 'subtype', get_string('source_subtype', 'mod_coursereadings'), $options);
        $mform->addElement('text', 'furtherinfo', get_string('furtherinfo', 'mod_coursereadings'));
        $mform->setType('furtherinfo', PARAM_TEXT);
        $mform->addElement('text', 'externalurl', get_string('sourceurl', 'mod_coursereadings'));
        $mform->setType('externalurl', PARAM_URL);

        $label = html_writer::tag('label', html_writer::tag('strong', get_string('articlesfromsource', 'mod_coursereadings')));
        $label = html_writer::tag('div', $label, array('class'=>'fitemtitle'));
        $content = html_writer::tag('div', $this->_customdata['articles'], array('class'=>'felement'));
        $mform->addElement('html', html_writer::tag('div', $label.$content, array('class'=>'fitem', 'style'=>'margin-top:20px;')));

        $label = html_writer::tag('div', '', array('class'=>'fitemtitle'));
        $sourceid = required_param('id', PARAM_INT);
        $url = new moodle_url('/mod/coursereadings/manage/merge-source.php', array('id'=>$sourceid, 'return'=>'find'));
        $content = html_writer::tag('div', html_writer::link($url, get_string('dashboard_mergesource_link', 'mod_coursereadings')), array('class'=>'felement'));
        $mform->addElement('html', html_writer::tag('div', $label.$content, array('class'=>'fitem', 'style'=>'margin-top:20px;')));

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}