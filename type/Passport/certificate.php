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
    $sealx = 240;
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
    $sealx = 160;
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



 /*PB Insert code for multiple courses NOT Mandatory
 */
	$where = "WHERE cc.timecompleted>0 AND u.id = ".$USER->id." AND c.category<>96 AND c.category<>63 AND c.category<>9 AND c.idnumber<>'GCUH' AND c.category<>98 AND category<>102";
	$sql = "SELECT c.fullname, c.id, cc.timecompleted
                                   FROM {course_completions} cc
                                   INNER JOIN {user} u
                                   ON u.id = cc.userid
                                   INNER JOIN {course} c
                                   ON c.id = cc.course
									$where
                                   ORDER BY cc.timecompleted  DESC";
		$completion = $DB->get_records_sql($sql);
/*PB Insert code for multiple courses  Mandatory
 */
	$where1 = "WHERE cc.timecompleted>0 AND u.id = ".$USER->id." AND (c.category=63 OR c.category=9)";
	$sql1 = "SELECT c.fullname, c.id, cc.timecompleted
                                   FROM {course_completions} cc
                                   INNER JOIN {user} u
                                   ON u.id = cc.userid
                                   INNER JOIN {course} c
                                   ON c.id = cc.course
									$where1
                                   ORDER BY c.idnumber DESC, c.fullname  ASC";
		$completion1 = $DB->get_records_sql($sql1);

/*PB Insert code for multiple courses  Additional learning modules
 */
	$where2 = "WHERE cc.timecompleted>0 AND u.id = ".$USER->id." AND (c.category=98)";
	$sql2 = "SELECT c.fullname, c.id, cc.timecompleted
                                   FROM {course_completions} cc
                                   INNER JOIN {user} u
                                   ON u.id = cc.userid
                                   INNER JOIN {course} c
                                   ON c.id = cc.course
									$where2
                                   ORDER BY c.idnumber DESC, c.fullname  ASC";
		$completion2 = $DB->get_records_sql($sql2);

/*PB Insert code for multiple courses  Manager support modules
 */
	$where3 = "WHERE cc.timecompleted>0 AND u.id = ".$USER->id." AND (c.category=102)";
	$sql3 = "SELECT c.fullname, c.id, cc.timecompleted
                                   FROM {course_completions} cc
                                   INNER JOIN {user} u
                                   ON u.id = cc.userid
                                   INNER JOIN {course} c
                                   ON c.id = cc.course
									$where3
                                   ORDER BY c.idnumber DESC, c.fullname  ASC";
		$completion3 = $DB->get_records_sql($sql3);
		
		
		$pageno = (int)((count($completion)+count($completion1)+count($completion2)+count($completion3)-15)/30)+2;
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
						certificate_print_text($pdf, $x+10, $y + 8, 'L', 'Helvetica', '', 37.5, 'Gold Coast Health');
						certificate_print_text($pdf, $x+10, $y + 22, 'L', 'Helvetica', '', 37.5, 'Learning On-Line');
						certificate_print_text($pdf, $x+10, $y + 36, 'L', 'Helvetica', '', 37.5, 'Portfolio');
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


			$k=1;

