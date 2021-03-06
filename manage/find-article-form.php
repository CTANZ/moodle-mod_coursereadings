<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Upload a CSV file with source information.
 */
class coursereadings_findarticle_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('findarticle', 'mod_coursereadings'));

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