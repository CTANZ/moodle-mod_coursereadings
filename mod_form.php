<?php
require_once($CFG->dirroot.'/mod/coursereadings/lib.php');
require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->libdir.'/formslib.php');//putting this is as a safety as i got a class not found error.
MoodleQuickForm::registerElementType('coursereadings_article_chooser', dirname(__FILE__).'/classes/articlechooser.class.php', 'MoodleQuickForm_coursereadings_article_chooser');

class mod_coursereadings_mod_form extends moodleform_mod {

    function definition() {

        global $CFG, $COURSE, $OUTPUT;
        $mform =& $this->_form;
        $config = get_config('coursereadings');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $donelabel = false;
        $i = 0;

        $mform->addElement('coursereadings_article_chooser', 'articles', get_string('articles', 'mod_coursereadings'));

        $this->standard_coursemodule_elements(array('groups'=>false, 'groupmembersonly'=>true, 'gradecat'=>false));

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values){
        global $COURSE, $DB;
        $mform =& $this->_form;
        if (!empty($this->_instance) && ($articles = $DB->get_records_sql('SELECT a.*, a.id AS articleid, s.title AS sourcetitle, s.author AS sourceAuthor, s.year FROM ({coursereadings_inst_article} ia INNER JOIN {coursereadings_article} a ON ia.articleid=a.id) INNER JOIN {coursereadings_source} s ON a.source=s.id WHERE ia.instanceid = :instanceid ORDER BY ia.id ASC', array('instanceid'=>$this->_instance)))) {
            $ids = array();
            $data = array();
            foreach ($articles as $article) {
                $ids[] = $article->id;
                $data[] = $article;
            }
            $mform->getElement('articles')->setArticles($data);
            $default_values['articles'] = implode(',', $ids);
        }
    }

}