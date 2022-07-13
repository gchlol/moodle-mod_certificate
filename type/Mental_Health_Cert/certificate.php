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
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');



$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();
$page=1;

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
    $y = 68;
    $sealx = 32;
    $sealy = 78;
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
	$datex = 20;
	$datey = 254;
}



 /*PB Insert code for multiple courses
 */
	$where = "WHERE u.id = ".$USER->id." AND s.course = 275";
	$sql = "SELECT s.name AS 'fullname', cmc.timemodified AS 'timecompleted'
                                   FROM {user} u
                                   INNER JOIN {course_modules_completion} cmc
                                   ON u.id=cmc.userid AND cmc.completionstate = 1
                                   INNER JOIN {course_modules} cm
                                   ON cm.id=cmc.coursemoduleid
									INNER JOIN {scorm} s
									ON s.id=cm.instance AND s.course=cm.course
									$where
                                   ";

	   $completion = $DB->get_records_sql($sql);
/*		if (!$completion) {
        print_error(get_string('notissuedyet','certificate'));
    } 
	
	else {
*/	
				//Print Heading
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
						certificate_print_text($pdf, $x, $y + 15, 'C', 'Helvetica', '', 37.5, 'Gold Coast Health');
						certificate_print_text($pdf, $x, $y + 29, 'C', 'Helvetica', '', 37.5, 'Mental Health');
						$pdf->SetTextColor(128, 128, 128);
						certificate_print_text($pdf, $x, $y + 60, 'C', 'Helvetica', '', 16, 'This is to certify that');
						$pdf->SetTextColor(0, 60, 105);
						certificate_print_text($pdf, $x, $y + 70, 'C', 'Helvetica', 'B', 32, fullname($USER));
						$pdf->SetTextColor(128, 128, 128);
						certificate_print_text($pdf, $x, $y + 90, 'C', 'Helvetica', '', 16, 'has completed the following');
						$pdf->SetTextColor(0, 0, 0);
						certificate_print_text($pdf, $datex, $datey, 'R', 'Helvetica', '', 10, 'Printed on ' . date('j F Y'));
						$pdf->SetTextColor(128, 128, 128);
						certificate_print_text($pdf, $x, $y + 190, 'C', 'Helvetica', '', 16, 'Presented by');
						$pdf->SetTextColor(0, 60, 105);
						certificate_print_text($pdf, $x, $y + 196, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
						certificate_print_text($pdf, $x, $y + 102, 'C', 'Times', '', 10, certificate_get_grade($certificate, $course));
						certificate_print_text($pdf, $x, $y + 112, 'C', 'Times', '', 10, certificate_get_outcome($certificate, $course));
						if ($certificate->printhours) {
							certificate_print_text($pdf, $x, $y + 122, 'C', 'Times', '', 10, get_string('credithours', 'certificate') . ': ' . $certificate->printhours);
						}
						certificate_print_text($pdf, $x, $codey, 'C', 'Times', '', 10, certificate_get_code($certificate, $certrecord));
						$pdf->SetTextColor(0, 0, 0);


		//Print Details
			$k=0;
			foreach ($completion as $complete1) {
				$comtime = $complete1->timecompleted;
				$class = $complete1->fullname;
				$k++;
				if (!$comtime) {
				}
				else {
					if ($page<2) {
						if ($k<16) {
							// Just print results
							$comtim2 = userdate($comtime, get_string('strftimedate'));
							certificate_print_text($pdf, $x+10, $y + 100 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
							certificate_print_text($pdf, $x+145, $y + 100 + ($k * 4), 'R', 'Helvetica', '', 10, $comtim2); 
							}
						else {
							// Create new page and print results
							certificate_print_text($pdf, $x, $datey, 'C', 'Helvetica', '', 10, 'Page '.$page.' of '.$pageno);
							$page++;
							$k=0;
							$pdf->AddPage();
							// Add images and lines
							certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
							certificate_draw_frame($pdf, $certificate);
							$comtim2 = userdate($comtime, get_string('strftimedate'));
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Mental Health');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Agreements (cont) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y  + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
							certificate_print_text($pdf, $x+145, $y + 20 + ($k * 4), 'R', 'Helvetica', '', 10, $comtim2); 
							certificate_print_text($pdf, $datex, $datey, 'R', 'Helvetica', '', 10, 'Printed on ' . date('j F Y'));
							$pdf->SetTextColor(128, 128, 128);
							certificate_print_text($pdf, $x, $y + 190, 'C', 'Helvetica', '', 16, 'Presented by');
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y + 196, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y + 102, 'C', 'Times', '', 10, certificate_get_grade($certificate, $course));
							certificate_print_text($pdf, $x, $y + 112, 'C', 'Times', '', 10, certificate_get_outcome($certificate, $course));
							if ($certificate->printhours) {
								certificate_print_text($pdf, $x, $y + 122, 'C', 'Times', '', 10, get_string('credithours', 'certificate') . ': ' . $certificate->printhours);
							}
							certificate_print_text($pdf, $x, $codey, 'C', 'Times', '', 10, certificate_get_code($certificate, $certrecord));
							$pdf->SetTextColor(0, 0, 0);
}
						}
					else {
						if($k<30){
							// Just print results
							$comtim2 = userdate($comtime, get_string('strftimedate'));
							certificate_print_text($pdf, $x+10, $y  + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
							certificate_print_text($pdf, $x+145, $y + 20 + ($k * 4), 'R', 'Helvetica', '', 10, $comtim2); 
							}
						else {
							// Create new page and print results
							certificate_print_text($pdf, $x, $datey, 'C', 'Helvetica', '', 10, 'Page '.$page.' of '.$pageno);
							$page++;
							$k=0;
							$pdf->AddPage();
							// Add images and lines
							certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
							certificate_draw_frame($pdf, $certificate);
							$comtim2 = userdate($comtime, get_string('strftimedate'));
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Mental Health');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Agreements (cont) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y  + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
							certificate_print_text($pdf, $x+145, $y + 20 + ($k * 4), 'R', 'Helvetica', '', 10, $comtim2); 
							certificate_print_text($pdf, $datex, $datey, 'R', 'Helvetica', '', 10, 'Printed on ' . date('j F Y'));
							$pdf->SetTextColor(128, 128, 128);
							certificate_print_text($pdf, $x, $y + 190, 'C', 'Helvetica', '', 16, 'Presented by');
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y + 196, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y + 102, 'C', 'Times', '', 10, certificate_get_grade($certificate, $course));
							certificate_print_text($pdf, $x, $y + 112, 'C', 'Times', '', 10, certificate_get_outcome($certificate, $course));
							if ($certificate->printhours) {
								certificate_print_text($pdf, $x, $y + 122, 'C', 'Times', '', 10, get_string('credithours', 'certificate') . ': ' . $certificate->printhours);
							}
							certificate_print_text($pdf, $x, $codey, 'C', 'Times', '', 10, certificate_get_code($certificate, $certrecord));
							$pdf->SetTextColor(0, 0, 0);
							}
						}	

					}
					
					}


					if ($page>1) {
					certificate_print_text($pdf, $x, $datey, 'C', 'Helvetica', '', 10, 'Page '.$page.' of '.$pageno);
					}

					
						$i = 0;


					

			


 ?>