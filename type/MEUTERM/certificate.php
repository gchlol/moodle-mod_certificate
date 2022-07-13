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

$userid = optional_param('userid', $USER->id, PARAM_INT);
$responseid = optional_param('responseid', 0, PARAM_INT);
$user = $DB->get_record('user', array('id' => $userid));
$response = $DB->get_record('questionnaire_response', array('id' => $responseid));
$responsedate=$response->submitted;

$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle('Intern Assessment - '.$user->fullname);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();
$page = 1;

// Define variables

    $x = 10;
    $y = 10;
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
	$head1y = 70;
	$head2y = 140;
	$head3y = 210;
	$box1y = 75;
	$box2y = 145;
	$box3y = 215;
	$box1x = 15;
	$box2x = 115;



certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);


/**
Questionnaire
Print Name, Payroll ID, date of submission
SET IPAP=NO
Get list of questions (mdl_questionnaire_question.content)and ID (mdl_questionnaire_question.id) and type (mdl_questionnaire_question.type.id) order by position (mdl_questionnaire_question.position)
WHERE deleted=n AND type_id IS NOT 99

If type_id=100 THEN JUST PRINT content
IF type_id=1 (YesNo) - (29750) (mdl_questionnaire_response_bool) response_id, questionid, choice_id (y)
IF type_id=2 (Text Box) - (22341) (mdl_questionnaire_response_text) response_id, question_id, response
IF type_id=3 (Essay Box) - (22345) (mdl_questionnaire_response_text) response_id, question_id, response
IF type_id=4 (Radio Button) - (22384) (mdl_questionnaire_resp_single) response_id, question_id, choice_id (mdl_questionnaire_quest_choice) - content, id
       IF LEFT content is 1 or 2, then IPAP = YES
IF type_id=5 (Checkboxes) - (22335)  (mdl_questionnaire_resp_multiple) response_id, question_id, choice_id (mdl_questionnaire_quest_choice) - content, id
IF type_id=6 (Dropdown) - (22333)  (mdl_questionnaire_resp_single) response_id, question_id, choice_id  (mdl_questionnaire_quest_choice) - content, id
IF IPAP=Yes, Print IPAP warning
Add text for "This is an accurate report"
Print box for DCT comments
Print box for DCT signing

*/

$IPAP = 0;
// certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);

$questionnaire=$DB->get_record(questionnaire, array('id' => $response->questionnaireid));
$questions=$DB->get_records(questionnaire_question, array('surveyid' => $questionnaire->sid, 'deleted' => 'n'), $sorted='position');


