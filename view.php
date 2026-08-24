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
 * Handles viewing a certificate
 *
 * @package    mod_certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_certificate\local\temporary_user;
use mod_certificate\permission;

require_once("../../config.php");
require_once("$CFG->dirroot/mod/certificate/locallib.php");
require_once("$CFG->dirroot/mod/certificate/deprecatedlib.php");
require_once("$CFG->libdir/pdflib.php");

$id = required_param('id', PARAM_INT);    // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA);
$edit = optional_param('edit', -1, PARAM_BOOL);

if (!$cm = get_coursemodule_from_id('certificate', $id)) {
    throw new moodle_exception('invalidcoursemodule', 'mod_certificate');
}
if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    throw new moodle_exception('coursemisconfigured', 'mod_certificate');
}
if (!$certificate = $DB->get_record('certificate', array('id'=> $cm->instance))) {
    throw new moodle_exception('invalidcertificate', 'mod_certificate');
}

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/certificate:view', $context);

list($userid, $targetuser) = certificate_get_requested_user($context);

$event = \mod_certificate\event\course_module_viewed::create(array(
    'objectid' => $certificate->id,
    'context' => $context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('certificate', $certificate);
$event->trigger();

$completion=new completion_info($course);
$completion->set_module_viewed($cm);

// Initialize $PAGE, compute blocks
$PAGE->set_url('/mod/certificate/view.php', ['id' => $cm->id, 'userid' => $userid]);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($certificate->name));
$PAGE->set_heading(format_string($course->fullname));

if (($edit != -1) and $PAGE->user_allowed_editing()) {
     $USER->editing = $edit;
}

// Add block editing button
if ($PAGE->user_allowed_editing()) {
    $editvalue = $PAGE->user_is_editing() ? 'off' : 'on';
    $strsubmit = $PAGE->user_is_editing() ? get_string('blockseditoff') : get_string('blocksediton');
    $url = new moodle_url(
        $CFG->wwwroot . '/mod/certificate/view.php',
        ['id' => $cm->id, 'userid' => $userid, 'edit' => $editvalue]
    );
    $PAGE->set_button($OUTPUT->single_button($url, $strsubmit));
}

// Check if the user can view the certificate
if (
    $userid == $USER->id &&
    $certificate->requiredtime &&
    !has_capability('mod/certificate:manage', $context)
) {
    if (certificate_get_course_time($course->id) < ($certificate->requiredtime * 60)) {
        $a = new stdClass;
        $a->requiredtime = $certificate->requiredtime;
        notice(get_string('requiredtimenotmet', 'certificate', $a), "$CFG->wwwroot/course/view.php?id=$course->id");
        die;
    }
}

// Resolve the existing issue, or create one for self-service and on-demand portfolios.
$certrecord = certificate_get_issue_for_view($course, $targetuser, $certificate, $cm);

make_cache_directory('tcpdf');

// Load the specific certificate type.
$requirepath = "$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate.php";
$reponame = get_config('certificate', 'reponame');
if (!empty($reponame)) {
    $requirepath = "$CFG->dataroot/repository/$reponame/CERTIFICATE/type/$certificate->certificatetype/certificate.php";
}

$usercontext = new temporary_user($targetuser);
try {
    $usercontext->apply();
    $requestinguser = $usercontext->get_requesting_user();
    require($requirepath);

} finally {
    $usercontext->restore();
}

if (empty($action)) { // Not displaying PDF
    echo $OUTPUT->header();

    $canviewotherusers = permission::can_view_other_users($context);
    if ($canviewotherusers) {
        $numusers = certificate_count_issues($certificate->id, $cm);
        $url = html_writer::tag('a', get_string('viewcertificateviews', 'certificate', $numusers),
            array('href' => $CFG->wwwroot . '/mod/certificate/report.php?id=' . $cm->id));
        echo html_writer::tag('div', $url, array('class' => 'reportlink'));
    }

    if ($attempts = certificate_get_attempts($certificate->id, $userid)) {
        echo certificate_print_attempts($course, $certificate, $attempts);
    }
    if ($certificate->delivery == 0)    {
        $str = get_string('openwindow', 'certificate');
    } elseif ($certificate->delivery == 1)    {
        $str = get_string('opendownload', 'certificate');
    } elseif ($certificate->delivery == 2)    {
        $str = get_string('openemail', 'certificate');
    }
    echo html_writer::tag('p', $str, array('style' => 'text-align:center'));
    $linkname = get_string('getcertificate', 'certificate');

    $link = new moodle_url('/mod/certificate/view.php', ['id' => $cm->id, 'action' => 'get', 'userid' => $userid]);
    $button = new single_button($link, $linkname);
    if ($certificate->delivery != 1) {
        $button->add_action(new popup_action('click', $link, 'view' . $cm->id, array('height' => 600, 'width' => 800)));
    }

    echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));
    echo $OUTPUT->footer($course);
    exit;
} else { // Output to pdf

    // No debugging here, sorry.
    $CFG->debugdisplay = 0;
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');

    $filename = certificate_get_certificate_filename($certificate, $cm, $course) . '.pdf';

    // PDF contents are now in $file_contents as a string.
    $filecontents = $pdf->Output('', 'S');

    // Viewing another user's certificate must not overwrite their stored file.
    if ($certificate->savecert == 1 && $userid == $USER->id) {
        certificate_save_pdf($filecontents, $certrecord->id, $filename, $context->id);
    }

    if ($certificate->delivery == 0) {
        // Open in browser.
        send_file($filecontents, $filename, 0, 0, true, false, 'application/pdf');
    } elseif ($certificate->delivery == 1) {
        // Force download.
        send_file($filecontents, $filename, 0, 0, true, true, 'application/pdf');
    } elseif ($certificate->delivery == 2) {
        if ($userid == $USER->id) {
            certificate_email_student($course, $certificate, $certrecord, $context, $filecontents, $filename);
        }

        // Open in browser for the requester.
        send_file($filecontents, $filename, 0, 0, true, false, 'application/pdf');
    }
}
