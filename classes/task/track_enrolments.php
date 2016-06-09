<?php

namespace mod_coursereadings\task;

class track_enrolments extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('track_enrolments', 'mod_coursereadings');
    }

    public function execute() {
        global $CFG, $DB;
        mtrace('... Updating course enrolment numbers');
        $config = get_config('coursereadings');
        $now = time();
        $newrecords = array();
        $updates = array();
        $studentrole = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // Fetch existing tracked enrolment figures.
        $oldrecords = $DB->get_records('coursereadings_enrolments');
        $map = array();
        foreach ($oldrecords as $old) {
            $map[$old->courseid] = $old->id;
        }
        $basefilters = ' ue.status = :status AND e.status = :enabled AND ue.timestart <= :start';
        $basefilters .= ' AND (ue.timeend = 0 OR ue.timeend >= :end)';

        // Fetch "main" enrolments (selected enrolment methods).
        $currentmain = array();
        if (!empty($config->trackedenrolmethods)) {
            list($sql, $params) = $DB->get_in_or_equal(explode(',', $config->trackedenrolmethods), SQL_PARAMS_NAMED, 'enrol');
            $sql = 'SELECT c.id, COUNT(DISTINCT ue.userid) AS numstudents
                    FROM {course} c INNER JOIN {context} ctx ON (ctx.contextlevel = :level AND ctx.instanceid = c.id)
                                    INNER JOIN {enrol} e ON e.courseid=c.id
                                    INNER JOIN {user_enrolments} ue ON ue.enrolid=e.id
                                    INNER JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.userid = ue.userid AND ra.roleid = :roleid)
                    WHERE e.enrol ' . $sql . ' AND ' . $basefilters . '
                    GROUP BY c.id';
            $params['status'] = ENROL_USER_ACTIVE;
            $params['enabled'] = ENROL_INSTANCE_ENABLED;
            $params['start'] = $now;
            $params['end'] = $now;
            $params['roleid'] = $studentrole;
            $params['level'] = CONTEXT_COURSE;
            $currentmain = $DB->get_recordset_sql($sql, $params);
        }

        // Fetch additional self-enrolled users using specified pattern(s).
        $currentself = array();
        if (!empty($config->trackedselfenrolpattern)) {
            $patterns = explode(',', $config->trackedselfenrolpattern);
            $where = array();
            $params = array();
            $i = 0;
            foreach ($patterns as $pattern) {
                $param = 'pattern' . $i;
                $where[] .= $DB->sql_like('u.username', ":$param", $casesensitive = true, $accentsensitive = true);
                $params[$param] = $pattern;
            }
            $wsql = implode(' AND ', $where);
            $sql = "SELECT c.id, COUNT(DISTINCT ue.userid) AS numstudents
                    FROM {course} c INNER JOIN {context} ctx ON (ctx.contextlevel = :level AND ctx.instanceid = c.id)
                                    INNER JOIN {enrol} e ON e.courseid=c.id
                                    INNER JOIN {user_enrolments} ue ON ue.enrolid=e.id
                                    INNER JOIN {user} u ON u.id=ue.userid
                                    INNER JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.userid = u.id AND ra.roleid = :roleid)
                    WHERE $wsql AND e.enrol = 'self' AND $basefilters
                    GROUP BY c.id";
            $params['status'] = ENROL_USER_ACTIVE;
            $params['enabled'] = ENROL_INSTANCE_ENABLED;
            $params['start'] = $now;
            $params['end'] = $now;
            $params['roleid'] = $studentrole;
            $params['level'] = CONTEXT_COURSE;
            $currentself = $DB->get_recordset_sql($sql, $params);
        }

        // Process current main enrolments.
        foreach ($currentmain as $cid => $course) {
            if (array_key_exists($cid, $map)) {
                // Updating existing record - shift it to the updates array and update number of students.
                $id = $map[$cid];
                $updates[$cid] = clone($oldrecords[$id]);
                $updates[$cid]->enrolments = $course->numstudents;
            } else {
                // New records are indexed by course ID so we can add additional self-enrolments later.
                $newrecords[$cid] = new \stdClass();
                $newrecords[$cid]->courseid = $course->id;
                $newrecords[$cid]->enrolments = $course->numstudents;
                $newrecords[$cid]->lastreset = $now;
            }
        }

        // Process current (additional) self enrolments.
        foreach ($currentself as $cid => $course) {
            if (array_key_exists($cid, $updates)) {
                // Updated record already has main enrolments - add number of students to those from main.
                $updates[$id]->enrolments += $course->numstudents;
            } elseif (array_key_exists($cid, $newrecords)) {
                // New record already has main enrolments - add number of students to those from main.
                $newrecords[$cid]->enrolments += $course->numstudents;
            } elseif (array_key_exists($cid, $map)) {
                // Existing record (no main enrolments) - shift it to the updates array and update number of students.
                $id = $map[$cid];
                $updates[$cid] = clone($oldrecords[$id]);
                $updates[$cid]->enrolments = $course->numstudents;
            } else {
                // New record, with no main enrolments.
                $newrecords[$cid] = new \stdClass();
                $newrecords[$cid]->courseid = $course->id;
                $newrecords[$cid]->enrolments = $course->numstudents;
                $newrecords[$cid]->lastreset = $now;
            }
        }

        if (count($newrecords) > 0 ) {
            mtrace('  Inserting ' . count($newrecords) . ' new records:');
            $DB->insert_records('coursereadings_enrolments', $newrecords);
            mtrace('  Done.');
        }

        mtrace('  Updating existing records:');
        foreach ($updates as $record) {
            $id = $record->id;
            $new = $record->enrolments;
            $old = $oldrecords[$id]->enrolments;
            $threshold = 0;
            if (!empty($config->enroldecreasepercent)) {
                // Calculate percentage-based threshold.
                $threshold = round($old / ($config->enroldecreasepercent / 100));
            }
            if (!empty($config->enroldecreasethreshold)) {
                // Take the greatest threshold from percentage-based and fixed values.
                $threshold = max($threshold, $config->enroldecreasethreshold);
            }
            $threshold = $old - $threshold; // Only update if number has increased, or has decreased by less than ~10%.
            if ($new == $old) {
                mtrace('    Course ' . $record->courseid . ': unchanged');
            } elseif ($new > 0 && $new >= $threshold) {
                mtrace('    Updating course ' . $record->courseid . ': ' . $record->enrolments . ' enrolled users (from '.$old.')');
                $DB->update_record('coursereadings_enrolments', $record);
            } else {
                mtrace("    Course $record->courseid decreased from $old to $new, skipping...");
            }
            unset($oldrecords[$id]);
        }
        mtrace('  Done.');

        mtrace('  ' . count($oldrecords) . ' existing records were skipped, as they do not appear to have any current enrolments.');

        // Do something here
        $status = true;

        if($status) {
            mtrace('... Done.');
        } else {
            mtrace('... Error processing sync!');
        }
    }
}