FOREACH ($questions AS $question) {
//Get information for the header
	if (strpos($question->content, 'your AHPRA') !== false) {
		$AHPRA=$DB->get_record(questionnaire_response_text, array('response_id' => $responseid, 'question_id' => $question->id));
		}

	if (strpos($question->content, 'your level of training') !== false) {
		$levelchoice=$DB->get_record(questionnaire_resp_multiple, array('response_id' => $responseid, 'question_id' => $question->id));
		$level=$DB->get_record(questionnaire_quest_choice, array('id' => $levelchoice->choice_id));
		}

	if (strpos($question->content, 'is being completed') !== false) {
		$compchoice=$DB->get_record(questionnaire_resp_single, array('response_id' => $responseid, 'question_id' => $question->id));
		$comp=$DB->get_record(questionnaire_quest_choice, array('id' => $compchoice->choice_id));
		}

	if (strpos($question->content, 'Select the term') !== false) {
		$termchoice=$DB->get_record(questionnaire_resp_multiple, array('response_id' => $responseid, 'question_id' => $question->id));
		$term=$DB->get_record(questionnaire_quest_choice, array('id' => $termchoice->choice_id));
		}

	if (strpos($question->content, 'the term was undertaken') !== false) {
		$teamchoice=$DB->get_record(questionnaire_resp_single, array('response_id' => $responseid, 'question_id' => $question->id));
		$team=$DB->get_record(questionnaire_quest_choice, array('id' => $teamchoice->choice_id));
		printhead(fullname($user), $level->content, $AHPRA->response, $term->content, $responsedate, $comp->content, $team->content);
		}

//Domain 1:

	if (strpos($question->content, '1.1') !== false) {
		printbox ($question->id, $question->content, $box1x, $box1y);
	}

	if (strpos($question->content, 'Comments on Domain 1') !== false) {
		$DOM1=$DB->get_record(questionnaire_response_text, array('response_id' => $responseid, 'question_id' => $question->id));
		certificate_print_text($pdf, $x+2, $head1y, 'l', 'Helvetica', 'B', 12, 'Domain 1: Science and scholarship - The '.$level->content.' as scientist and scholar' );
		printcommentbox($question->content, $DOM1->response, $box2x, $box1y);

		}
//Domain 2:
		if (strpos($question->content, '2.1') !== false) {
		certificate_print_text($pdf, $x+2, $head2y, 'l', 'Helvetica', 'B', 12, 'Domain 2: Clinical practice - The '.$level->content.' as practitioner' );
		printbox ($question->id, $question->content, $box1x, $box2y);
	}

		if (strpos($question->content, '2.2') !== false) {
		printbox ($question->id, $question->content, $box2x, $box2y);
	}
		if (strpos($question->content, '2.3') !== false) {
		printbox ($question->id, $question->content, $box1x, $box3y);
	}
		if (strpos($question->content, '2.4') !== false) {
		printbox ($question->id, $question->content, $box2x, $box3y);
	}
	if (strpos($question->content, '2.5') !== false) {
		$pdf->AddPage();
		certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
		printhead(fullname($user), $level->content, $AHPRA->response, $term->content, $responsedate, $comp->content, $team->content);
		certificate_print_text($pdf, $x+2, $head1y, 'l', 'Helvetica', 'B', 12, 'Domain 2: Clinical practice - The '.$level->content.' as practitioner' );
		printbox ($question->id, $question->content, $box1x, $box1y);
	}
		if (strpos($question->content, '2.6') !== false) {
		printbox ($question->id, $question->content, $box2x, $box1y);
	}
		if (strpos($question->content, '2.7') !== false) {
		printbox ($question->id, $question->content, $box1x, $box2y);
	}
		if (strpos($question->content, '2.8') !== false) {
		printbox ($question->id, $question->content, $box2x, $box2y);
	}
		if (strpos($question->content, '2.9') !== false) {
		printbox ($question->id, $question->content, $box1x, $box3y);
	}
	if (strpos($question->content, 'Comments on Domain 2') !== false) {
		$DOM1=$DB->get_record(questionnaire_response_text, array('response_id' => $responseid, 'question_id' => $question->id));
		printcommentbox($question->content, $DOM1->response, $box2x, $box3y);
		}
//Domain 3	
	if (strpos($question->content, '3.1') !== false) {
		$pdf->AddPage();
		certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
		printhead(fullname($user), $level->content, $AHPRA->response, $term->content, $responsedate, $comp->content, $team->content);
		certificate_print_text($pdf, $x+2, $head1y, 'l', 'Helvetica', 'B', 12, 'Domain 3: Health and society - The '.$level->content.' as a health advocate' );
		printbox ($question->id, $question->content, $box1x, $box1y);
	}
		if (strpos($question->content, '3.2') !== false) {
		printbox ($question->id, $question->content, $box2x, $box1y);
	}
		if (strpos($question->content, '3.3') !== false) {
		printbox ($question->id, $question->content, $box1x, $box2y);
	}
		if (strpos($question->content, '3.4') !== false) {
		printbox ($question->id, $question->content, $box2x, $box2y);
	}
	if (strpos($question->content, 'Comments on Domain 3') !== false) {
		$DOM1=$DB->get_record(questionnaire_response_text, array('response_id' => $responseid, 'question_id' => $question->id));
		printcommentbox($question->content, $DOM1->response, $box1x, $box3y);
		}
//Domain 4
	if (strpos($question->content, '4.1') !== false) {
		$pdf->AddPage();
		certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
		printhead(fullname($user), $level->content, $AHPRA->response, $term->content, $responsedate, $comp->content, $team->content);
		certificate_print_text($pdf, $x+2, $head1y, 'l', 'Helvetica', 'B', 12, 'Domain 4: Professionalism and leadership - The '.$level->content.' as a professional and leader' );
		printbox ($question->id, $question->content, $box1x, $box1y);
	}
		if (strpos($question->content, '4.2') !== false) {
		printbox ($question->id, $question->content, $box2x, $box1y);
	}
		if (strpos($question->content, '4.3') !== false) {
		printbox ($question->id, $question->content, $box1x, $box2y);
	}
		if (strpos($question->content, '4.4') !== false) {
		printbox ($question->id, $question->content, $box2x, $box2y);
	}
		if (strpos($question->content, '4.5') !== false) {
		printbox ($question->id, $question->content, $box1x, $box3y);
	}
		if (strpos($question->content, '4.6') !== false) {
		printbox ($question->id, $question->content, $box2x, $box3y);
	}
	if (strpos($question->content, 'Comments on Domain 4') !== false) {
		$DOM1=$DB->get_record(questionnaire_response_text, array('response_id' => $responseid, 'question_id' => $question->id));
		$pdf->AddPage();
		certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
		printhead(fullname($user), $level->content, $AHPRA->response, $term->content, $responsedate, $comp->content, $team->content);
		certificate_print_text($pdf, $x+2, $head1y, 'l', 'Helvetica', 'B', 12, 'Domain 4: Professionalism and leadership - The '.$level->content.' as a professional and leader' );
		printcommentbox($question->content, $DOM1->response, $box1x, $box1y);
		}
	
//IPAP
	if (strpos($question->content, '(IPAP)') !== false && ($question->type_id == 1)) {
		$IPAPq=$DB->get_record(questionnaire_response_bool, array('response_id' => $responseid, 'question_id' => $question->id));
		if ($IPAPq->choice_id=="y" || $IPAP>0) {
		certificate_print_text($pdf, $box2x-5, $box1y, 'l', 'Helvetica', 'B', 10, substr($question->content,0,90), 85);
		$pdf->SetTextColor(256, 0, 0);
		certificate_print_text($pdf, $box2x, $box1y+10, 'l', 'Helvetica', 'B', 10, 'IPAP is required for '.fullname($user));
		$pdf->SetTextColor(0, 0, 0);
		
		} ELSE {
		certificate_print_text($pdf, $box2x-5, $box1y, 'l', 'Helvetica', 'B', 10, substr($question->content,0,90), 85);
		certificate_print_text($pdf, $box2x, $box1y+10, 'l', 'Helvetica', '', 10, 'No IPAP required');
		}
	}
//Global rating
		if (strpos($question->content, 'global') !== false&& ($question->type_id == 5)) {
		printglobalrating ($question->id, $question->content, $box2x, $box1y);
	}
		if (strpos($question->content, 'Strengths') !== false) {
		$strengths=$DB->get_record(questionnaire_response_text, array('response_id' => $responseid, 'question_id' => $question->id));
		printglobalbox ($question->content, $strengths->response, $box1x, $box2y,1);
	}
		if (strpos($question->content, 'Areas for improvement') !== false) {
		$weakness=$DB->get_record(questionnaire_response_text, array('response_id' => $responseid, 'question_id' => $question->id));
		printglobalbox ($question->content, $weakness->response, $box1x, $box2y,2);
	}
//Supervisor details
		if (strpos($question->content, "Assessor's name") !== false) {
		$AssessName=$DB->get_record(questionnaire_response_text, array('response_id' => $responseid, 'question_id' => $question->id));
	}
		if (strpos($question->content, "Assessor's rol") !== false) {
		$AssessQ=$DB->get_record(questionnaire_resp_single, array('response_id' => $responseid, 'question_id' => $question->id));
		$Assessrole=$DB->get_record(questionnaire_quest_choice, array('id' => $AssessQ->choice_id));
	}
		if (strpos($question->content, "Assessor's AHPRA") !== false) {
		$AssessAHPRA=$DB->get_record(questionnaire_response_text, array('response_id' => $responseid, 'question_id' => $question->id));
	}
//Allows limited sharing
	if (strpos($question->content, 'Agreement for limited sharing') !== false) {
		$sharechoice=$DB->get_record(questionnaire_resp_single, array('response_id' => $responseid, 'question_id' => $question->id));
		$share=$DB->get_record(questionnaire_quest_choice, array('id' => $sharechoice->choice_id));
	SWITCH ($share->content) {
		CASE NULL:
			$pdf->SetTextColor(256, 0, 0);
			certificate_print_text($pdf, $box2x, $box2y+48, 'l', 'Helvetica', 'i' ,10, 'Sharing of this term assessment has not been completed by the '.$level->content.'.');
			$pdf->SetTextColor(0, 0, 0);
			break;
		CASE "I give permission":
			certificate_print_text($pdf, $box2x, $box2y+48, 'l', 'Helvetica', 'i' ,10, 'Sharing of this term assessment has been authorised by the '.$level->content.'.');
			break;
		CASE "I do not give permission":
			$pdf->SetTextColor(256, 0, 0);
			certificate_print_text($pdf, $box2x, $box2y+48, 'l', 'Helvetica', 'i' ,10, 'Sharing of this term assessment has not been authorised by the '.$level->content.'.');
			$pdf->SetTextColor(0, 0, 0);
			break;
		}
	}
}
//Signature box for Student and Supervisor
	certificate_print_text($pdf, $box2x-5, $box2y, 'l', 'Helvetica', 'B' ,12, 'Supervisor');
	certificate_print_text($pdf, $box2x, $box2y+5, 'l', 'Helvetica', '' ,10, 'Name: '.$AssessName->response);
	certificate_print_text($pdf, $box2x, $box2y+10, 'l', 'Helvetica', '' ,10, 'Position: '.$Assessrole->content);
	
	certificate_print_text($pdf, $box2x-5, $box2y+20, 'l', 'Helvetica', 'B' ,12, $level->content);
	certificate_print_text($pdf, $box2x, $box2y+25, 'l', 'Helvetica', '' ,10, 'Name: '.fullname($user));
	certificate_print_text($pdf, $box2x, $box2y+35, 'l', 'Helvetica', 'i' ,10, 'This document was electronically agreed to by the above named Supervisor on the '.date('j F Y',$responsedate).'.', 90);


