<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Define all the backup steps that will be used by the backup_resource_activity_task
 *
 * @package    mod_coursereadings
 * @copyright  2014 Paul Nicholls
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete coursreeadings structure for backup, with file and id annotations
 */
class backup_coursereadings_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $coursereadings = new backup_nested_element('coursereadings', array('id'), array(
            'name', 'intro', 'introformat', 'display', 'timemodified'));

        $articles = new backup_nested_element('articles');

        $articleinstance = new backup_nested_element('article', array('id'), array('articleid'));
        // Build the tree
        $coursereadings->add_child($articles);
        $articles->add_child($articleinstance);

        // Define sources
        $coursereadings->set_source_table('coursereadings', array('id' => backup::VAR_ACTIVITYID));
        $articleinstance->set_source_table('coursereadings_inst_article',
                array('instanceid' => backup::VAR_PARENTID));
        $articleinstance->set_source_sql('
                SELECT articleid AS id, articleid
                FROM {coursereadings_inst_article}
                WHERE instanceid = :instanceid',
                array('instanceid' => backup::VAR_PARENTID));

        // Define id annotations
        // (none)

        // Define file annotations
        $coursereadings->annotate_files('mod_coursereadings', 'intro', null); // This file areas haven't itemid

        // Return the root element (resource), wrapped into standard activity structure
        return $this->prepare_activity_structure($coursereadings);
    }
}
