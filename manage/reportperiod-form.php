<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Add/edit a reporting period.
 */
class coursereadings_reportperiod_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('dashboard_reportperiod', 'mod_coursereadings'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sortorder');
        $mform->setType('sortorder', PARAM_INT);
        $mform->addElement('text', 'name', get_string('reportperiod_name', 'mod_coursereadings'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('text', 'pattern', get_string('reportperiod_pattern', 'mod_coursereadings'));
        $mform->setType('pattern', PARAM_RAW);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}