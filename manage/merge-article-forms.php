<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Search form for article merge.
 */
class coursereadings_mergearticle_search_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('hidden', 'return');
        $mform->setType('return', PARAM_ALPHANUM);

        $mform->addElement('header', 'settingsheader', get_string('dashboard_mergearticle_duplicate', 'mod_coursereadings'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('static', 'static_articletitle', get_string('title_of_article', 'mod_coursereadings'));
        $mform->addElement('static', 'static_pagerange', get_string('page_range', 'mod_coursereadings'));
        $mform->addElement('static', 'static_sourcetitle', get_string('title_of_source', 'mod_coursereadings'));
        $mform->addElement('static', 'static_isbn', get_string('isbn', 'mod_coursereadings'));
        $mform->addElement('static', 'static_pages', get_string('pages', 'mod_coursereadings'));

        $mform->addElement('html', html_writer::tag('p', get_string('dashboard_mergearticle_intro', 'mod_coursereadings')));


        $mform->addElement('header', 'settingsheader', get_string('dashboard_mergearticle_findtarget', 'mod_coursereadings'));

        $mform->addElement('text', 'title', get_string('title_of_article', 'mod_coursereadings'), array('class'=>'ignoredirty'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addElement('text', 'external', get_string('doi_or_url', 'mod_coursereadings'), array('class'=>'ignoredirty'));
        $mform->setType('external', PARAM_TEXT);
        $mform->addElement('text', 'sourcetitle', get_string('title_of_source', 'mod_coursereadings'), array('class'=>'ignoredirty'));
        $mform->setType('sourcetitle', PARAM_TEXT);
        $mform->addElement('text', 'isbn', get_string('isbn', 'mod_coursereadings'), array('class'=>'ignoredirty'));
        $mform->setType('isbn', PARAM_TEXT);

        $label = html_writer::tag('label', html_writer::tag('strong', get_string('searchresults')));
        $label = html_writer::tag('div', $label, array('class'=>'fitemtitle'));
        $content = html_writer::tag('div', '&nbsp;', array('class'=>"article_search_results", 'style'=>"display:none;", 'id'=>"article_search_results"));
        $content .= html_writer::tag('div', get_string('noresults_brief', 'mod_coursereadings'), array('class'=>"article_search_noresults", 'id'=>"article_search_noresults"));
        $content .= html_writer::tag('div', get_string('noarticlesinsource_brief', 'mod_coursereadings'), array('class'=>"article_search_noresults", 'id'=>"article_search_noarticles", 'style'=>"display:none;"));
        $content = html_writer::tag('div', $content, array('class'=>'felement'));
        $mform->addElement('html', html_writer::tag('div', $label.$content, array('class'=>'fitem', 'style'=>'margin-top:20px;')));
    }
}

/**
 * Editing / confirmation form for article merge.
 */
class coursereadings_mergearticle_merge_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('hidden', 'return');
        $mform->setType('return', PARAM_ALPHANUM);
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'target');
        $mform->setType('target', PARAM_INT);

        $mform->addElement('html', '<div class="coursereadings-mergearticle-merge-form">');
        $mform->addElement('header', 'settingsheader', get_string('dashboard_mergearticle_target', 'mod_coursereadings'));

        $group = array();
        $group[] =& $mform->createElement('text', 'title', get_string('title_of_article', 'mod_coursereadings'));
        $mform->setType('title', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_title', '');
        $mform->addGroup($group, 'titlegrp', get_string('title_of_article', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'periodicalAuthor', get_string('author_of_periodical', 'mod_coursereadings'));
        $mform->setType('periodicalAuthor', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_periodicalAuthor', '');
        $mform->addGroup($group, 'periodicalAuthorgrp', get_string('author_of_periodical', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'pagerange', get_string('page_range', 'mod_coursereadings'));
        $mform->setType('pagerange', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_pagerange', '');
        $mform->addGroup($group, 'pagerangegrp', get_string('page_range', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'totalpages', get_string('total_pages', 'mod_coursereadings'));
        $mform->setType('totalpages', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_totalpages', '');
        $mform->addGroup($group, 'totalpagesgrp', get_string('total_pages', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'externalurl', get_string('externalurl', 'mod_coursereadings'));
        $mform->setType('externalurl', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_externalurl', '');
        $mform->addGroup($group, 'externalurlgrp', get_string('externalurl', 'mod_coursereadings'), array(' '), false);

        $group = array();
        $group[] =& $mform->createElement('text', 'doi', get_string('doi', 'mod_coursereadings'));
        $mform->setType('doi', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_doi', '');
        $mform->addGroup($group, 'doigrp', get_string('doi', 'mod_coursereadings'), array(' '), false);

        $mform->addElement('hidden', 'source');
        $mform->setType('source', PARAM_INT);
        $group = array();
        $group[] =& $mform->createElement('text', 'sourcedisplay', get_string('source', 'mod_coursereadings'));
        $mform->setType('sourcedisplay', PARAM_TEXT);
        $group[] =& $mform->createElement('static', 'static_sourcedisplay', '');
        $mform->addGroup($group, 'sourcedisplaygrp', get_string('source', 'mod_coursereadings'), array(' '), false);

        $mform->addElement('hidden', 'keepfile');
        $mform->setType('keepfile', PARAM_TEXT);
        $group = array();
        $group[] =& $mform->createElement('static', 'cfile', '');
        $group[] =& $mform->createElement('static', 'dfile', '');
        $mform->addGroup($group, 'keepfilegrp', get_string('dashboard_mergearticle_file', 'mod_coursereadings'), array(' '), false);

        $mform->addElement('html', '</div>');

        $this->add_action_buttons(true, get_string('dashboard_mergearticle', 'mod_coursereadings'));
    }
}