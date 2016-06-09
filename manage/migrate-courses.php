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
 * Course PDFs report.
 *
 * Provides links to all PDFs actively used in courses (visible to students)
 *
 * @package report_coursepdfs
 * @copyright 2013 Paul Nicholls
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$categoryid = optional_param('category', 0, PARAM_INT);

require_login();
$syscontext = context_system::instance();
require_capability('mod/coursereadings:migratecontent', $syscontext);
$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/coursereadings/manage/migrate-courses.php');
$PAGE->set_pagelayout('base');

// Read standard strings.
$strmaterials = get_string('modulenameplural', 'coursereadings');
$strdashboard  = get_string('dashboard', 'coursereadings');

$PAGE->navbar->add($strmaterials, new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/index.php"));
$PAGE->navbar->add(get_string('contentmigration', 'coursereadings'), new moodle_url("$CFG->wwwroot/mod/coursereadings/manage/migrate-courses.php"));
$PAGE->set_title($strmaterials);
$PAGE->set_heading($strdashboard);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');
echo $OUTPUT->header();

if (empty($categoryid)) {
	// No category selected - list all categories
	echo $OUTPUT->heading('Course Materials: Content Migration - select category');

    $separator = ' / ';
    require_once($CFG->libdir.'/coursecatlib.php');
    $allcategories = coursecat::make_categories_list('mod/coursereadings:migratecontent', 0, $separator);
    $depth = 1;
    echo '<ul>';
    foreach ($allcategories as $id=>$name) {
        $nameParts = explode($separator, $name);
        $thisDepth = count($nameParts);

        while ($thisDepth > $depth) {
            echo "<ul>";
            $depth++;
        }
        while ($thisDepth < $depth) {
            echo "</ul>";
            $depth--;
        }
        echo '<li><a href="'.$CFG->wwwroot.'/mod/coursereadings/manage/migrate-courses.php?category='.$id.'">'.$nameParts[count($nameParts)-1].'</a></li>';
    }
    while (0 < $depth) {
        echo "</ul>";
        $depth--;
    }


} else {
	echo $OUTPUT->heading('Course Materials: Content Migration - select course');
	$category = $DB->get_record('course_categories', array('id'=>$categoryid));

	// Get all subcategories
	$categories = $DB->get_records_select('course_categories', "path LIKE '".$category->path."/%'");
	$categoryids = array();
	array_push($categoryids, $category->id);
	if ($categories) {
	    foreach ($categories as $cat) {
	        array_push($categoryids, $cat->id);
	    }
	}

	// Get all courses in category + subcategories
	$courses = $DB->get_records_list('course', 'category', $categoryids, 'fullname ASC');

	echo '<ul>';
	foreach ($courses as $course) {
	    echo '<li><a href="'.$CFG->wwwroot.'/mod/coursereadings/manage/migrate-course-content.php?id='.$course->id.'">'.$course->fullname.'</a></li>';
	}
	echo '</ul>';
}

echo $OUTPUT->footer();