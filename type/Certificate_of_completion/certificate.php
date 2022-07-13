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
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Majorly modified to allow certificate
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}

$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
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
    $y = 78;

    $sealx = 150;
    $sealy = 220;
    $sigx = 140;
    $sigy = 239;
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
    $codey = 250;
	$datex = 10;
	$datey = 221;
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
$pdf->SetTextColor(0, 60, 105);
certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', '', 37.5, 'Certificate of completion');
$pdf->SetTextColor(128, 128, 128);
certificate_print_text($pdf, $x, $y + 32, 'C', 'Helvetica', '', 16, 'This is to certify that');
$pdf->SetTextColor(0, 60, 105);
certificate_print_text($pdf, $x, $y + 55, 'C', 'Helvetica', 'B', 32, fullname($USER));
$pdf->SetTextColor(128, 128, 128);
certificate_print_text($pdf, $x, $y + 85, 'C', 'Helvetica', '', 16, 'has successfully completed the requirements of');
$pdf->SetTextColor(0, 60, 105);
certificate_print_text($pdf, $x, $y + 110, 'C', 'Helvetica', 'B', 16, $course->fullname);
//$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $datex, $datey, 'C', 'Helvetica', 'B', 16, certificate_get_date($certificate, $certrecord, $course));
$pdf->SetTextColor(128, 128, 128);
certificate_print_text($pdf, $x, $y + 172, 'C', 'Helvetica', '', 16, 'Presented by');
$pdf->SetTextColor(0, 60, 105);
certificate_print_text($pdf, $x, $y + 180, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');

certificate_print_text($pdf, $x, $y + 102, 'C', 'Times', '', 10, certificate_get_grade($certificate, $course));
certificate_print_text($pdf, $x, $y + 112, 'C', 'Times', '', 10, certificate_get_outcome($certificate, $course));
if ($certificate->printhours) {
    certificate_print_text($pdf, $x, $y + 112, 'C', 'Times', '', 10, get_string('credithours', 'certificate') . ': ' . $certificate->printhours);
}
certificate_print_text($pdf, $x, $codey, 'C', 'Times', '', 10, certificate_get_code($certificate, $certrecord));

certificate_print_text($pdf, $x, $y + 126, 'C', 'Helvetica', 'B', 16, 'on');


//$pdf->SetTextColor(0, 0, 0);
$i = 0;
if ($certificate->printteacher) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);




    if ($teachers = get_users_by_capability($context, 'mod/certificate:printteacher', '', $sort = 'u.lastname ASC', '', '', '', '', false)) {
        foreach ($teachers as $teacher) {
            $i++;
            certificate_print_text($pdf, $sigx, $sigy + ($i * 5), 'R', 'Helvetica', '', 12, fullname($teacher));
        }
		$i++;
        certificate_print_text($pdf, $sigx, $sigy + ($i * 5), 'R', 'Helvetica', '', 12, 'Course Facilitator');
        }
}

 certificate_print_text($pdf, $custx, $custy, 'L', null, null, null, $certificate->customtext);
?>