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
$imagealt = get_string('hha', 'mod_certificate');
$image = html_writer::img($CFG->wwwroot . '/mod/certificate/type/HHA/image.jpg', $imagealt,
    array('width' => 500, 'height' => 150));
$image1 = html_writer::img($CFG->wwwroot . '/mod/certificate/type/HHA/hand.jpg', $imagealt,
    array('width' => 175, 'height' => 175));
$image2 = html_writer::img($CFG->wwwroot . '/mod/certificate/type/HHA/signature.png', $imagealt,
    array('width' => 100, 'height' => 100));

// Get the name of the staff member.
$name = fullname($USER);

//Get the completion Date.
$completion = $DB->get_record("course_completions", array('userid' => $USER->id, 'course' => $COURSE->id));
$date = userdate($completion->timecompleted, get_string('strftimedate', 'langconfig'));

$certificatetitle = get_string('certificateofattendance', 'mod_certificate');
$certify = get_string('certify', 'mod_certificate');
$workshopcompletion = get_string('hhaworkshopcompletion', 'mod_certificate');
$projectmanager = get_string('hhaprojectmanager', 'mod_certificate');

$pdf->writeHTML('
    <table>
        <tr>
            <td colspan="3">'. html_writer::tag('p', $image, array('style' => 'text-align: center;')) .'</td>
        </tr>
        <tr>
            <td colspan="3" style="border-top: 1px solid #000;border-bottom: 1px solid #000; text-align: center;"><p style="font-size: 25px; line-hieght: 25px;">' . $certificatetitle . '</p></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center;"><br><p>' . $certify . '<br>
                <span style="font-size: 30px; line-hieght: 30px;">' . $name . '</span><br><br>
                ' . $workshopcompletion . '</p>
            </td>
        </tr>
        <tr>
            <td><br><br><br><br><br><br><br><br><br><br><h2 style="text-align: center;">' . $date . '</h2></td>
            <td>'. html_writer::tag('p', $image1, array('style' => 'text-align: center;')) .'</td>
            <td><br><br><br>'. html_writer::tag('p', $image2, array('style' => 'text-align: center;')) .' <h2 style="text-align: center">' . $projectmanager . '</h2></td>
        </tr>
    </table>
');
