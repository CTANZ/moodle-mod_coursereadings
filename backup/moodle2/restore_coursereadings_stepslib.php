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
 * @package mod_coursereadings
 * @copyright 2014 Paul Nicholls
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_coursereadings_activity_task
 */

/**
 * Structure step to restore one coursereadings activity
 */
class restore_coursereadings_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('coursereadings', '/activity/coursereadings');
        $paths[] = new restore_path_element('coursereadings_article', '/activity/coursereadings/articles/article');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_coursereadings($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the resource record
        $newitemid = $DB->insert_record('coursereadings', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_coursereadings_article($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        unset($data->id);

        $data->instanceid = $this->get_new_parentid('coursereadings');

        $DB->insert_record('coursereadings_inst_article', $data);
    }

    protected function after_execute() {
        // Add choice related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_coursereadings', 'intro', null);
    }
}
