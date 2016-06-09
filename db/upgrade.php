<?php

// This file keeps track of upgrades to
// the Course Material module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_coursereadings_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2011042804) {
        // Switch from old "summary" field to "intro" and "introformat"
        $table = new xmldb_table('coursereadings');

        $field = new xmldb_field('summary', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'name');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'intro');
        }

        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'intro');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2011042804, 'coursereadings');
    }

    if ($oldversion < 2013072900) {
        // Define table coursereadings_source to be created
        $table = new xmldb_table('coursereadings_source');

        // Adding fields to table coursereadings_source
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '1024', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('author', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('year', XMLDB_TYPE_INTEGER, '8', null, null, null, null);
        $table->add_field('publisher', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('isbn', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pages', XMLDB_TYPE_INTEGER, '8', null, null, null, null);
        $table->add_field('editor', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('volume', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('edition', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table coursereadings_source
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for coursereadings_source
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // Define table coursereadings_article to be created
        $table = new xmldb_table('coursereadings_article');

        // Adding fields to table coursereadings_article
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '1024', null, XMLDB_NOTNULL, null, null);
        $table->add_field('author', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('pagerange', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('year', XMLDB_TYPE_INTEGER, '8', null, null, null, null);
        $table->add_field('source', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table coursereadings_article
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for coursereadings_article
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // Define table coursereadings_inst_article to be created
        $table = new xmldb_table('coursereadings_inst_article');

        // Adding fields to table coursereadings_inst_article
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('articleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table coursereadings_inst_article
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for coursereadings_inst_article
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2013072900, 'coursereadings');
    }
    if ($oldversion < 2013072903) {
        // Define table coursereadings_queue to be created
        $table = new xmldb_table('coursereadings_queue');

        // Adding fields to table coursereadings_queue
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, null);
        $table->add_field('objectid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table coursereadings_queue
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for coursereadings_queue
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // coursereadings savepoint reached
        upgrade_mod_savepoint(true, 2013072903, 'coursereadings');
    }
    if ($oldversion < 2013072904) {

        // Define table coursereadings_approval to be created
        $table = new xmldb_table('coursereadings_approval');

        // Adding fields to table coursereadings_approval
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sourceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('withinlimits', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('blanketapproval', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table coursereadings_approval
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table coursereadings_approval
        $table->add_index('coursereadings_approval_course_source_idx', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'sourceid'));

        // Conditionally launch create table for coursereadings_approval
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table coursereadings_appr_article to be created
        $table = new xmldb_table('coursereadings_appr_article');

        // Adding fields to table coursereadings_appr_article
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('approvalid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('articleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table coursereadings_appr_article
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for coursereadings_appr_article
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // coursereadings savepoint reached
        upgrade_mod_savepoint(true, 2013072904, 'coursereadings');
    }

    if ($oldversion < 2013072905) {

        // Define table coursereadings_noncopyright to be created
        $table = new xmldb_table('coursereadings_noncopyright');

        // Adding fields to table coursereadings_noncopyright
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeflagged', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('flaggedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table coursereadings_noncopyright
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for coursereadings_noncopyright
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // coursereadings savepoint reached
        upgrade_mod_savepoint(true, 2013072905, 'coursereadings');
    }

    if ($oldversion < 2013072906) {

        // Define table coursereadings_migrationnote to be created
        $table = new xmldb_table('coursereadings_migrationnote');

        // Adding fields to table coursereadings_migrationnote
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table coursereadings_migrationnote
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for coursereadings_migrationnote
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // coursereadings savepoint reached
        upgrade_mod_savepoint(true, 2013072906, 'coursereadings');
    }

    if ($oldversion < 2013072907) {

        // Define fields "subtype" and "furtherinfo" to be added to coursereadings_source.
        $table = new xmldb_table('coursereadings_source');
        $subtype = new xmldb_field('subtype', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'edition');
        $furtherinfo = new xmldb_field('furtherinfo', XMLDB_TYPE_CHAR, '1024', null, null, null, null, 'subtype');

        // Conditionally launch add field "subtype".
        if (!$dbman->field_exists($table, $subtype)) {
            $dbman->add_field($table, $subtype);
        }
        // Conditionally launch add field "furtherinfo".
        if (!$dbman->field_exists($table, $furtherinfo)) {
            $dbman->add_field($table, $furtherinfo);
        }

        // Changing type of field year on table coursereadings_source to char
        $table = new xmldb_table('coursereadings_source');
        $field = new xmldb_field('year', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'author');

        // Launch change of type for field year
        $dbman->change_field_type($table, $field);

        // coursereadings savepoint reached.
        upgrade_mod_savepoint(true, 2013072907, 'coursereadings');
    }

    if ($oldversion < 2013072908) {

        // Define table coursereadings_breach_note to be created
        $table = new xmldb_table('coursereadings_breach_note');

        // Adding fields to table coursereadings_breach_note
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sourceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table coursereadings_breach_note
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for coursereadings_breach_note
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // coursereadings savepoint reached
        upgrade_mod_savepoint(true, 2013072908, 'coursereadings');
    }

    if ($oldversion < 2014102201) {

        // Add creator/modifier user ID fields to article table.
        $table = new xmldb_table('coursereadings_article');

        $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'source');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('modifiedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'createdby');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add creator/modifier user ID fields to source table.
        $table = new xmldb_table('coursereadings_source');

        $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'furtherinfo');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('modifiedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'createdby');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Coursereadings savepoint reached.
        upgrade_mod_savepoint(true, 2014102201, 'coursereadings');
    }

    if ($oldversion < 2014102202) {

        // Add total page count field to article table.
        $table = new xmldb_table('coursereadings_article');

        $field = new xmldb_field('totalpages', XMLDB_TYPE_INTEGER, '8', null, null, null, null, 'pagerange');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Coursereadings savepoint reached.
        upgrade_mod_savepoint(true, 2014102202, 'coursereadings');
    }

    if ($oldversion < 2014102203) {

        // Define index idx_courseread_src_title (not unique) to be added to coursereadings_source.
        $table = new xmldb_table('coursereadings_source');
        $index = new xmldb_index('idx_courseread_src_title', XMLDB_INDEX_NOTUNIQUE, array('title'));

        // Conditionally launch add index idx_courseread_src_title.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index idx_courseread_art_title (not unique) to be added to coursereadings_source.
        $table = new xmldb_table('coursereadings_article');
        $index = new xmldb_index('idx_courseread_art_title', XMLDB_INDEX_NOTUNIQUE, array('title'));

        // Conditionally launch add index idx_courseread_art_title.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Coursereadings savepoint reached.
        upgrade_mod_savepoint(true, 2014102203, 'coursereadings');
    }

    if ($oldversion < 2014102204) {

        // Define index coursereadings_noncopyright_resource_idx (not unique) to be added to coursereadings_noncopyright.
        $table = new xmldb_table('coursereadings_noncopyright');
        $index = new xmldb_index('coursereadings_noncopyright_resource_idx', XMLDB_INDEX_NOTUNIQUE, array('resourceid'));

        // Conditionally launch add index coursereadings_noncopyright_resource_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index coursereadings_migrationnote_resource_idx (not unique) to be added to coursereadings_migrationnote.
        $table = new xmldb_table('coursereadings_migrationnote');
        $index = new xmldb_index('coursereadings_migrationnote_resource_idx', XMLDB_INDEX_NOTUNIQUE, array('resourceid'));

        // Conditionally launch add index coursereadings_migrationnote_resource_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Coursereadings savepoint reached.
        upgrade_mod_savepoint(true, 2014102204, 'coursereadings');
    }

    if ($oldversion < 2014102206) {

        $table = new xmldb_table('coursereadings_article');

        // Add 'externalurl' field to article table if not already present.
        $field = new xmldb_field('externalurl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'source');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index idx_courseread_art_ext (not unique) to coursereadings_article if not already present.
        $index = new xmldb_index('idx_courseread_art_ext', XMLDB_INDEX_NOTUNIQUE, array('externalurl(255)'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add 'doi' field to article table if not already present.
        $field = new xmldb_field('doi', XMLDB_TYPE_CHAR, '1024', null, null, null, null, 'externalurl');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index idx_courseread_art_doi (not unique) to coursereadings_article if not already present.
        $index = new xmldb_index('idx_courseread_art_doi', XMLDB_INDEX_NOTUNIQUE, array('doi(255)'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add 'externalurl' field to source table if not already present.
        $table = new xmldb_table('coursereadings_source');
        $field = new xmldb_field('externalurl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'furtherinfo');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index idx_courseread_sou_externalurl (not unique) to coursereadings_source if not already present.
        $index = new xmldb_index('idx_courseread_sou_ext', XMLDB_INDEX_NOTUNIQUE, array('externalurl(255)'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Coursereadings savepoint reached.
        upgrade_mod_savepoint(true, 2014102206, 'coursereadings');
    }

    if ($oldversion < 2015030100) {
        // Add table for tracking enrolment numbers, if it doesn't exist.
        $table = new xmldb_table('coursereadings_enrolments');
        $file = $CFG->dirroot.'/mod/coursereadings/db/install.xml';
        if (!$dbman->table_exists($table)) {
            $dbman->install_one_table_from_xmldb_file($file, 'coursereadings_enrolments');
        }

        // Coursereadings savepoint reached.
        upgrade_mod_savepoint(true, 2015030100, 'coursereadings');
    }

    if ($oldversion < 2015030104) {
        // Add table for reporting periods, if it doesn't exist.
        $table = new xmldb_table('coursereadings_reportperiod');
        $file = $CFG->dirroot.'/mod/coursereadings/db/install.xml';
        if (!$dbman->table_exists($table)) {
            $dbman->install_one_table_from_xmldb_file($file, 'coursereadings_reportperiod');
        }

        // Coursereadings savepoint reached.
        upgrade_mod_savepoint(true, 2015030104, 'coursereadings');
    }

    if ($oldversion < 2016033102) {
        // Define index idx_courseread_enr_cid_enr (not unique) to be added to coursereadings_enrolments.
        $table = new xmldb_table('coursereadings_enrolments');
        $index = new xmldb_index('idx_courseread_enr_cid_enr', XMLDB_INDEX_NOTUNIQUE, array('courseid, enrolments'));

        // Conditionally launch add index idx_courseread_enr_cid_enr.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Coursereadings savepoint reached.
        upgrade_mod_savepoint(true, 2016033102, 'coursereadings');
    }

    return true;
}