//Signature and comment box for DCT

	certificate_print_text($pdf, $box1x-5, $box3y, 'l', 'Helvetica', 'B' ,12, 'Director of Clinical Training');
	certificate_print_text($pdf, $box1x, $box3y+10, 'l', 'Helvetica', '' ,10, 'Name');
	certificate_print_text($pdf, $box1x, $box3y+30, 'l', 'Helvetica', '' ,10, 'Signature');
	certificate_print_text($pdf, $box1x, $box3y+50, 'l', 'Helvetica', '' ,10, 'Date');

	printcommentbox('Director of Clinical Training comments:','', $box2x, $box3y);



//Print Appendix if Strengths or Weaknesses are too big.
	$appendix=0;
	if (strlen($strengths->response)>190) {
		$appendix++;
		$pdf->AddPage();
		printhead(fullname($user), $level->content, $AHPRA->response, $term->content, $responsedate, $comp->content, $team->content);
		certificate_print_text($pdf, $box1x, $box1y, 'l', 'Helvetica', 'B' ,16, 'Appendix '.$appendix.' - Strengths');
		certificate_print_text($pdf, $box1x, $box1y+20, 'l', 'Helvetica', 'I' ,10, $strengths->response,180);
	}
	if (strlen($weakness->response)>190) {
		$appendix++;
		$pdf->AddPage();
		printhead(fullname($user), $level->content, $AHPRA->response, $term->content, $responsedate, $comp->content, $team->content);
		certificate_print_text($pdf, $box1x, $box1y, 'l', 'Helvetica', 'B' ,16, 'Appendix '.$appendix.' - Areas for improvement');
		certificate_print_text($pdf, $box1x, $box1y+20, 'l', 'Helvetica', 'I' ,10, $weakness->response,180);
	}

