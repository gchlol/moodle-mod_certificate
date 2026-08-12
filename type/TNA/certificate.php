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
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

$userid = (int) $USER->id;
$questid = optional_param('questid', 0, PARAM_INT);
$level = optional_param('level', 'self', PARAM_TEXT);
if (!in_array($level, array('self', 'others', 'leaders', 'org'), true)) {
    throw new invalid_parameter_exception('Invalid certificate level.');
}

$user = $USER;
$role = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => '9')); 
$roletitle = ucwords(strtolower($role->data));
$quiz = $DB->get_record('questionnaire', array('id' => $questid, 'course' => $course->id), '*', MUST_EXIST);
$response = $DB->get_record('questionnaire_response',
    array('userid' => $userid, 'questionnaireid' => $questid), '*', MUST_EXIST);
$responsedate=$response->submitted;
$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle(get_string('tnaresultstitle', 'mod_certificate', fullname($user)));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();
$page = 1;

// Define variables

    $x = 10;
    $y = 10;
    $sealx = 175;
    $sealy = 5;
    $sealw = 25;
    $sealh = 25;
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
	$head1y = 70;
	$head2y = 140;
	$head3y = 210;
	$box1 = 44;
	$box2 = 92;
	$box3 = 140;
	$box4 = 188;
	$box5 = 235;



$pdf->Image("$CFG->dirroot/mod/certificate/type/TNA/TNA certificate.jpg", $brdrx, $brdry, $brdrw, $brdrh);
switch ($level) {
			case "self":
				$uploadpath = "$CFG->dirroot/mod/certificate/type/TNA/Lead self.png";
				break;
			case "others":
				$uploadpath = "$CFG->dirroot/mod/certificate/type/TNA/Lead others.png";
				break;
			case "leaders":
				$uploadpath = "$CFG->dirroot/mod/certificate/type/TNA/Lead leaders.png";
				break;
			case "org":
				$uploadpath = "$CFG->dirroot/mod/certificate/type/TNA/Lead organisation.png";
				break;
}
$pdf->Image($uploadpath, $sealx, $sealy, $sealw, $sealh);

$pdf->SetTextColor(256, 256, 256);
certificate_print_text($pdf, $x, 4, 'L', 'Helvetica', '' ,20,
        get_string('tnacorecapabilityprofile', 'mod_certificate', fullname($user)));
certificate_print_text($pdf, $x, 15, 'L', 'Helvetica', '' ,12,
        userdate($responsedate, get_string('strftimedate', 'langconfig')));
certificate_print_text($pdf, $x+74, 15, 'L', 'Helvetica', '' ,12, $roletitle);
certificate_print_text($pdf, $x, 24, 'L', 'Helvetica', '' ,9,
        get_string('tnaresultsintro', 'mod_certificate'), 145);
certificate_print_text($pdf, $x+29, 281, 'L', 'Helvetica', '' ,8,
        get_string('tnacriticalmarker', 'mod_certificate'));

$pdf->SetTextColor(0, 0, 0);


/**
Questionnaire
IF type_id=8 (Rate Scale) - (51420) (mdl_questionnaire_resp_rank) response_id, question_id, choice_id (mdl_questionnaire_quest_choice) - content, id

*/



$questions=$DB->get_records(questionnaire_question, array('surveyid' => $quiz->sid, 'deleted' => 'n'), $sorted='position');


FOREACH ($questions AS $question) {
//For each of the CCF Questions

	if ($question->position==3) {
		numbox($box1, $response->id, $question->id);
		}
	if ($question->position==4) {
		printresp($box1, get_string('tnapersonalattributes', 'mod_certificate'),
				get_string('tnapersonalattributesdescription', 'mod_certificate'), $response->id, $question->id);
		}

	if ($question->position==6) {
		numbox($box2, $response->id, $question->id);
		}

	if ($question->position==7) {
		printresp($box2, get_string('tnabuildrelationships', 'mod_certificate'),
				get_string('tnabuildrelationshipsdescription', 'mod_certificate'), $response->id, $question->id);
		}

	if ($question->position==9) {
		numbox($box3, $response->id, $question->id);
		}

	if ($question->position==10) {
		printresp($box3, get_string('tnaresultsfocused', 'mod_certificate'),
				get_string('tnaresultsfocuseddescription', 'mod_certificate'), $response->id, $question->id);
		}

	if ($question->position==12) {
		numbox($box4, $response->id, $question->id);
		}

	if ($question->position==13) {
		printresp($box4, get_string('tnabusinessenablers', 'mod_certificate'),
				get_string('tnabusinessenablersdescription', 'mod_certificate'), $response->id, $question->id);
		}

	if ($question->position==15) {
		numbox($box5, $response->id, $question->id);
		}

	if ($question->position==16) {
		printresp($box5, get_string('tnaleadershippeoplemanagement', 'mod_certificate'),
				get_string('tnaleadershippeoplemanagementdescription', 'mod_certificate'), $response->id, $question->id);
		}


}

$pdf->AddPage();
$pdf->Image("$CFG->dirroot/mod/certificate/type/TNA/TNA certificate.jpg", $brdrx, $brdry, $brdrw, $brdrh);
$pdf->SetTextColor(256, 256, 256);
certificate_print_text($pdf, $x, 6, 'L', 'Helvetica', '' ,20,
        get_string('tnadevelopmentopportunities', 'mod_certificate', fullname($user)));
