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
 * Course Material module post install function
 *
 * This file replaces:
 *  - STATEMENTS section in db/install.xml
 *  - lib.php/modulename_install() post installation hook
 *  - partially defaults.php
 *
 * @package    mod
 * @subpackage coursereadings
 * @copyright  2011 Paul Nicholls
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_coursereadings_install() {
    global $DB;

    $dbman = $DB->get_manager();

    // Define index idx_courseread_src_title (not unique) to be added to coursereadings_source.
    $table = new xmldb_table('coursereadings_source');
    $index = new xmldb_index('idx_courseread_src_title', XMLDB_INDEX_NOTUNIQUE, array('title(255)'));

    // Conditionally launch add index idx_courseread_src_title.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define index idx_courseread_art_title (not unique) to be added to coursereadings_source.
    $table = new xmldb_table('coursereadings_article');
    $index = new xmldb_index('idx_courseread_art_title', XMLDB_INDEX_NOTUNIQUE, array('title(255)'));

    // Conditionally launch add index idx_courseread_art_title.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define index idx_courseread_art_title (not unique) to be added to coursereadings_source.
    $table = new xmldb_table('coursereadings_article');
    $index = new xmldb_index('idx_courseread_art_exturl', XMLDB_INDEX_NOTUNIQUE, array('externalurl(255)'));

    // Conditionally launch add index idx_courseread_art_title.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define index idx_courseread_art_title (not unique) to be added to coursereadings_source.
    $table = new xmldb_table('coursereadings_article');
    $index = new xmldb_index('idx_courseread_art_doi', XMLDB_INDEX_NOTUNIQUE, array('doi(255)'));

    // Conditionally launch add index idx_courseread_art_title.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define index idx_courseread_enr_cid_enr (not unique) to be added to coursereadings_enrolments.
    $table = new xmldb_table('coursereadings_enrolments');
    $index = new xmldb_index('idx_courseread_enr_cid_enr', XMLDB_INDEX_NOTUNIQUE, array('courseid, enrolments'));

    // Conditionally launch add index idx_courseread_enr_cid_enr.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }
}