$pdf->Output(fullname($user).'-'.$team->content.'-'.date('Y',$responsedate).'-'.$term->content.'-'.$comp->content.'.pdf'); 

//Functions
function printhead($name, $level, $regno, $tnum, $subdate, $term, $org) {
	GLOBAL $pdf, $x, $y, $certificate, $brdrx, $brdry, $brdrw, $brdrh;
	certificate_print_text($pdf, $x, $y+20, 'l', 'Helvetica', 'B' ,16, $level.' training - term assessment form');
	certificate_print_text($pdf, $x+2, $y+28, 'l', 'Helvetica', 'B', 12, $level.' details' );
	certificate_print_text($pdf, $x+107, $y+28, 'l', 'Helvetica', 'B', 12, 'Term details' );
	certificate_print_text($pdf, $x+5, $y+35, 'l', 'Helvetica', '', 10, $level.' name' );
	certificate_print_text($pdf, $x+35, $y+35, 'l', 'Helvetica', '', 10, $name);
	certificate_print_text($pdf, $x+110, $y+35, 'l', 'Helvetica', '' ,10, 'Term name:');
	certificate_print_text($pdf, $x+140, $y+35, 'l', 'Helvetica', '' ,10, $tnum.' - '.date('Y',$subdate));
	certificate_print_text($pdf, $x+5, $y+40, 'l', 'Helvetica', '', 10, 'AHPRA reg no:' );
	certificate_print_text($pdf, $x+35, $y+40, 'l', 'Helvetica', '' ,10, $regno);
	certificate_print_text($pdf, $x+110, $y+40, 'l', 'Helvetica', '' ,10, 'Assessment date:');
	certificate_print_text($pdf, $x+140, $y+40, 'l', 'Helvetica', '' ,10, date('j F Y',$subdate));
	certificate_print_text($pdf, $x+2, $y+48, 'l', 'Helvetica', 'B', 12, 'This form is being completed for:' );
	certificate_print_text($pdf, $x+70, $y+48, 'l', 'Helvetica', 'B' ,12, $term);
	certificate_print_text($pdf, $x+110, $y+45, 'l', 'Helvetica', '' ,10, 'Unit of term:');
	certificate_print_text($pdf, $x+140, $y+45, 'l', 'Helvetica', '' ,10, $org);
}