//Print Details mandatory
		
			certificate_print_text($pdf, $x+10, $y + 100 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Mandatory courses');
			
			foreach ($completion1 as $complete1) {
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
							certificate_print_text($pdf, $x+12.5, $y + 100 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Mandatory courses (cont\'d)');
							$k++;
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Mandatory courses (cont\'d)');
							$k++;
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
			$k++;
			$k++;
//Check for new page required
					if ($page<2) {
						if ($k<15) {
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $datex, $datey, 'R', 'Helvetica', '', 10, 'Printed on ' . date('j F Y'));
							$pdf->SetTextColor(128, 128, 128);
							certificate_print_text($pdf, $x, $y + 190, 'C', 'Helvetica', '', 16, 'Presented by');
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y + 196, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
						}
					}
					else {
						if($k<29){
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $datex, $datey, 'R', 'Helvetica', '', 10, 'Printed on ' . date('j F Y'));
							$pdf->SetTextColor(128, 128, 128);
							certificate_print_text($pdf, $x, $y + 190, 'C', 'Helvetica', '', 16, 'Presented by');
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y + 196, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							}
						}	
// End check new page

//Print details non mandatory
			if ($page==1){
				certificate_print_text($pdf, $x+10, $y + 100 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Other courses');
				}
			else {
				certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Other courses');
			}

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
							certificate_print_text($pdf, $x+12.5, $y + 100 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Other courses (cont\'d)');
							$k++;
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Other courses (cont\'d)');
							$k++;
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
				$k++;
				$k++;
//Check for new page required
					if ($page<2) {
						if ($k<15) {
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $datex, $datey, 'R', 'Helvetica', '', 10, 'Printed on ' . date('j F Y'));
							$pdf->SetTextColor(128, 128, 128);
							certificate_print_text($pdf, $x, $y + 190, 'C', 'Helvetica', '', 16, 'Presented by');
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y + 196, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
						}
					}
					else {
						if($k<29){
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $datex, $datey, 'R', 'Helvetica', '', 10, 'Printed on ' . date('j F Y'));
							$pdf->SetTextColor(128, 128, 128);
							certificate_print_text($pdf, $x, $y + 190, 'C', 'Helvetica', '', 16, 'Presented by');
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y + 196, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							}
						}	
// End check new page
				
//Print details additional learning modules

			if ($page<2){
				certificate_print_text($pdf, $x+10, $y + 100 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Additional learning modules');
				}
			else {
				certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Additional learning modules');
			}
			
			foreach ($completion2 as $complete1) {
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
							certificate_print_text($pdf, $x+12.5, $y + 100 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Additional learning modules (cont\'d)');
							$k++;
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Additional learning modules (cont\'d)');
							$k++;
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
				$k++;
				$k++;
//Check for new page required
					if ($page<2) {
						if ($k<15) {
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $datex, $datey, 'R', 'Helvetica', '', 10, 'Printed on ' . date('j F Y'));
							$pdf->SetTextColor(128, 128, 128);
							certificate_print_text($pdf, $x, $y + 190, 'C', 'Helvetica', '', 16, 'Presented by');
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y + 196, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
						}
					}
					else {
						if($k<29){
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $datex, $datey, 'R', 'Helvetica', '', 10, 'Printed on ' . date('j F Y'));
							$pdf->SetTextColor(128, 128, 128);
							certificate_print_text($pdf, $x, $y + 190, 'C', 'Helvetica', '', 16, 'Presented by');
							$pdf->SetTextColor(0, 60, 105);
							certificate_print_text($pdf, $x, $y + 196, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							}
						}	
// End check new page

//Print details Manager support modules

		if (!empty($completion3)) {
			if ($page<2){
				certificate_print_text($pdf, $x+10, $y + 100 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Manager support modules');
				}
			else {
				certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Manager support modules');
			}
			
			foreach ($completion3 as $complete1) {
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
							certificate_print_text($pdf, $x+12.5, $y + 100 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Manager support modules (cont\'d)');
							$k++;
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
							certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 16, 'Gold Coast Health Learning On-Line');
							certificate_print_text($pdf, $x, $y+6, 'C', 'Helvetica', 'B', 12, 'Portfolio (cont\'d) for '.fullname($USER));
							$pdf->SetTextColor(0, 0, 0);
							certificate_print_text($pdf, $x+10, $y + 20 + ($k * 4), 'L', 'Helvetica', 'B', 10, 'Manager support modules (cont\'d)');
							$k++;
							certificate_print_text($pdf, $x+12.5, $y + 20 + ($k * 4), 'L', 'Helvetica', '', 10, $class); 
 
 
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
			}
					if ($page>1) {
					certificate_print_text($pdf, $x, $datey, 'C', 'Helvetica', '', 10, 'Page '.$page.' of '.$pageno);
					}

					
						$i = 0;


					

			


 ?>