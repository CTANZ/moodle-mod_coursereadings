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
 * Defines the tab bar used on Course Materials dashboard.
 *
 * @package    mod_coursereadings
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
}

require_once($CFG->dirroot.'/mod/coursereadings/lib.php');

$toprow = array();
$toprow[] = new tabobject('dashboard', new moodle_url('/mod/coursereadings/manage/index.php'), get_string('dashboard', 'mod_coursereadings'));
$toprow[] = new tabobject('flaggedarticles', new moodle_url('/mod/coursereadings/manage/flagged-articles.php'), get_string('dashboard_flaggedarticles', 'mod_coursereadings'));
$toprow[] = new tabobject('flaggedsources', new moodle_url('/mod/coursereadings/manage/flagged-sources.php'), get_string('dashboard_flaggedsources', 'mod_coursereadings'));
$toprow[] = new tabobject('mergesplitarticle', new moodle_url('/mod/coursereadings/manage/merge-split-article.php'), get_string('dashboard_mergesplitarticle', 'mod_coursereadings'));
$toprow[] = new tabobject('findarticle', new moodle_url('/mod/coursereadings/manage/find-article.php'), get_string('dashboard_findarticle', 'mod_coursereadings'));
$toprow[] = new tabobject('findsource', new moodle_url('/mod/coursereadings/manage/find-source.php'), get_string('dashboard_findsource', 'mod_coursereadings'));
$toprow[] = new tabobject('reports', new moodle_url('/mod/coursereadings/manage/reports/index.php'), get_string('dashboard_reports', 'mod_coursereadings'));
if (has_capability('mod/coursereadings:managereportperiods', context_system::instance())) {
    $toprow[] = new tabobject('reportperiods', new moodle_url('/mod/coursereadings/manage/reportperiods.php'), get_string('dashboard_reportperiods', 'mod_coursereadings'));
}
$tabs = array($toprow);

print_tabs($tabs, $currenttab);