function printbox($qid, $qcontent, $boxx, $boxy) {
	GLOBAL $pdf, $responseid, $DB, $IPAP;
		$qchoice=$DB->get_record(questionnaire_resp_multiple, array('response_id' => $responseid, 'question_id' => $qid));
		$qans=$DB->get_records(questionnaire_quest_choice, array('question_id' => $qid));
		foreach($qans AS $ans) {
			switch (substr($ans->content,0,1)) {
				case "5":
					$ans5=$ans->content;
					break;
				case "4":
					$ans4=$ans->content;
					break;
				case "3":
					$ans3=$ans->content;
					break;
				case "2":
					$ans2=$ans->content;
					break;
				case "1":
					$ans1=$ans->content;
					break;
				case "N":
					$ans0=$ans->content;
					break;
				case "0":
					$ans0=$ans->content;
					break;
			}
		}	
		$q=$DB->get_record(questionnaire_quest_choice, array('id' => $qchoice->choice_id));

		switch (substr($q->content,0,2)) {
			case "N/":
				$posx=35;
				$posy=14;
				$B0='B';
				break;
			case "1:":
				$posx=-5;
				$posy=48;
				$B1='B';
				$IPAP++;
				break;
			case "2:":
				$posx=-5;
				$posy=41;
				$B2='B';
				$IPAP++;
				break;
			case "3:":
				$posx=-5;
				$posy=34;
				$B3='B';
				break;
			case "4:":
				$posx=-5;
				$posy=27;
				$B4='B';
				break;
			case "5:":
				$posx=-5;
				$posy=20;
				$B5='B';
				break;
		}

	certificate_print_text($pdf, $boxx+$posx, $boxy+$posy, l, 'Helvetica', 'B', 12, '>');
	certificate_print_text($pdf, $boxx, $boxy+20, l, 'Helvetica', $B5, 9, substr($ans5,0,2));
	certificate_print_text($pdf, $boxx+8, $boxy+20, l, 'Helvetica', $B5, 9, substr($ans5,2),78);
	certificate_print_text($pdf, $boxx, $boxy+27, l, 'Helvetica', $B4, 9, $ans4);
	certificate_print_text($pdf, $boxx, $boxy+34, l, 'Helvetica', $B3, 9, substr($ans3,0,2));
	certificate_print_text($pdf, $boxx+8, $boxy+34, l, 'Helvetica', $B3, 9, substr($ans3,2),78);
	certificate_print_text($pdf, $boxx, $boxy+41, l, 'Helvetica', $B2, 9, $ans2);
	certificate_print_text($pdf, $boxx, $boxy+48, l, 'Helvetica', $B1, 9, substr($ans1,0,2));
	certificate_print_text($pdf, $boxx+8, $boxy+48, l, 'Helvetica', $B1, 9, substr($ans1,2),78);
	certificate_print_text($pdf, $boxx+40, $boxy+15, l, 'Helvetica', $B0, 9, $ans0);



	certificate_print_text($pdf, $boxx-3, $boxy+1, 'l', 'Helvetica', 'I', 7, $qcontent,88);
	certificate_print_text($pdf, $boxx-5, $boxy+15, 'l', 'Helvetica', 'B', 9, 'Rating');
	certificate_print_text($pdf, $boxx+8, $boxy+15, 'l', 'Helvetica', 'B', 9, 'Description');

}

