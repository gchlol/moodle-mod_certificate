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
 * A4_non_embedded certificate type
 *
 * @package    mod_certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $DB, $USER;

require_once($CFG->dirroot . '/mod/ballot/lib.php');

$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->SetProtection(array('modify'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// Define variables
// Landscape
if ($certificate->orientation == 'L') {
    $x = 10;
    $y = 30;
    $sealx = 230;
    $sealy = 150;
    $sigx = 47;
    $sigy = 155;
    $custx = 47;
    $custy = 155;
    $wmarkx = 40;
    $wmarky = 31;
    $wmarkw = 212;
    $wmarkh = 148;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 297;
    $brdrh = 210;
    $codey = 175;
} else { // Portrait
    $x = 10;
    $y = 70;
    $sealx = 150;
    $sealy = 220;
    $sigx = 30;
    $sigy = 230;
    $custx = 30;
    $custy = 230;
    $wmarkx = 26;
    $wmarky = 58;
    $wmarkw = 158;
    $wmarkh = 170;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 210;
    $brdrh = 297;
    $codey = 285;
}

// Add images and lines
certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
certificate_draw_frame($pdf, $certificate);
// Set alpha to semi-transparency
$pdf->SetAlpha(0.2);
certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
$pdf->SetAlpha(1);
certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

// Add text
$pdf->SetTextColor(0, 0, 120);
certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', '', 30, format_string($course->fullname));
$pdf->SetTextColor(0, 0, 0);

$result = $DB->get_record('ballot_requests', array('ballot' => 1, 'userid' => $USER->id));

// Get ballot request.
if ($result) {
    $request = new \mod_ballot\request($result);

    // Get required fields.
    if (!$posds = $DB->get_record('ballot_request_data', array('requestid' => $request->id, 'fieldid' => 9))) {
        $posds = new stdClass();
        $posds->data = '';
    }

    if (!$empsubgroup = $DB->get_record('ballot_request_data', array('requestid' => $request->id, 'fieldid' => 11))) {
        $empsubgroup = new stdClass();
        $empsubgroup->data = '';
    }

    if (!$orgno = $DB->get_record('ballot_request_data', array('requestid' => $request->id, 'fieldid' => 8))) {
        $orgno = new stdClass();
        $orgno->data = '';
    }

    if (!$unit = $DB->get_record('ballot_request_data', array('requestid' => $request->id, 'fieldid' => 7))) {
        $unit = new stdClass();
        $unit->data = '';
    }

    $empsubgroup->data = str_replace('01', '', $empsubgroup->data);
    $empsubgroup->data = trim($empsubgroup->data);

    $sql = "SELECT bd.*
              FROM {ballot_dates} bd
              JOIN {ballot_date_signups} bds ON bd.id = dateid
                   AND bds.requestid = $request->id
              JOIN {ballot_date_signup_statuses} bdss ON bds.id = bdss.datesignupid
                   AND bdss.type = 1
                   AND bdss.status = 1
         LEFT JOIN {ballot_date_signup_statuses} cancel ON bds.id = cancel.datesignupid
                   AND cancel.type = 4
             WHERE bd.ballot = 1
                   AND (cancel.status IS NULL OR cancel.status = 0)
          ORDER BY bd.date";
    $dates = $DB->get_records_sql($sql);

    certificate_print_text($pdf, $x, $y + 14, 'L', 'Helvetica', '', 12, 'Name: ' . fullname($USER));
    certificate_print_text($pdf, $x, $y + 20, 'L', 'Helvetica', '', 12, 'Payroll number: ' . format_string($USER->username));
    certificate_print_text($pdf, $x, $y + 26, 'L', 'Helvetica', '', 12, 'Position description: ' . format_string($posds->data));
    certificate_print_text($pdf, $x, $y + 32, 'L', 'Helvetica', '', 12, 'Employee level: ' . format_string($empsubgroup->data));
    certificate_print_text($pdf, $x, $y + 38, 'L', 'Helvetica', '', 12, 'Org unit number: ' . format_string($orgno->data));
    certificate_print_text($pdf, $x, $y + 44, 'L', 'Helvetica', '', 12, 'Org unit name: ' . format_string($unit->data));
    certificate_print_text($pdf, $x, $y + 52, 'C', 'Helvetica', '', 14, fullname($USER) . ' has been approved leave for the following days: ');

    $list = '<ul>';

    $count = count($dates);
    $numdates = 0;

    $printlist = false;

    foreach ($dates as $key => $date) {
        if ($numdates < 26) {
            $signupdate = new DateTime();
            $signupdate->setTimestamp($date->date);

            $list .= '<li>' . $signupdate->format('l, dS F Y') . '</li>';
            unset($dates[$key]);
            $numdates++;
            $printlist = true;
        }
    }

    $list .= '</ul>';

    $pdf->setFont('Helvetica');
    $pdf->setFontSize(12);
    $pdf->SetXY($x, $y + 62);

    if ($printlist) {
        $pdf->writeHTML($list);
    }

    if ($count < 26) {
        $totalpages = 1;
    } else {
        $totalpages = 2;
    }

    $pagenum = 1;

    certificate_print_text($pdf, $x, $codey, 'C', 'Times', '', 10, certificate_get_code($certificate, $certrecord));
    certificate_print_text($pdf, $x, $y + 200, 'C', 'Times', '', 10, 'Page ' . $pagenum . ' of ' . $totalpages);

    if (!empty($dates)) {
        $pdf->AddPage();
        $pagenum++;

        // Add images and lines
        certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
        certificate_draw_frame($pdf, $certificate);

        // Set alpha to semi-transparency
        $pdf->SetAlpha(0.2);
        certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
        $pdf->SetAlpha(1);
        certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
        certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

        $printlist = false;

        $list = '<ul>';
        foreach ($dates as $key => $date) {
            $signupdate = new DateTime();
            $signupdate->setTimestamp($date->date);

            $list .= '<li>' . $signupdate->format('l, dS F Y') . '</li>';
            $printlist = true;
        }
        $list .= '</ul>';

        $pdf->setFont('Helvetica');
        $pdf->setFontSize(12);
        $pdf->SetXY($x, $y);

        if ($printlist) {
            $pdf->writeHTML($list);
        }

        certificate_print_text($pdf, $x, $y + 200, 'C', 'Times', '', 10, 'Page ' . $pagenum . ' of ' . $totalpages);
    }
}

certificate_print_text($pdf, $x, $y + 90, 'C', 'Helvetica', '', 14, certificate_get_date($certificate, $certrecord, $course));
certificate_print_text($pdf, $x, $y + 100, 'C', 'Times', '', 10, certificate_get_grade($certificate, $course));
certificate_print_text($pdf, $x, $y + 110, 'C', 'Times', '', 10, certificate_get_outcome($certificate, $course));

if ($certificate->printhours) {
    certificate_print_text($pdf, $x, $y + 122, 'C', 'Times', '', 6, get_string('credithours', 'certificate') . ': ' . $certificate->printhours);
}
certificate_print_text($pdf, $x, $codey, 'C', 'Times', '', 10, certificate_get_code($certificate, $certrecord));
$i = 0;
if ($certificate->printteacher) {
    $context = context_module::instance($cm->id);
    if ($teachers = get_users_by_capability($context, 'mod/certificate:printteacher', '', $sort = 'u.lastname ASC', '', '', '', '', false)) {
        foreach ($teachers as $teacher) {
            $i++;
            certificate_print_text($pdf, $sigx, $sigy + ($i * 4), 'L', 'Times', '', 12, fullname($teacher));
        }
    }
}

certificate_print_text($pdf, $custx, $custy, 'L', null, null, null, $certificate->customtext);
