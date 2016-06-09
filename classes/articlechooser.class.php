<?php

require_once("HTML/QuickForm/hidden.php");

/**
 * Article chooser for Course Readings module
 *
 * @author       Paul Nicholls
 * @version      1.0
 * @access       public
 */
class MoodleQuickForm_coursereadings_article_chooser extends HTML_QuickForm_hidden {
    private $hiddenEl;
    private $articles = array();
    function MoodleQuickForm_coursereadings_article_chooser($elementName=null, $label='', $attributes=null) {
        HTML_QuickForm_input::HTML_QuickForm_input($elementName, $label, $attributes);
        $this->hiddenEl = new HTML_QuickForm_hidden($elementName, '', $attributes);
    }

    function freeze() {
        return false;
    }

    function setValue($value) {
        $this->hiddenEl->setValue($value);
    }

    function setArticles($articles) {
        $this->articles = $articles;
    }

    function accept(&$renderer, $required=false, $error=null) {
        $renderer->renderHidden($this->hiddenEl);
        $renderer->renderElement($this, $required, $error);
    }

    function toHtml() {
        global $CFG, $OUTPUT, $PAGE, $COURSE;

        require_once($CFG->dirroot.'/repository/lib.php');

        $args = new stdClass();
        $args->accepted_types = array('.pdf');
        $args->return_types = FILE_INTERNAL;

        if ($COURSE->id == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($COURSE->id);
        }
        $args->context = $context;

        $fpoptions = initialise_filepicker($args);
        $fpoptions->itemid = file_get_unused_draft_itemid();
        $fpoptions->client_id = uniqid();
        $fpoptions->context = $context;
        $fpoptions->env = 'filepicker';

        $PAGE->requires->yui_module('moodle-mod_coursereadings-articlechooser', 'M.mod_coursereadings.articlechooser.init', array(array('fpoptions'=>$fpoptions)));
        $PAGE->requires->strings_for_js(array('loading', 'articleuploadintro', 'articlelinkintro', 'choosefile', 'confirmRemoveArticle', 'addarticle', 'articlesearchintro', 'noresults', 'noresults_external', 'title_of_article', 'source_type', 'source_book', 'source_journal', 'source_other', 'source_subtype', 'source_subtypes', 'furtherinfo', 'journal_notice', 'title_of_source', 'author_of_periodical', 'author_of_source', 'editor_of_source', 'year_of_publication', 'publisher', 'isbn', 'volume_number', 'edition', 'page_range', 'pages', 'sourceurl', 'externalurl', 'doi', 'doi_or_url'), 'mod_coursereadings');
        $PAGE->requires->strings_for_js(array('upload', 'dndenabled_inbox', 'next'), 'moodle');
        $PAGE->requires->strings_for_js(array('openpicker'), 'repository');


        $data = html_writer::tag('div', json_encode($this->articles), array('class'=>'coursereadings-articles-json'));
        $html = html_writer::tag('div', $data.$OUTPUT->pix_icon('i/loading_small', '').' '.get_string('loading', 'mod_coursereadings'), array('class'=>'coursereadings-articles-loading'));
        $html = html_writer::tag('div', $html, array('class'=>'coursereadings-article-list'));
        $html = html_writer::tag('div', $html, array('class'=>'coursereadings-article-chooser', 'id'=>'coursereadings-article-chooser_'.$this->getName(), 'data-fieldname'=>$this->getName()));
        return $html;
    }
}