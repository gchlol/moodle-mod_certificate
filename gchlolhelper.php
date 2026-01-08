<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * Display saved Custom Reports in table customreports .
 *
 * @package     mod_certificate
 * @copyright   2025 Gold Coast Health
 * @author      Jonas Sajonas
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Returns requested user id coming from userid URL parameter.
 *
 * @return int
 */
function certificate_requested_userid() {
    static $requestedid = null;

    if ($requestedid !== null) {
        return $requestedid;
    }

    $userid = optional_param('userid', 0, PARAM_INT);
    if ($userid <= 0) {
        $userid = 0;
    }

    $requestedid = $userid;

    return $requestedid;
}

/**
 * Resolves user that should be used for issuing a certificate.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context_module $context
 * @return stdClass resolved user record
 */
function certificate_target_user($course, $cm, $context) {
    global $DB, $USER;

    static $targetuser = null;

    if ($targetuser !== null) {
        return $targetuser;
    }

    $requestedid = certificate_requested_userid();
    if (empty($requestedid) || $requestedid == $USER->id) {
        $targetuser = $USER;

        return $targetuser;
    }

    if (!has_capability('mod/certificate:manage', $context)) {
        $targetuser = $USER;

        return $targetuser;
    }

    if (!$requesteduser = $DB->get_record('user', array('id' => $requestedid))) {
        $targetuser = $USER;

        return $targetuser;
    }

    $coursecontext = context_course::instance($course->id);
    if (!is_enrolled($coursecontext, $requesteduser, '', true)) {
        $targetuser = $USER;

        return $targetuser;
    }

    $targetuser = $requesteduser;

    return $targetuser;
}

/**
 * Creates a certificate issue record without triggering email notifications.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $certificate
 * @param stdClass $cm
 * @return stdClass certificate issue
 */
function certificate_issue_no_email($course, $user, $certificate, $cm) {
    $originalemailteachers = $certificate->emailteachers;
    $originalemailothers = $certificate->emailothers;

    $certificate->emailteachers = 0;
    $certificate->emailothers = '';

    $certissue = certificate_get_issue($course, $user, $certificate, $cm);

    $certificate->emailteachers = $originalemailteachers;
    $certificate->emailothers = $originalemailothers;

    return $certissue;
}

/**
 * Builds standard parameter array for certificate view URLs.
 *
 * @param int $cmid
 * @param int $requesteduserid
 * @param array $extra
 * @return array
 */
function certificate_view_params($cmid, $requesteduserid = 0, array $extra = array()) {
    $params = array('id' => $cmid);
    if (!empty($requesteduserid)) {
        $params['userid'] = $requesteduserid;
    }

    if (!empty($extra)) {
        $params = array_merge($params, $extra);
    }

    return $params;
}

/**
 * Helper to construct a view URL with relevant parameters.
 *
 * @param int $cmid
 * @param int $requesteduserid
 * @param array $extra
 * @return moodle_url
 */
function certificate_view_url($cmid, $requesteduserid = 0, array $extra = array()) {
    $params = certificate_view_params($cmid, $requesteduserid, $extra);

    return new moodle_url('/mod/certificate/view.php', $params);
}

/**
 * Renders attempt list for a specific user if attempts exist.
 *
 * @param stdClass $course
 * @param stdClass $certificate
 * @param int $userid
 */
function certificate_render_user_attempts($course, $certificate, $userid) {
    if ($attempts = certificate_user_attempts($certificate->id, $userid)) {
        certificate_print_user_attempts($course, $certificate, $attempts, $userid);
    }
}

/**
 * Returns a list of attempts for the provided user.
 *
 * @param int $certificateid
 * @param int $userid
 * @return array|bool
 */
function certificate_user_attempts($certificateid, $userid) {
    global $DB, $USER;

    if ($userid == $USER->id) {
        return certificate_get_attempts($certificateid);
    }

    $sql = "SELECT *
              FROM {certificate_issues} i
             WHERE certificateid = :certificateid
               AND userid = :userid";

    if ($issues = $DB->get_records_sql($sql, array('certificateid' => $certificateid, 'userid' => $userid))) {
        return $issues;
    }

    return false;
}

/**
 * Outputs the attempt table for the supplied user with a cached grade.
 *
 * @param stdClass $course
 * @param stdClass $certificate
 * @param array $attempts
 * @param int $userid
 */
function certificate_print_user_attempts($course, $certificate, $attempts, $userid) {
    global $OUTPUT;

    echo $OUTPUT->heading(get_string('summaryofattempts', 'certificate'));

    $table = new html_table();
    $table->class = 'generaltable';
    $table->head = array(get_string('issued', 'certificate'));
    $table->align = array('left');
    $table->attributes = array("style" => "width:20%; margin:auto");
    $gradecolumn = $certificate->printgrade;
    if ($gradecolumn) {
        $table->head[] = get_string('grade');
        $table->align[] = 'center';
        $table->size[] = '';
        $attemptgrade = certificate_get_grade($certificate, $course, $userid);
    }

    foreach ($attempts as $attempt) {
        $row = array();
        $row[] = userdate($attempt->timecreated);

        if ($gradecolumn) {
            $row[] = $attemptgrade;
        }

        $table->data[$attempt->id] = $row;
    }

    echo html_writer::table($table);
}