certificate_print_text($pdf, $x, $y+9, 'l', 'Helvetica', '' ,12,
        get_string('tnadevelopmentprompt', 'mod_certificate'), 95);


certificate_print_text($pdf, $x+110, $y+9, 'l', 'Helvetica', 'i' ,9,
        get_string('tnamodel70', 'mod_certificate'));
certificate_print_text($pdf, $x+110, $y+14, 'l', 'Helvetica', 'i' ,9,
        get_string('tnamodel20', 'mod_certificate'));
certificate_print_text($pdf, $x+110, $y+19, 'l', 'Helvetica', 'i' ,9,
        get_string('tnamodel10', 'mod_certificate'));

$pdf->SetTextColor(0, 0, 0);


FOREACH ($questions AS $question) {
//For each of the CCF Questions
	if ($question->position==2) {
		printblank($box1, get_string('tnapersonalattributes', 'mod_certificate'));
		}

	if ($question->position==5) {
		printblank($box2, get_string('tnabuildrelationships', 'mod_certificate'));
		}

	if ($question->position==8) {
		printblank($box3, get_string('tnaresultsfocused', 'mod_certificate'));
		}

	if ($question->position==11) {
		printblank($box4, get_string('tnabusinessenablers', 'mod_certificate'));
		}

	if ($question->position==14) {
		printblank($box5, get_string('tnaleadershippeoplemanagement', 'mod_certificate'));
		}


}


//Functions
function printresp($box, $title, $desc, $respid, $questid) {
	GLOBAL $DB, $pdf, $x, $y, $certificate, $brdrx, $brdry, $brdrw, $brdrh;

	certificate_print_text($pdf, $x+32, $box, 'C', 'Helvetica', 'B' ,16, $title);
	certificate_print_text($pdf, $x+32, $box+8, 'l', 'Helvetica', '' ,9, $desc);

	$responses=$DB->get_records_sql('SELECT * FROM {questionnaire_response_rank} qrr INNER JOIN {questionnaire_quest_choice} qqc ON qrr.choice_id=qqc.id AND qrr.question_id=? AND qrr.response_id=?',[$questid,$respid]);
	$count=1;
	FOREACH ($responses AS $resp) {
		switch ($resp->rankvalue) {
			case "1":
				$ans = get_string('tnaratingneedsdevelopment', 'mod_certificate');
				$pdf->SetTextColor(0, 0, 200);
				break;
			case "2":
				$ans = get_string('tnaratingalmostthere', 'mod_certificate');
				$pdf->SetTextColor(0, 0, 0);
				break;
			case "3":
				$ans = get_string('tnaratingcanapply', 'mod_certificate');
				$pdf->SetTextColor(0, 0, 0);
				break;
			case "4":
				$ans = get_string('tnaratingcancoach', 'mod_certificate');
				$pdf->SetTextColor(0, 0, 0);
				break;
			}
		certificate_print_text($pdf, $x+90, $box+12+($count*6), 'l', 'Helvetica', 'i' ,9, $ans);
		$pdf->SetTextColor(0, 0, 0);
		certificate_print_text($pdf, $x+32, $box+12+($count*6), 'l', 'Helvetica', '' ,9, $resp->content);
		$count++;
	}
		
		

}

function numbox($box, $respid, $questid) {
	GLOBAL $DB, $pdf, $x, $y, $certificate, $brdrx, $brdry, $brdrw, $brdrh;


	$responses=$DB->get_records_sql('SELECT * FROM {questionnaire_response_rank} qrr INNER JOIN {questionnaire_quest_choice} qqc ON qrr.choice_id=qqc.id AND qrr.question_id=? AND qrr.response_id=?',[$questid,$respid]);
	$count=1;
	FOREACH ($responses AS $resp) {
		switch ($resp->rankvalue) {
			case "1":
				$ans='';
				break;
			case "2":
				$ans='';
				break;
			case "3":
				$ans='';
				break;
			case "4":
				$ans='>';
				break;
			}
		$pdf->SetTextColor(255, 255, 255);
		certificate_print_text($pdf, $x+28, $box+12+($count*6), 'l', 'Helvetica', 'i' ,9, $ans);
		$pdf->SetTextColor(0, 0, 0);
		$count++;
	}
		
		

}
function printblank($box, $title) {
	GLOBAL $DB, $pdf, $x, $y, $certificate, $brdrx, $brdry, $brdrw, $brdrh;

	certificate_print_text($pdf, $x+32, $box, 'C', 'Helvetica', 'B' ,16, $title);
	certificate_print_text($pdf, $x+32, $box+9, 'l', 'Helvetica', '' ,9,
			get_string('tnaactivities70', 'mod_certificate') . ' ________________________________________________________________');
	certificate_print_text($pdf, $x+32, $box+15, 'l', 'Helvetica', '' ,9, "_______________________________________________________________________________");
	certificate_print_text($pdf, $x+32, $box+21, 'l', 'Helvetica', '' ,9,
			get_string('tnaactivities20', 'mod_certificate') . ' ________________________________________________________________');
	certificate_print_text($pdf, $x+32, $box+27, 'l', 'Helvetica', '' ,9, "_______________________________________________________________________________");
	certificate_print_text($pdf, $x+32, $box+33, 'l', 'Helvetica', '' ,9,
			get_string('tnaactivities10', 'mod_certificate') . ' ________________________________________________________________');
	certificate_print_text($pdf, $x+32, $box+39, 'l', 'Helvetica', '' ,9, "_______________________________________________________________________________");
		
		

}
