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
 * This page reviews a certificate
 *
 * @package    mod_certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');
require_once("$CFG->libdir/pdflib.php");

// Retrieve any variables that are passed
$id = required_param('id', PARAM_INT);    // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA);

if (!$cm = get_coursemodule_from_id('certificate', $id)) {
    throw new moodle_exception('Course Module ID was incorrect');
}

if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    throw new moodle_exception('course is misconfigured');
}

if (!$certificate = $DB->get_record('certificate', array('id'=> $cm->instance))) {
    throw new moodle_exception('course module is incorrect');
}

// Requires a course login
require_login($course, true, $cm);

// Check the capabilities
$context = context_module::instance($cm->id);
require_capability('mod/certificate:view', $context);

// Initialize $PAGE, compute blocks
$PAGE->set_url('/mod/certificate/review.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($certificate->name));
$PAGE->set_heading(format_string($course->fullname));

// Get previous cert record
if (!$certrecord = $DB->get_record('certificate_issues', array('userid' => $USER->id, 'certificateid' => $certificate->id))) {
    notice(get_string('nocertificatesissued', 'certificate'), "$CFG->wwwroot/course/view.php?id=$course->id");
    die;
}

// Load the specific certificatetype
require ("$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate.php");

if ($action) {
    $filename = certificate_get_certificate_filename($certificate, $cm, $course) . '.pdf';
    $filecontents = $pdf->Output('', 'S');
    // Open in browser.
    send_file($filecontents, $filename, 0, 0, true, false, 'application/pdf');
    exit();
}

echo $OUTPUT->header();

$reviewurl = new moodle_url('/mod/certificate/review.php', array('id' => $cm->id));
groups_print_activity_menu($cm, $reviewurl);
$currentgroup = groups_get_activity_group($cm);
$groupmode = groups_get_activity_groupmode($cm);

if (has_capability('mod/certificate:manage', $context)) {
    $numusers = count(certificate_get_issues($certificate->id, 'ci.timecreated ASC', $groupmode, $cm));
    $url = html_writer::tag('a', get_string('viewcertificateviews', 'certificate', $numusers),
        array('href' => $CFG->wwwroot . '/mod/certificate/report.php?id=' . $cm->id));
    echo html_writer::tag('div', $url, array('class' => 'reportlink'));
}

echo html_writer::tag('p', get_string('viewed', 'certificate'). '<br />' . userdate($certrecord->timecreated), array('style' => 'text-align:center'));

$link = new moodle_url('/mod/certificate/review.php?id='.$cm->id.'&action=get');
$linkname = get_string('reviewcertificate', 'certificate');
$button = new single_button($link, $linkname);
$button->add_action(new popup_action('click', $link, array('height' => 600, 'width' => 800)));

echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));

echo $OUTPUT->footer($course);
