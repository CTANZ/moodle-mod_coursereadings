<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Search form for source merge.
 */
class coursereadings_mergesource_search_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('hidden', 'return');
        $mform->setType('return', PARAM_ALPHANUM);

        $mform->addElement('header', 'settingsheader', get_string('dashboard_mergesource_duplicate', 'mod_coursereadings'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('static', 'type', get_string('source_type_editing', 'mod_coursereadings'));
        $mform->addElement('static', 'title', get_string('title_of_source', 'mod_coursereadings'));
        $mform->addElement('static', 'static_isbn', get_string('isbn', 'mod_coursereadings'));
        $mform->addElement('static', 'pages', get_string('pages', 'mod_coursereadings'));

        $mform->addElement('html', html_writer::tag('p', get_string('dashboard_mergesource_intro', 'mod_coursereadings')));


        $mform->addElement('header', 'settingsheader', get_string('dashboard_mergesource_findtarget', 'mod_coursereadings'));

        $mform->addElement('text', 'sourcetitle', get_string('title_of_source', 'mod_coursereadings'), array('class'=>'ignoredirty'));
        $mform->setType('sourcetitle', PARAM_TEXT);
        $mform->addElement('text', 'isbn', get_string('isbn', 'mod_coursereadings'), array('class'=>'ignoredirty'));
        $mform->setType('isbn', PARAM_TEXT);

        $label = html_writer::tag('label', html_writer::tag('strong', get_string('searchresults')));
        $label = html_writer::tag('div', $label, array('class'=>'fitemtitle'));
        $content = html_writer::tag('div', '&nbsp;', array('class'=>"article_search_results", 'style'=>"display:none;", 'id'=>"article_search_results"));
        $content .= html_writer::tag('div', get_string('noresults_source', 'mod_coursereadings'), array('class'=>"article_search_noresults", 'id'=>"article_search_noresults"));
        $content = html_writer::tag('div', $content, array('class'=>'felement'));
        $mform->addElement('html', html_writer::tag('div', $label.$content, array('class'=>'fitem', 'style'=>'margin-top:20px;')));
    }
}

/**
 * Editing / confirmation form for source merge.
 */
class coursereadings_mergesource_merge_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('hidden', 'return');
        $mform->setType('return', PARAM_ALPHANUM);
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'target');
        $mform->setType('target', PARAM_INT);

        $mform->addElement('html', '<div class="coursereadings-mergesource-merge-form">');
        $mform->addElement('header', 'settingsheader', get_string('dashboard_mergesource_target', 'mod_coursereadings'));

        $group = array();
        $typearray = array(
            'book' => get_string('source_book', 'mod_coursereadings'),
            'journal' => get_string('source_journal', 'mod_coursereadings'),
            'other' => get_string('source_other', 'mod_coursereadings')
        );
        $group[] =& $mform->createElement('select', 'type', get_string('source_type_editing', 'mod_coursereadings'), $typearray, array('style'=>'width:220px;'));
        $group[] =& $mform->createElement('static', 'static_type', '');
        $mform->addGroup($group, 'typegrp', get_string('source_type_editing', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'title', get_string('title_of_source', 'mod_coursereadings'));
        $mform->setType('title', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_title', '');
        $mform->addGroup($group, 'titlegrp', get_string('title_of_source', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'author', get_string('author_of_source', 'mod_coursereadings'));
        $mform->setType('author', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_author', '');
        $mform->addGroup($group, 'authorgrp', get_string('author_of_source', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'editor', get_string('editor_of_source', 'mod_coursereadings'));
        $mform->setType('editor', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_editor', '');
        $mform->addGroup($group, 'editorgrp', get_string('editor_of_source', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'year', get_string('year_of_publication', 'mod_coursereadings'));
        $mform->setType('year', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_year', '');
        $mform->addGroup($group, 'yeargrp', get_string('year_of_publication', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'volume', get_string('volume_number', 'mod_coursereadings'));
        $mform->setType('volume', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_volume', '');
        $mform->addGroup($group, 'volumegrp', get_string('volume_number', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'edition', get_string('edition', 'mod_coursereadings'));
        $mform->setType('edition', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_edition', '');
        $mform->addGroup($group, 'editiongrp', get_string('edition', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'publisher', get_string('publisher', 'mod_coursereadings'));
        $mform->setType('publisher', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_publisher', '');
        $mform->addGroup($group, 'publishergrp', get_string('publisher', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'isbn', get_string('isbn', 'mod_coursereadings'));
        $mform->setType('isbn', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_isbn', '');
        $mform->addGroup($group, 'isbngrp', get_string('isbn', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'pages', get_string('pages', 'mod_coursereadings'));
        $mform->setType('pages', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_pages', '');
        $mform->addGroup($group, 'pagesgrp', get_string('pages', 'mod_coursereadings'), array(' '), false);

        $subtypes = explode(',', get_string('source_subtypes', 'mod_coursereadings'));
        $options = array('' => 'Please select');
        foreach ($subtypes as $subtype) {
            $options[$subtype] = $subtype;
        }
        $group = array();
        $group[] =& $mform->createElement('select', 'subtype', get_string('source_subtype', 'mod_coursereadings'), $options, array('style'=>'width:220px;'));
        $group[] =& $mform->createElement('static', 'static_subtype', '');
        $mform->addGroup($group, 'subtypegrp', get_string('source_subtype', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'furtherinfo', get_string('furtherinfo', 'mod_coursereadings'));
        $mform->setType('furtherinfo', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_furtherinfo', '');
        $mform->addGroup($group, 'furtherinfogrp', get_string('furtherinfo', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'externalurl', get_string('sourceurl', 'mod_coursereadings'));
        $mform->setType('externalurl', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_externalurl', '');
        $mform->addGroup($group, 'externalurlgrp', get_string('sourceurl', 'mod_coursereadings'), array(' '), false);

        $mform->addElement('html', '</div>');

        $this->add_action_buttons(true, get_string('dashboard_mergesource', 'mod_coursereadings'));
    }
}