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
 * @copyright  Nathan Robertson <nathanrobertson1997@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */

defined('MOODLE_INTERNAL') || die();

// Global variables.
global $DB;

// Create a new PDF.
$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

// Setup the PDF.
$pdf->SetTitle($certificate->name);
$pdf->SetProtection(array('modify'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_FOOTER);
$pdf->SetMargins(10, 20, 10, true);

// Add a page to the certificate.
$pdf->AddPage();

// Reference to the images we are using.
$image = html_writer::img($CFG->wwwroot . '/mod/certificate/type/HHA/image.jpg', 'HHA', array('width' => 500, 'height' => 150));
$image1 = html_writer::img($CFG->wwwroot . '/mod/certificate/type/HHA/hand.jpg', 'HHA', array('width' => 175, 'height' => 175));
$image2 = html_writer::img($CFG->wwwroot . '/mod/certificate/type/HHA/signature.png', 'HHA', array('width' => 100, 'height' => 100));

// Get the name of the staff member.
$name = $USER->firstname . " " . $USER->lastname;

//Get the completion Date.
$completion = $DB->get_record("course_completions", array('userid' => $USER->id, 'course' => $COURSE->id));
$date = date("d/m/Y", $completion->timecompleted);

$pdf->writeHTML('
    <table>
        <tr>
            <td colspan="3">'. html_writer::tag('p', $image, array('style' => 'text-align: center;')) .'</td>
        </tr>
        <tr>
            <td colspan="3" style="border-top: 1px solid #000;border-bottom: 1px solid #000; text-align: center;"><p style="font-size: 25px; line-hieght: 25px;">CERTIFICATE OF ATTENDANCE</p></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center;"><br><p>This is to  certify that<br>
                <span style="font-size: 30px; line-hieght: 30px;">' . $name . '</span><br><br>
                Has successfully completed a 5 hour HHA workshop<br> and is now a recognised HHA Compliance Auditor & Assessor.</p>
            </td>
        </tr>
        <tr>
            <td><br><br><br><br><br><br><br><br><br><br><h2 style="text-align: center;">' . $date . '</h2></td>
            <td>'. html_writer::tag('p', $image1, array('style' => 'text-align: center;')) .'</td>
            <td><br><br><br>'. html_writer::tag('p', $image2, array('style' => 'text-align: center;')) .' <h2 style="text-align: center">HHA Project Manager</h2></td>
        </tr>
    </table>
');