function printcommentbox($heading, $descr1, $boxx, $boxy) {
	GLOBAL $pdf;
	certificate_print_text($pdf, $boxx, $boxy, 'l', 'Helvetica', 'b' ,10, $heading, 80);
	certificate_print_text($pdf, $boxx, $boxy+5, 'l', 'Helvetica', '' ,10, $descr1, 80);

}

function printglobalbox($heading, $descr1, $boxx, $boxy,$num) {
	GLOBAL $pdf;
	switch ($num) {
		case (1):
			$offset=0;
			break;
		case (2):
			$offset=30;
			break;
	}
	if (strlen($descr1)>190) {
		$descr1="See appendix attached";
	}
	certificate_print_text($pdf, $boxx, $boxy+$offset, 'l', 'Helvetica', 'b' ,10, $heading, 80);
	certificate_print_text($pdf, $boxx, $boxy+5+$offset, 'l', 'Helvetica', '' ,10, $descr1, 80);

}

function printglobalrating($qid, $qcontent, $boxx, $boxy) {
	GLOBAL $pdf, $responseid, $DB, $IPAP;
		$qchoice=$DB->get_record(questionnaire_resp_multiple, array('response_id' => $responseid, 'question_id' => $qid));

		$qans=$DB->get_records(questionnaire_quest_choice, array('question_id' => $qid));
		$q=$DB->get_record(questionnaire_quest_choice, array('id' => $qchoice->choice_id));
		certificate_print_text($pdf, $boxx-3, $boxy+30, 'l', 'Helvetica', 'I', 10, $qcontent,88);
		certificate_print_text($pdf, $boxx, $boxy+45, l, 'Helvetica', B, 9, $q->content);
}
