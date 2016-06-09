<?php

if (!isset($CFG->requiremodintro)) {
$settings->add(new admin_setting_configcheckbox('coursereadings/requiremodintro',
                                                    get_string('requiremodintro', 'coursereadings'),
                                                    get_string('requiremodintro_desc', 'coursereadings'),
                                                    1));
}

$settings->add(new admin_setting_configtext('coursereadings/doiresolver',
                                                get_string('doiresolver', 'coursereadings'),
                                                get_string('doiresolver_desc', 'coursereadings'),
                                                'http://doi.org/',
                                                PARAM_URL));

$settings->add(new admin_setting_configtext('coursereadings/crossrefemail',
                                                get_string('crossrefemail', 'coursereadings'),
                                                get_string('crossrefemail_desc', 'coursereadings'),
                                                '',
                                                PARAM_EMAIL));

$settings->add(new admin_setting_configfile('coursereadings/copyrightnoticearticle',
                                            get_string('copyrightnoticearticle', 'coursereadings'),
                                            get_string('copyrightnoticearticle_desc', 'coursereadings'),
                                            $CFG->dirroot.'/mod/coursereadings/templates/copyright-article.pdf'));

$settings->add(new admin_setting_configcheckbox('coursereadings/enablecombined',
                                                get_string('enablecombined', 'coursereadings'),
                                                get_string('enablecombined_desc', 'coursereadings'),
                                                1));

$settings->add(new admin_setting_configfile('coursereadings/copyrightnoticecombined',
                                            get_string('copyrightnoticecombined', 'coursereadings'),
                                            get_string('copyrightnoticecombined_desc', 'coursereadings'),
                                            $CFG->dirroot.'/mod/coursereadings/templates/copyright-combined.pdf'));

$enrolplugins = core_component::get_plugin_list('enrol');
foreach ($enrolplugins as $enrolplugin => $path) {
    $enrolplugins[$enrolplugin] = get_string('pluginname', 'enrol_' . $enrolplugin);
}
$settings->add(new admin_setting_configmultiselect('coursereadings/trackedenrolmethods',
                                                    get_string('trackedenrolmethods', 'coursereadings'),
                                                    get_string('trackedenrolmethods_desc', 'coursereadings'),
                                                    array(),
                                                    $enrolplugins));

$settings->add(new admin_setting_configtext('coursereadings/trackedselfenrolpattern',
                                                get_string('trackedselfenrolpattern', 'coursereadings'),
                                                get_string('trackedselfenrolpattern_desc', 'coursereadings'),
                                                '',
                                                PARAM_TEXT));

$settings->add(new admin_setting_configtext('coursereadings/enroldecreasepercent',
                                                get_string('enroldecreasepercent', 'coursereadings'),
                                                get_string('enroldecreasepercent_desc', 'coursereadings'),
                                                10,
                                                PARAM_INT));

$settings->add(new admin_setting_configtext('coursereadings/enroldecreasethreshold',
                                                get_string('enroldecreasethreshold', 'coursereadings'),
                                                get_string('enroldecreasethreshold_desc', 'coursereadings'),
                                                10,
                                                PARAM_TEXT));

$options = array('idnumber' => get_string('idnumbercourse'), 'shortname' => get_string('shortnamecourse'));
$settings->add(new admin_setting_configselect('coursereadings/courseidfield',
                                                    get_string('courseidfield', 'coursereadings'),
                                                    get_string('courseidfield_desc', 'coursereadings'),
                                                    'idnumber',
                                                    $options));