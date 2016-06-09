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
 * Library to handle drag and drop course reading uploads
 *
 * @package    mod_coursereadings
 * @copyright  2013 Paul Nicholls
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->dirroot.'/repository/upload/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/dnduploadlib.php');

class coursereadings_dndupload_ajax_processor extends dndupload_ajax_processor {
    protected $metadata = null;
    /**
     * Set up some basic information needed to handle the upload
     *
     * @param int $courseid The ID of the course we are uploading to
     * @param int $section The section number we are uploading to
     * @param string $type The type of upload (as reported by the browser)
     * @param string $modulename The name of the module requested to handle this upload
     */
    public function __construct($courseid, $section, $type, $modulename, $metadata) {
        global $DB;

        parent::__construct($courseid, $section, $type, $modulename);
        $this->metadata = $metadata;
    }

    /**
     * Process the upload - creating the module in the course and returning the result to the browser
     *
     * @param string $displayname optional the name (from the browser) to give the course module instance
     * @param string $content optional the content of the upload (for non-file uploads)
     */
    public function process($displayname = null, $content = null) {
        require_capability('moodle/course:manageactivities', $this->context);

        if ($this->is_file_upload()) {
            require_capability('moodle/course:managefiles', $this->context);
            if ($content != null) {
                throw new moodle_exception('fileuploadwithcontent', 'moodle');
            }
        } else {
            if (empty($content)) {
                throw new moodle_exception('dnduploadwithoutcontent', 'moodle');
            }
        }

        require_sesskey();

        $this->displayname = $displayname;

        if ($this->is_file_upload()) {
            $this->handle_file_upload();
        } else {
            throw new coding_exception("Course Readings module should not be requested to handle non-file uploads");
        }
    }

    /**
     * Handle uploads containing files - create the course module, ask the upload repository
     * to process the file, ask the mod to set itself up, then return the result to the browser
     */
    protected function handle_file_upload() {
        global $CFG, $DB;

        if (!empty($this->metadata->articleid)) {
            // We have an article ID - no need to actually process the file.

            if (empty($this->displayname)) {
                // No display name given - use article title if available, "Article" string if not.
                $title = $DB->get_field('coursereadings_article', 'title', array('id' => $this->metadata->articleid));
                if ($title) {
                    $this->displayname = $title;
                } else {
                    $this->displayname = get_string('article', 'mod_coursereadings');
                }
            }
        } else if (!empty($this->metadata->draftitemid)) {
            // New file, already uploaded.
            $draftitemid = $this->metadata->draftitemid;
            if (empty($this->displayname)) {
                // No display name given - generate from draft file name.
                $params = array(
                    'component' => 'user',
                    'filearea' => 'draft',
                    'itemid' => $draftitemid,
                    'mimetype' => 'application/pdf'
                );
                $filename = $DB->get_field('files', 'filename', $params);
                $this->displayname = $this->display_name_from_file($filename);
            }
        } else {
            // No article or draft file specified - something's gone wrong.
            throw new coding_exception("No article or file specified.");
        }

        // Create a course module to hold the new instance.
        $this->create_course_module();

        // Ask the module to set itself up.
        $moduledata = $this->prepare_module_data($draftitemid);
        $instanceid = plugin_callback('mod', $this->module->name, 'dndupload', 'handle', array($moduledata), 'invalidfunction');
        if ($instanceid === 'invalidfunction') {
            throw new coding_exception("{$this->module->name} does not support drag and drop upload (missing {$this->module->name}_dndupload_handle function");
        }

        // Finish setting up the course module.
        $this->finish_setup_course_module($instanceid);
    }

    /**
     * Gather together all the details to pass on to the mod, so that it can initialise it's
     * own database tables
     *
     * @param int $draftitemid optional the id of the draft area containing the file (for file uploads)
     * @param string $content optional the content dropped onto the course (for non-file uploads)
     * @return object data to pass on to the mod, containing:
     *              string $type the 'type' as registered with dndupload_handler (or 'Files')
     *              object $course the course the upload was for
     *              int $draftitemid optional the id of the draft area containing the files
     *              int $coursemodule id of the course module that has already been created
     *              string $displayname the name to use for this activity (can be overriden by the mod)
     */
    protected function prepare_module_data($draftitemid = null, $content = null) {
        $data = new stdClass();
        $data->type = $this->type;
        $data->course = $this->course;
        if ($draftitemid) {
            $data->draftitemid = $draftitemid;
        } else if ($content) {
            $data->content = $content;
        }
        $data->coursemodule = $this->cm->id;
        $data->displayname = $this->displayname;
        if ($this->module->name === 'coursereadings') {
            // Add metadata for module to process.
            $data->metadata = $this->metadata;
        }
        return $data;
    }
}
