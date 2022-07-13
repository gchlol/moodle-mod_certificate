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
 * Good Luck...
 * 
 */

defined('MOODLE_INTERNAL') || die();

// Require the certificate library.
require_once(__DIR__ . '/../lib.php');

// Global variables.
global $DB, $USER;

// Create a new PDF.
$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

// Setup the PDF.
$pdf->SetTitle($certificate->name);
$pdf->SetProtection(array('modify'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_FOOTER);
$pdf->SetMargins(10, 20, 10, true);

// If the certificate is in Landscape.
if ($certificate->orientation == 'L') {

    // Define some variables.
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
}
// Otherwise the certificate is in Portrait.
else {

    // Define some variables.
    $x = 8;
    $y = 10;
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
    $codey = 250;
}

// Set the content area.
$contentareaheight = 200;

// Get the current users position id.
$positionid = mod_certificate_get_position_id($USER->id);

// Get the report names for the position id.
$reports = mod_certificate_get_report_numbers($positionid);

//If the position ID has atleast 1 report.
if(!empty($reports)){

    //Do this for each report.
    foreach($reports as $report){

        // Get the Report Name.
        $reportName = mod_certificate_get_report_name($report);

        // Get the Report Owner.
        $reportOwner = mod_certificate_get_report_owner($report);

        // Get the Culture Champion.
        $cultureChampion = mod_certificate_get_culture_champion($reportName);

        // Get the Service Line.
        $serviceLine = mod_certificate_get_service_line($reportOwner);

        // Get the Division.
        $division = mod_certificate_get_division($reportOwner);

        // Add images and lines
        certificate_draw_frame($pdf, $certificate);

        // Set alpha to semi-transparency
        $pdf->SetAlpha(0.2);
        certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
        $pdf->SetAlpha(1);
        certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
        certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

        // Current Action number.
        $actionnum = 1;

        // The completed Actions.
        $completedactions = [];

        // Get the Initial actions.
        $sql = "SELECT qr.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND (qq.surveyid = 1316)
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content like '" . $report . "%'";

        // Get all of the initial Action responses related to the report.
        $initialActions = $DB->get_records_sql($sql);
        $initialActionsSurveyID = 1316;

        // Get the Quarterly Update 2 Actions.
        $sql = "SELECT qr.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND qq.surveyid = 1484
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content LIKE '" . $report . "%'";
        
        // Get all of the Q2 Action responses related to the report.
        $Q2Actions = $DB->get_records_sql($sql);
        $Q2ActionSurveyID = 1484;

        // Get the Quarterly Update 2 Updates.
        $sql = "SELECT qr.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND (qq.surveyid = 1475)
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content like '" . $report . "%'";

        // Get all of the Q2 Update Actions responses related to the report.
        $Q2Updates = $DB->get_records_sql($sql);
        $Q2UpdateSurveyID = 1475; 
        
        // Get the Quarterly Update 4 Actions.
        $sql = "SELECT qr.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND (qq.surveyid = 1664)
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content like '" . $report . "%'";
        
        // Get all of the Q4 Action responses related to the report.
        $Q4Actions = $DB->get_records_sql($sql);
        $Q4ActionSurveyID = 1664;

        // Get the Quarterly Update 4 Updates.
        $sql = "SELECT qr.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND (qq.surveyid = 1663)
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content like '" . $report . "%'";

        // Get all of the Q4 Update responses related to the report.
        $Q4Updates = $DB->get_records_sql($sql);
        $Q4UpdateSurveyID = 1663;

        // Get the Close Out Updates.
        $sql = "SELECT qr.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND (qq.surveyid = 1778)
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content like '" . $report . "%'";

        // Get all of the Close Out responses related to the report.
        $CloseOutUpdates = $DB->get_records_sql($sql);
        $CloseOutSurveyID = 1778;

        //Debug.
        /*echo html_writer::tag('pre', "Initial " . print_r($initialActions, true));
        echo html_writer::tag('pre', "Q2 Actions " . print_r($Q2Actions, true));
        echo html_writer::tag('pre', "Q2 Updates " . print_r($Q2Updates, true));
        echo html_writer::tag('pre', "Q4 Actions " . print_r($Q4Actions, true));
        echo html_writer::tag('pre', "Q4 Updates " . print_r($Q4Updates, true));
        echo html_writer::tag('pre', "Close Out Updates " . print_r($CloseOutUpdates, true));
        die;*/

        //If the CIAP has any Initial Actions.
        if(!empty($initialActions)){

            //echo html_writer::tag('pre', print_r($initialActions, true));die;

            // Do this for each action.
            foreach($initialActions as $action){

                // If we have already completed this action.
                if (in_array($action->id, $completedactions)) {

                    // Skip this action.
                    continue;
                }

                // Add a page to the certificate.
                $pdf->AddPage();

                // Add this action to the completed actions.
                $completedactions[] = $action->id;

                // The status of this action in Close Out.
                $CloseOutActionStatus = "Update not Provided";

                // The status of this action in Quater 4.
                $Q4ActionStatus = "Update not Provided";

                // The status of this action in Quater 2.
                $Q2ActionStatus = "Update not Provided";

                // The action's Close Out response id.
                $CloseOutResponseID = NULL;

                // The action's Q4 response id.
                $Q4UpdateResponseID = NULL;

                // The action's Q2 response id.
                $Q2UpdateResponseID = NULL;

                // Set the text color to blue.
                $pdf->SetTextColor(21, 128, 188);

                // Print the Page heading.
                certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 25, '2018 Going for Gold Staff Survey');
                
                // Set the text color to black.
                $pdf->SetTextColor(0, 0, 0);

                // Print the Report Name.
                certificate_print_text($pdf, $x, $y + 12, 'C', 'Helvetica', 'B', 14, $reportName);

                // Print the service line.
                certificate_print_text($pdf, $x, $y + 20, 'C', 'Helvetica', 'B', 14, $serviceLine);

                //Print the division.
                certificate_print_text($pdf, $x, $y + 28, 'C', 'Helvetica', 'B', 14, $division);
                
                // Print the Culture Champion.
                certificate_print_text($pdf, $x, $y + 36, 'C', 'Helvetica', 'B', 14, '2018 GFG Survey Culture Champion: ' . $cultureChampion);

                // Set the text color back to blue.
                $pdf->SetTextColor(21, 128, 188);

                // Print the CIAP Heading.
                certificate_print_text($pdf, $x, $y + 43, 'C', 'Helvetica', 'B', 14, "Close out of your team's");
                certificate_print_text($pdf, $x, $y + 50, 'C', 'Helvetica', 'B', 18, 'Continuous Improvement Action Plan');
                certificate_print_text($pdf, $x, $y + 60, 'C', 'Helvetica', 'B', 14, '2018 / 2019');
            
                // Print some space.
                $pdf->writeHTML('<br>');
                $pdf->writeHTML('<br>');

                // Print the Image.
                $image = html_writer::img($CFG->wwwroot . '/mod/certificate/type/CIAP/BACS.jpg', 'Going for Gold', array('width' => 455, 'height' => 50));
                
                // Center the Image.
                $pdf->writeHTML(html_writer::tag('p', $image, array('style' => 'text-align: center;')));

                // Do this for each Close Out Update.
                foreach($CloseOutUpdates as $update){

                    // Get the action number question from the database.
                    $updatenumberquestion = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'Action Number', 'deleted' => 'n'));

                    // Find what action this update is for.
                    $sql = "SELECT qqc.*
                            FROM {questionnaire_quest_choice} qqc
                            JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                            AND qqc.id = qrs.choice_id
                            AND qrs.response_id = $update->id
                            WHERE qqc.question_id = :questionid";

                    // Get the answer to the first question.
                    $updatenumber = $DB->get_record_sql($sql, array('questionid' => $updatenumberquestion->id));
                    
                    // Setup the Action Number.
                    $actionnumber = "Action " . $actionnum;
                    
                    // If the update is for this action.
                    if($updatenumber->content == $actionnumber){
                    
                        // Get the action status.
                        $updatestatusquestion = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'Action Status', 'deleted' => 'n'));
                    
                        // Find what staus this update is.
                        $sql = "SELECT qqc.*
                                FROM {questionnaire_quest_choice} qqc
                                JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                AND qqc.id = qrs.choice_id
                                AND qrs.response_id = $update->id
                                WHERE qqc.question_id = :questionid";

                        // Get the answer to the first question.
                        $updatestatus = $DB->get_record_sql($sql, array('questionid' => $updatestatusquestion->id));

                        // Set the actions status.
                        $CloseOutActionStatus = $updatestatus->content;

                        // Set the action's update id.
                        $CloseOutResponseID = $update->id;
                    }
                }
                
                // Do this for each Q4 update.
                foreach($Q4Updates as $update){

                    // Get the action number question from the database.
                    $updatenumberquestion = $DB->get_record('questionnaire_question', array('surveyid' => $Q4UpdateSurveyID, 'name' => 'Action Number', 'deleted' => 'n'));

                    // Find what action this update is for.
                    $sql = "SELECT qqc.*
                            FROM {questionnaire_quest_choice} qqc
                            JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                            AND qqc.id = qrs.choice_id
                            AND qrs.response_id = $update->id
                            WHERE qqc.question_id = :questionid";

                    // Get the answer to the first question.
                    $updatenumber = $DB->get_record_sql($sql, array('questionid' => $updatenumberquestion->id));
                    
                    // Setup the Action Number.
                    $actionnumber = "Action " . $actionnum;
                    
                    // If the update is for this action.
                    if($updatenumber->content == $actionnumber){
                    
                        // Get the action status.
                        $updatestatusquestion = $DB->get_record('questionnaire_question', array('surveyid' => $Q4UpdateSurveyID, 'name' => 'Action Status', 'deleted' => 'n'));
                    
                        // Find what staus this update is.
                        $sql = "SELECT qqc.*
                                FROM {questionnaire_quest_choice} qqc
                                JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                AND qqc.id = qrs.choice_id
                                AND qrs.response_id = $update->id
                                WHERE qqc.question_id = :questionid";

                        // Get the answer to the first question.
                        $updatestatus = $DB->get_record_sql($sql, array('questionid' => $updatestatusquestion->id));

                        // Set the actions status.
                        $Q4ActionStatus = $updatestatus->content;

                        // Set the action's update id.
                        $Q4UpdateResponseID = $update->id;
                    }
                }

                // Do this for each Q2 update.
                foreach($Q2Updates as $update){

                    // Get the action number question from the database.
                    $updatenumberquestion = $DB->get_record('questionnaire_question', array('surveyid' => $Q2UpdateSurveyID, 'name' => 'Action Number', 'deleted' => 'n'));

                    // Find what action this update is for.
                    $sql = "SELECT qqc.*
                            FROM {questionnaire_quest_choice} qqc
                            JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                            AND qqc.id = qrs.choice_id
                            AND qrs.response_id = $update->id
                            WHERE qqc.question_id = :questionid";

                    // Get the answer to the first question.
                    $updatenumber = $DB->get_record_sql($sql, array('questionid' => $updatenumberquestion->id));
                    
                    // Setup the Action Number.
                    $actionnumber = "Action " . $actionnum;
                    
                    // If the update is for this action.
                    if($updatenumber->content == $actionnumber){
                    
                        // Get the action status.
                        $updatestatusquestion = $DB->get_record('questionnaire_question', array('surveyid' => $Q2UpdateSurveyID, 'name' => 'Action Status', 'deleted' => 'n'));
                    
                        // Find what staus this update is.
                        $sql = "SELECT qqc.*
                                FROM {questionnaire_quest_choice} qqc
                                JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                AND qqc.id = qrs.choice_id
                                AND qrs.response_id = $update->id
                                WHERE qqc.question_id = :questionid";

                        // Get the answer to the first question.
                        $updatestatus = $DB->get_record_sql($sql, array('questionid' => $updatestatusquestion->id));

                        // Set the actions status.
                        $Q2ActionStatus = $updatestatus->content;

                        // Set the action's update id.
                        $Q2UpdateResponseID = $update->id;
                    }
                }

                // Set the Font.
                $pdf->SetFont('helvetica', 'B', '16');

                // If an update wasn't provided for the action.
                if($CloseOutActionStatus == "Update not Provided"){

                    // Print the current Action Number and Action Status.
                    $pdf->writeHTML('Action ' . $actionnum . " (<i>" . $CloseOutActionStatus . "</i>)");
                }
                else{

                    // Print the current Action Number.
                    $pdf->writeHTML('Action ' . $actionnum);
                }

                // Set the Color to black.
                $pdf->SetTextColor(0, 0, 0);

                // Get the first question from the database.
                $question1 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Start, Stop or Keep', 'deleted' => 'n'));
                
                // The SQL code.
                $sql = "SELECT qqc.content
                        FROM {questionnaire_quest_choice} qqc
                        JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                        AND qqc.id = qrs.choice_id
                        AND qrs.response_id = $action->id
                        WHERE qqc.question_id = :questionid";

                // Get the answer to the first question.
                $answer1 = $DB->get_record_sql($sql, array('questionid' => $question1->id));

                // Set the Font.
                $pdf->SetFont('helvetica', 'I', '14');

                // Print whether the action is Start, Stop or Keep.
                $pdf->writeHTML($answer1->content);
                $pdf->writeHTML('<br>');
                
                // Question 2.
                $question2 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Related to Pillar', 'deleted' => 'n'));
                $answer2 = $DB->get_record_sql($sql, array('questionid' => $question2->id));

                // Set the font.
                $pdf->SetFont('helvetica', 'B', '14');

                // Print the question.
                $pdf->writeHTML('What research program from the Staff Survey does this action link to?');
                
                // Set the font.
                $pdf->SetFont('helvetica', 'I', '14');

                // Print the answer.
                $pdf->writeHTML($answer2->content);

                // Print a space.
                $pdf->writeHTML('<br>');

                // Set the font.
                $pdf->SetFont('helvetica', 'B', '14');

                // Print the question.
                $pdf->writeHTML('What is the action in response to?');

                // Question 3
                $question3 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'What is it in response to', 'deleted' => 'n'));
                
                // The SQL query.
                $sql = "SELECT qqc.content
                        FROM {questionnaire_quest_choice} qqc
                        JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                        AND qrs.response_id = $action->id
                        AND qqc.id = qrs.choice_id
                        WHERE qqc.question_id = $question3->id";

                // Question 4
                $question4 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Survey Q it relates to', 'deleted' => 'n'));
                
                if ($answer4 = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $question4->id))) {
                    $pdf->SetFont('helvetica', '', '14');
                    $pdf->writeHTML('<i>Survey Question: ' . $answer4->response . '</i>');
                } else if ($answer5 = $DB->get_record_sql($sql)) {
                    $pdf->SetFont('helvetica', 'I', '14');
                    $pdf->writeHTML($answer5->content);
                }
                $pdf->writeHTML('<br>');

                // Question 6.
                $question6 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Describe Action', 'deleted' => 'n'));
                $answer6 = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $question6->id));
                $pdf->SetFont('helvetica', 'B', '14');
                $pdf->writeHTML('What is the action your team has agreed to?');
                $pdf->SetFont('helvetica', 'I', '14');
                $pdf->writeHTML(strip_tags($answer6->response, '<ul><li><ol>'));
                $pdf->writeHTML('<br>');

                // If the action is in progress.
                if($Q4ActionStatus == "In Progress" || $Q2ActionStatus == "In Progress" || $CloseOutActionStatus == "In Progress"){

                    // Whether the Implementation Date for this action has been amended.
                    $dateAmended = false;

                    // Whether the Person Responsible for this action has been amended.
                    $personAmended = false;

                    // If a Close Out Update was provided for this action.
                    if(!empty($CloseOutResponseID)){

                        // Get whether the Due Date was Amended.
                        $CloseOutDueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47923));

                        if(!empty($CloseOutDueDateAnswer)){

                            // If the Due Date was amended.
                            if($CloseOutDueDateAnswer->choice_id == 'y'){

                                // Print the new Due Date on the CIAP.
                                $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $CloseOutResponseID, 'question_id' => 47921));
                                $date = new DateTime($dueDate->response);
                                $pdf->SetFont('helvetica', '', '14');
                                $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                $pdf->writeHTML('<br>');

                                // Set dateAmended to true.
                                $dateAmended = true;
                            }
                        }

                        // Get whether the Person Responsible was Amended.
                        $CloseOutPersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47930));

                        if(!empty($CloseOutPersonResponsibleAnswer)){

                            // If the Person Responsible was amended.
                            if($CloseOutPersonResponsibleAnswer->choice_id == 'y'){

                                // Print the new Person Responsible on the CIAP.
                                $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => 47933));
                                $pdf->SetFont('helvetica', 'B', '14');
                                $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                $pdf->writeHTML('<br>');

                                // Set personAmended to true.
                                $personAmended = true;
                            }
                        }
                    }

                    // If a Q4 Update was provided for this action.
                    if(!empty($Q4UpdateResponseID)){

                        // If the date wasn't amended in Close Out.
                        if(!$dateAmended){

                            // Get whether the Due Date was Amended.
                            $Q4DueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45501));

                            if(!empty($Q4DueDateAnswer)){

                                // If the Due Date was amended.
                                if($Q4DueDateAnswer->choice_id == 'y'){

                                    // Print the new Due Date on the CIAP.
                                    $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45499));
                                    $date = new DateTime($dueDate->response);
                                    $pdf->SetFont('helvetica', '', '14');
                                    $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                    $pdf->writeHTML('<br>');

                                    // Set dateAmended to true.
                                    $dateAmended = true;
                                }
                            }
                        }

                        // If the person wasn't amended in Close Out.
                        if(!$personAmended){

                            // Get whether the Person Responsible was Amended.
                            $Q4PersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45508));

                            if(!empty($Q4PersonResponsibleAnswer)){

                                // If the Person Responsible was amended.
                                if($Q4PersonResponsibleAnswer->choice_id == 'y'){

                                    // Print the new Person Responsible on the CIAP.
                                    $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45511));
                                    $pdf->SetFont('helvetica', 'B', '14');
                                    $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                    $pdf->writeHTML('<br>');

                                    // Set personAmended to true.
                                    $personAmended = true;
                                }
                            }
                        }
                    }
                    
                    // If a Q2 Update was provided for this action.
                    if(!empty($Q2UpdateResponseID)){

                        // If the date wasn't amended in Q4 or Close Out.
                        if(!$dateAmended){

                            // Get whether the Due Date was Amended.
                            $Q2DueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q2UpdateResponseID, 'question_id' => 44575));

                            if(!empty($Q2DueDateAnswer)){

                                // If the Due Date was amended.
                                if($Q2DueDateAnswer->choice_id == 'y'){

                                    // Print the new Due Date on the CIAP.
                                    $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $Q2UpdateResponseID, 'question_id' => 44572));
                                    $date = new DateTime($dueDate->response);
                                    $pdf->SetFont('helvetica', '', '14');
                                    $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                    $pdf->writeHTML('<br>');

                                    // Set dateAmended to true.
                                    $dateAmended = true;
                                }
                            }
                        }

                        // If the person wasn't amended in Q4 or Close Out.
                        if(!$personAmended){

                            // Get whether the Person Responsible was Amended.
                            $Q2PersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q2UpdateResponseID, 'question_id' => 44593));

                            if(!empty($Q2PersonResponsibleAnswer)){

                                // If the Person Responsible was amended.
                                if($Q2PersonResponsibleAnswer->choice_id == 'y'){

                                    // Print the new Person Responsible on the CIAP.
                                    $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $Q2UpdateResponseID, 'question_id' => 44610));
                                    $pdf->SetFont('helvetica', 'B', '14');
                                    $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                    $pdf->writeHTML('<br>');

                                    // Set personAmended to true.
                                    $personAmended = true;
                                }
                            }
                        }
                    }

                    // If the Date wasn't amended in Close Out, Q4 or Q2
                    if(!$dateAmended){

                        // Print the original Due Date on the CIAP.
                        $question7 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Implemented by', 'deleted' => 'n'));
                        $answer7 = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => $question7->id));
                        $date = new DateTime($answer7->response);
                        $pdf->SetFont('helvetica', '', '14');
                        $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                        $pdf->writeHTML('<br>');
                    }

                    // If the Person wasn't amended in Close Out, Q4 or Q2
                    if(!$personAmended){

                        // Print the original Person Responsible on the CIAP.
                        $question8 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Responsible Person', 'deleted' => 'n'));
                        $answer8 = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $question8->id));
                        $pdf->SetFont('helvetica', 'B', '14');
                        $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $answer8->response . '</i></span>');
                        $pdf->writeHTML('<br>');
                    }
                }
                // If the action is in Not Yet Started.
                else if($Q4ActionStatus == "Not yet Started" || $Q2ActionStatus == "Not yet Started" || $CloseOutActionStatus == "Not yet Started"){

                    // Wheether the Implementation Date for this action has been amended.
                    $dateAmended = false;

                    // Whether the Person Responsible for this action has been amended.
                    $personAmended = false;

                    // If a Close Out Update was provided for this action.
                    if(!empty($CloseOutResponseID)){

                        // Get whether the Due Date was Amended.
                        $CloseOutDueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47925));

                        if(!empty($CloseOutDueDateAnswer)){

                            // If the Due Date was amended.
                            if($CloseOutDueDateAnswer->choice_id == 'y'){

                                // Print the new Due Date on the CIAP.
                                $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $CloseOutResponseID, 'question_id' => 47927));
                                $date = new DateTime($dueDate->response);
                                $pdf->SetFont('helvetica', '', '14');
                                $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                $pdf->writeHTML('<br>');

                                // Set dateAmended to true.
                                $dateAmended = true;
                            }
                        }

                        // Get whether the Person Responsible was Amended.
                        $CloseOutPersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47935));

                        if(!empty($CloseOutPersonResponsibleAnswer)){

                            // If the Person Responsible was amended.
                            if($CloseOutPersonResponsibleAnswer->choice_id == 'y'){

                                // Print the new Person Responsible on the CIAP.
                                $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => 47936));
                                $pdf->SetFont('helvetica', 'B', '14');
                                $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                $pdf->writeHTML('<br>');

                                // Set personAmended to true.
                                $personAmended = true;
                            }
                        }
                    }

                    // If a Q4 Update was provided for this action.
                    if(!empty($Q4UpdateResponseID)){

                        // If the date wasn't amended in Close Out.
                        if(!$dateAmended){

                            // Get whether the Due Date was Amended.
                            $Q4DueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45503));

                            if(!empty($Q4DueDateAnswer)){

                                // If the Due Date was amended.
                                if($Q4DueDateAnswer->choice_id == 'y'){

                                    // Print the new Due Date on the CIAP.
                                    $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45505));
                                    $date = new DateTime($dueDate->response);
                                    $pdf->SetFont('helvetica', '', '14');
                                    $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                    $pdf->writeHTML('<br>');

                                    // Set dateAmended to true.
                                    $dateAmended = true;
                                }
                            }
                        }

                        // If the person wasn't amended in Close Out.
                        if(!$personAmended){

                            // Get whether the Person Responsible was Amended.
                            $Q4PersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45513));

                            if(!empty($Q4PersonResponsibleAnswer)){

                                // If the Person Responsible was amended.
                                if($Q4PersonResponsibleAnswer->choice_id == 'y'){

                                    // Print the new Person Responsible on the CIAP.
                                    $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45514));
                                    $pdf->SetFont('helvetica', 'B', '14');
                                    $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                    $pdf->writeHTML('<br>');

                                    // Set personAmended to true.
                                    $personAmended = true;
                                }
                            }
                        }
                    }
                    
                    // If a Q2 Update was provided for this action.
                    if(!empty($Q2UpdateResponseID)){

                        // If the date wasn't amended in Q4 or Close Out.
                        if(!$dateAmended){

                            // Get whether the Due Date was Amended.
                            $Q2DueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q2UpdateResponseID, 'question_id' => 44577));

                            if(!empty($Q2DueDateAnswer)){

                                // If the Due Date was amended.
                                if($Q2DueDateAnswer->choice_id == 'y'){

                                    // Print the new Due Date on the CIAP.
                                    $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $Q2UpdateResponseID, 'question_id' => 44580));
                                    $date = new DateTime($dueDate->response);
                                    $pdf->SetFont('helvetica', '', '14');
                                    $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                    $pdf->writeHTML('<br>');

                                    // Set dateAmended to true.
                                    $dateAmended = true;
                                }
                            }
                        }

                        // If the person wasn't amended in Q4 or Close Out.
                        if(!$personAmended){

                            // Get whether the Person Responsible was Amended.
                            $Q2PersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q2UpdateResponseID, 'question_id' => 44593));

                            if(!empty($Q2PersonResponsibleAnswer)){

                                // If the Person Responsible was amended.
                                if($Q2PersonResponsibleAnswer->choice_id == 'y'){

                                    // Print the new Person Responsible on the CIAP.
                                    $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $Q2UpdateResponseID, 'question_id' => 44610));
                                    $pdf->SetFont('helvetica', 'B', '14');
                                    $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                    $pdf->writeHTML('<br>');

                                    // Set personAmended to true.
                                    $personAmended = true;
                                }
                            }
                        }

                    }

                    // If the Date wasn't amended in Close Out, Q4 or Q2
                    if(!$dateAmended){

                        // Print the original Due Date on the CIAP.
                        $question7 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Implemented by', 'deleted' => 'n'));
                        $answer7 = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => $question7->id));
                        $date = new DateTime($answer7->response);
                        $pdf->SetFont('helvetica', '', '14');
                        $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                        $pdf->writeHTML('<br>');
                    }

                    // If the Person wasn't amended in Close Out, Q4 or Q2
                    if(!$personAmended){

                        // Print the original Person Responsible on the CIAP.
                        $question8 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Responsible Person', 'deleted' => 'n'));
                        $answer8 = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $question8->id));
                        $pdf->SetFont('helvetica', 'B', '14');
                        $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $answer8->response . '</i></span>');
                        $pdf->writeHTML('<br>');
                    }
                }
                else{

                    // Question 7.
                    $question7 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Implemented by', 'deleted' => 'n'));
                    $answer7 = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => $question7->id));
                    $date = new DateTime($answer7->response);
                    $pdf->SetFont('helvetica', '', '14');
                    $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                    $pdf->writeHTML('<br>');

                    // Question 8.
                    $question8 = $DB->get_record('questionnaire_question', array('surveyid' => $initialActionsSurveyID, 'name' => 'Responsible Person', 'deleted' => 'n'));
                    $answer8 = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $question8->id));
                    $pdf->SetFont('helvetica', 'B', '14');
                    $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $answer8->response . '</i></span>');
                    $pdf->writeHTML('<br>');
                }
                // If the action has an update.
                if($CloseOutActionStatus != "Update not Provided"){

                    // New Line.
                    $pdf->writeHTML('<br>');

                    // Set the text color to blue.
                    $pdf->SetTextColor(21, 128, 188);

                    // Set the Font.
                    $pdf->SetFont('helvetica', 'B', '16');

                    // Print the Close Out Action Status.
                    $pdf->writeHTML('Close Out Status: <span style="font-weight: normal; color: #000;"><i>' . $CloseOutActionStatus . '</i></span>');

                    // Set the Color to black.
                    $pdf->SetTextColor(0, 0, 0);

                    // Set the Font.
                    $pdf->SetFont('helvetica', 'I', '14');

                    // If the action is complete.
                    if($CloseOutActionStatus == "Complete"){

                        // Set the text color to blue.
                        $pdf->SetTextColor(21, 128, 188);

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '25');
                        
                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print Congradulations.
                        $pdf->writeHTML('<p style="text-align: center;">Congratulations!!</p>');

                        // Set the text color to black.
                        $pdf->SetTextColor(0, 0, 0);

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'I', '14');

                        $pdf->writeHTML('<p style="text-align: center;">Don’t forget to celebrate this success with your team.</p>');
                    
                    }
                    // If the action is in progress.
                    else if($CloseOutActionStatus == "In Progress"){
                        
                        // Question 9.
                        $question9 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'OnTrack Progress', 'deleted' => 'n'));
                        $answer9 = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => $question9->id));
                        
                        // Whether the action is on track.
                        $ontrack;

                        // If the action is ontrack.
                        if($answer9->choice_id == 'y'){
                            $ontrack  = "Yes";
                        }
                        else{
                            $ontrack  = "No";
                        }

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '14');

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print the question.
                        $pdf->writeHTML('Are you on track to achieve this task by the commencement of the 2020 Going for Gold Survey? <span style="font-weight: normal;"><i>' . $ontrack . '</i></span>');
                    
                        // If the current action isn't on track.
                        if($ontrack == "No"){

                            // Question 10.
                            $question10 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'OnTrack Progress Why', 'deleted' => 'n'));
                            
                            // Find what action this update is for.
                            $sql = "SELECT qqc.*
                                    FROM {questionnaire_quest_choice} qqc
                                    JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                    AND qqc.id = qrs.choice_id
                                    AND qrs.response_id = $CloseOutResponseID
                                    WHERE qqc.question_id = :questionid";

                            // Get the answer to the first question.
                            $answer10 = $DB->get_record_sql($sql, array('questionid' => $question10->id));

                            // New Line.
                            $pdf->writeHTML('<br>');

                            // If ontrack why is other
                            if($answer10->content == "Other"){
                                
                                // Question 11.
                                $question11 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'OnTrack Progress Other', 'deleted' => 'n'));
                                $answer11 = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => $question11->id));

                                // Print the question.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $answer11->response . '</i></span>');
                            }
                            else{

                                // Print the question.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $answer10->content . '</i></span>');
                            }
                        }
                    }
                    // If the action is Not yet Started.
                    else if($CloseOutActionStatus == "Not yet Started"){
                        
                        // Question 12.
                        $question12 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'OnTrack NotStarted', 'deleted' => 'n'));
                        $answer12 = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => $question12->id));
                        
                        // Whether the action is on track.
                        $ontrack;

                        // If the action is ontrack.
                        if($answer12->choice_id == 'y'){
                            $ontrack  = "Yes";
                        }
                        else{
                            $ontrack  = "No";
                        }

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '14');

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print the question.
                        $pdf->writeHTML('Are you on track to achieve this task by the commencement of the 2020 Going for Gold Survey? <span style="font-weight: normal;"><i>' . $ontrack . '</i></span>');
                        
                        // If the current action isn't on track.
                        if($ontrack == "No"){
                            
                            // Find what action this update is for.
                            $sql = "SELECT qqc.*
                                    FROM {questionnaire_quest_choice} qqc
                                    JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                    AND qqc.id = qrs.choice_id
                                    AND qrs.response_id = $CloseOutResponseID
                                    WHERE qqc.question_id = :questionid";

                            // Get the answer to the first question.
                            $answer13 = $DB->get_record_sql($sql, array('questionid' => 45489));

                            // New Line.
                            $pdf->writeHTML('<br>');

                            // If ontrack why is other
                            if($answer13->content == "Other"){
                                
                                // Question 14.
                                $question14 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'OnTrack NotStarted Other', 'deleted' => 'n'));
                                $answer14 = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => $question14->id));

                                // Print the question.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $answer14->response . '</i></span>');
                            }
                            else{

                                // Print the question.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $answer13->content . '</i></span>');
                            }
                        }

                    }
                    // If the action is No Longer Required.
                    else if($CloseOutActionStatus == "No Longer Required"){

                        // Question 15.
                        $question15 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'Not Required', 'deleted' => 'n'));
                        $answer15 = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => $question15->id));
                    
                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '14');

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print the question.
                        $pdf->writeHTML('Why is the action no longer required? <span style="font-weight: normal;"><i>' . $answer15->response . '</i></span>');
                    }
                }

                // New Line.
                $pdf->writeHTML('<br>');

                // Set the text color to blue.
                $pdf->SetTextColor(21, 128, 188);

                // Set the Font.
                $pdf->SetFont('helvetica', 'B', '16');

                // Print the current Action Number.
                $pdf->writeHTML('Q4 Where were we at: <span style="font-weight: normal; color: #000;"><i>' . $Q4ActionStatus . '</i></span>');

                // New Line.
                $pdf->writeHTML('<br>');

                // Print the current Action Number.
                $pdf->writeHTML('Q2 Where were we at: <span style="font-weight: normal; color: #000;"><i>' . $Q2ActionStatus . '</i></span>');

                // Set the Color to black.
                $pdf->SetTextColor(0, 0, 0);

                // Set the Font.
                $pdf->SetFont('helvetica', 'I', '14');

                // Add 1 to the action number.
                $actionnum++;
            }
        }

        //If the CIAP has any Q2 Actions.
        if(!empty($Q2Actions)){

            // Do this for each action.
            foreach($Q2Actions as $action){

                // If we have already completed this action.
                if (in_array($action->id, $completedactions)) {

                    // Skip this action.
                    continue;
                }

                // Add a page to the certificate.
                $pdf->AddPage();

                // Add this action to the completed actions.
                $completedactions[] = $action->id;

                // The status of this action in Close Out.
                $CloseOutActionStatus = "Update not Provided";

                // The status of this action in Q4.
                $Q4ActionStatus = "Update not Provided";

                // The status of this action in Q2.
                $Q2ActionStatus = "New Action Added";

                // The action's Close Out response id.
                $CloseOutResponseID = NULL;

                // The action's Q4 response id.
                $Q4UpdateResponseID = NULL;

                // Set the text color to blue.
                $pdf->SetTextColor(21, 128, 188);

                // Print the Page heading.
                certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 25, '2018 Going for Gold Staff Survey');
                
                // Set the text color to black.
                $pdf->SetTextColor(0, 0, 0);

                // Print the Report Name.
                certificate_print_text($pdf, $x, $y + 12, 'C', 'Helvetica', 'B', 14, $reportName);

                // Print the service line.
                certificate_print_text($pdf, $x, $y + 20, 'C', 'Helvetica', 'B', 14, $serviceLine);

                //Print the division.
                certificate_print_text($pdf, $x, $y + 28, 'C', 'Helvetica', 'B', 14, $division);
                
                // Print the Culture Champion.
                certificate_print_text($pdf, $x, $y + 36, 'C', 'Helvetica', 'B', 14, '2018 GFG Survey Culture Champion: ' . $cultureChampion);

                // Set the text color back to blue.
                $pdf->SetTextColor(21, 128, 188);

                // Print the CIAP Heading.
                certificate_print_text($pdf, $x, $y + 43, 'C', 'Helvetica', 'B', 14, "Close out of your team's");
                certificate_print_text($pdf, $x, $y + 50, 'C', 'Helvetica', 'B', 18, 'Continuous Improvement Action Plan');
                certificate_print_text($pdf, $x, $y + 60, 'C', 'Helvetica', 'B', 14, '2018 / 2019');
            
                // Print some space.
                $pdf->writeHTML('<br>');
                $pdf->writeHTML('<br>');

                // Print the Image.
                $image = html_writer::img($CFG->wwwroot . '/mod/certificate/type/CIAP/BACS.jpg', 'Going for Gold', array('width' => 455, 'height' => 50));
                
                // Center the Image.
                $pdf->writeHTML(html_writer::tag('p', $image, array('style' => 'text-align: center;')));
                
                // Do this for each Close Out Update.
                foreach($CloseOutUpdates as $update){

                    // Get the action number question from the database.
                    $updatenumberquestion = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'Action Number', 'deleted' => 'n'));

                    // Find what action this update is for.
                    $sql = "SELECT qqc.*
                            FROM {questionnaire_quest_choice} qqc
                            JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                            AND qqc.id = qrs.choice_id
                            AND qrs.response_id = $update->id
                            WHERE qqc.question_id = :questionid";

                    // Get the answer to the first question.
                    $updatenumber = $DB->get_record_sql($sql, array('questionid' => $updatenumberquestion->id));
                    
                    // Setup the Action Number.
                    $actionnumber = "Action " . $actionnum;
                    
                    // If the update is for this action.
                    if($updatenumber->content == $actionnumber){

                        // Find what staus this update is.
                        $sql = "SELECT qqc.*
                                FROM {questionnaire_quest_choice} qqc
                                JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                AND qqc.id = qrs.choice_id
                                AND qrs.response_id = $update->id
                                WHERE qqc.question_id = 47900";

                        // Get the Action's Update Status.
                        $updatestatus = $DB->get_record_sql($sql);

                        // Set the actions status.
                        $CloseOutActionStatus = $updatestatus->content;

                        // Set the action's update id.
                        $CloseOutResponseID = $update->id;
                    }
                }

                // Do this for each Q4 update.
                foreach($Q4Updates as $update){

                    // Get the action number question from the database.
                    $updatenumberquestion = $DB->get_record('questionnaire_question', array('surveyid' => $Q4UpdateSurveyID, 'name' => 'Action Number', 'deleted' => 'n'));

                    // Find what action this update is for.
                    $sql = "SELECT qqc.*
                            FROM {questionnaire_quest_choice} qqc
                            JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                            AND qqc.id = qrs.choice_id
                            AND qrs.response_id = $update->id
                            WHERE qqc.question_id = :questionid";

                    // Get the answer to the first question.
                    $updatenumber = $DB->get_record_sql($sql, array('questionid' => $updatenumberquestion->id));
                    
                    // Setup the Action Number.
                    $actionnumber = "Action " . $actionnum;
                    
                    // If the update is for this action.
                    if($updatenumber->content == $actionnumber){

                        // Find what staus this update is.
                        $sql = "SELECT qqc.*
                                FROM {questionnaire_quest_choice} qqc
                                JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                AND qqc.id = qrs.choice_id
                                AND qrs.response_id = $update->id
                                WHERE qqc.question_id = 45478";

                        // Get the Action's Update Status.
                        $updatestatus = $DB->get_record_sql($sql);

                        // Set the actions status.
                        $Q4ActionStatus = $updatestatus->content;

                        // Set the action's update id.
                        $Q4UpdateResponseID = $update->id;
                    }
                }

                // Set the Font.
                $pdf->SetFont('helvetica', 'B', '16');

                // If an update wasn't provided for the action.
                if($CloseOutActionStatus == "Update not Provided"){

                    // Print the current Action Number and Q4 Action Status.
                    $pdf->writeHTML('Action ' . $actionnum . " <i>(Update not Provided)</i>");
                }
                else{

                    // Print the current Action Number.
                    $pdf->writeHTML('Action ' . $actionnum);
                }

                // Set the Color to black.
                $pdf->SetTextColor(0, 0, 0);

                // The SQL code.
                $sql = "SELECT qqc.content
                        FROM {questionnaire_quest_choice} qqc
                        JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                        AND qqc.id = qrs.choice_id
                        AND qrs.response_id = $action->id
                        WHERE qqc.question_id = 39300";

                // Get the (Start, Stop, Keep) Initiative for this action.
                $initiative = $DB->get_record_sql($sql);

                // Set the Font.
                $pdf->SetFont('helvetica', 'I', '14');

                // Print whether the action is Start, Stop or Keep.
                $pdf->writeHTML($initiative->content);
                $pdf->writeHTML('<br>');

                // The SQL code.
                $sql = "SELECT qqc.content
                        FROM {questionnaire_quest_choice} qqc
                        JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                        AND qqc.id = qrs.choice_id
                        AND qrs.response_id = $action->id
                        WHERE qqc.question_id = 39302";
                
                $pillar = $DB->get_record_sql($sql);

                // Set the font.
                $pdf->SetFont('helvetica', 'B', '14');

                // Print the question.
                $pdf->writeHTML('What research program from the Staff Survey does this action link to?');
                
                // Set the font.
                $pdf->SetFont('helvetica', 'I', '14');

                // Print the answer.
                $pdf->writeHTML($pillar->content);

                // Print a space.
                $pdf->writeHTML('<br>');

                // Set the font.
                $pdf->SetFont('helvetica', 'B', '14');

                // Print the question.
                $pdf->writeHTML('What is the action in response to?');

                // The SQL code.
                $sql = "SELECT qqc.content
                        FROM {questionnaire_quest_choice} qqc
                        JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                        AND qqc.id = qrs.choice_id
                        AND qrs.response_id = $action->id
                        WHERE qqc.question_id = 39303";
                
                // If the action is in respose to a survey question
                $responseToSurvey = $DB->get_record_sql($sql);

                if($responseToSurvey->content == 'Yes'){

                    // Get the Survey Question that this action is in response to.
                    $surveyQuestion = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => 39304));

                    if(!empty($surveyQuestion)){

                        // Print the Survey Question.
                        $pdf->SetFont('helvetica', '', '14');
                        $pdf->writeHTML('<i>Survey Question: ' . $surveyQuestion->response . '</i>');
                    }
                }
                else{

                    // The SQL query.
                    $sql = "SELECT qqc.content
                              FROM {questionnaire_quest_choice} qqc
                              JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                               AND qrs.response_id = $action->id
                               AND qqc.id = qrs.choice_id
                             WHERE qqc.question_id = 42180";
                    
                    // Get what the action is in response to.
                    $responseTo = $DB->get_record_sql($sql);

                    if(!empty($responseTo)){
                        $pdf->SetFont('helvetica', 'I', '14');
                        $pdf->writeHTML($responseTo->content);
                    }
                }

                // Have a Break, Have a Kit Kat :).
                $pdf->writeHTML('<br>');

                // Get the Action Description from the database.
                $actionDescription = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => 39312));
                $pdf->SetFont('helvetica', 'B', '14');
                $pdf->writeHTML('What is the action your team has agreed to?');
                $pdf->SetFont('helvetica', 'I', '14');
                $pdf->writeHTML(strip_tags($actionDescription->response, '<ul><li><ol>'));
                $pdf->writeHTML('<br>');

                // If the action is in progress.
                if($Q4ActionStatus == "In Progress" || $CloseOutActionStatus == "In Progress"){

                    // Whether the Implementation Date for this action has been amended.
                    $dateAmended = false;

                    // Whether the Person Responsible for this action has been amended.
                    $personAmended = false;

                    // If a Close Out Update was provided for this action.
                    if(!empty($CloseOutResponseID)){

                        // Get whether the Due Date was Amended.
                        $CloseOutDueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47923));

                        if(!empty($CloseOutDueDateAnswer)){

                            // If the Due Date was amended.
                            if($CloseOutDueDateAnswer->choice_id == 'y'){

                                // Print the new Due Date on the CIAP.
                                $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $CloseOutResponseID, 'question_id' => 47921));
                                $date = new DateTime($dueDate->response);
                                $pdf->SetFont('helvetica', '', '14');
                                $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                $pdf->writeHTML('<br>');

                                // Set dateAmended to true.
                                $dateAmended = true;
                            }
                        }

                        // Get whether the Person Responsible was Amended.
                        $CloseOutPersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47930));

                        if(!empty($CloseOutPersonResponsibleAnswer)){

                            // If the Person Responsible was amended.
                            if($CloseOutPersonResponsibleAnswer->choice_id == 'y'){

                                // Print the new Person Responsible on the CIAP.
                                $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => 47933));
                                $pdf->SetFont('helvetica', 'B', '14');
                                $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                $pdf->writeHTML('<br>');

                                // Set personAmended to true.
                                $personAmended = true;
                            }
                        }
                    }

                    // If a Q4 Update was provided for this action.
                    if(!empty($Q4UpdateResponseID)){

                        // If the date wasn't amended in Close Out.
                        if(!$dateAmended){

                            // Get whether the Due Date was Amended.
                            $Q4DueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45501));

                            if(!empty($Q4DueDateAnswer)){

                                // If the Due Date was amended.
                                if($Q4DueDateAnswer->choice_id == 'y'){

                                    // Print the new Due Date on the CIAP.
                                    $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45499));
                                    $date = new DateTime($dueDate->response);
                                    $pdf->SetFont('helvetica', '', '14');
                                    $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                    $pdf->writeHTML('<br>');

                                    // Set dateAmended to true.
                                    $dateAmended = true;
                                }
                            }
                        }

                        // If the Person wasn't amended in Close Out.
                        if(!$personAmended){

                            // Get whether the Person Responsible was Amended.
                            $Q4PersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45508));

                            if(!empty($Q4PersonResponsibleAnswer)){

                                // If the Person Responsible was amended.
                                if($Q4PersonResponsibleAnswer->choice_id == 'y'){

                                    // Print the new Person Responsible on the CIAP.
                                    $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45511));
                                    $pdf->SetFont('helvetica', 'B', '14');
                                    $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                    $pdf->writeHTML('<br>');

                                    // Set personAmended to true.
                                    $personAmended = true;
                                }
                            }
                        }
                    }

                    // If the Date wasn't amended in Q4 or Q2
                    if(!$dateAmended){

                        // Print the original Due Date on the CIAP.
                        $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => 39314));
                        $date = new DateTime($dueDate->response);
                        $pdf->SetFont('helvetica', '', '14');
                        $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                        $pdf->writeHTML('<br>');
                    }

                    // If the Person wasn't amended in Q4 or Q2
                    if(!$personAmended){

                        // Print the original Person Responsible on the CIAP.
                        $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => 39315));
                        $pdf->SetFont('helvetica', 'B', '14');
                        $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                        $pdf->writeHTML('<br>');
                    }
                }
                // If the action is in Not Yet Started.
                else if($Q4ActionStatus == "Not yet Started" || $CloseOutActionStatus == "Not yet Started"){

                    // Wheether the Implementation Date for this action has been amended.
                    $dateAmended = false;

                    // Whether the Person Responsible for this action has been amended.
                    $personAmended = false;

                    // If a Close Out Update was provided for this action.
                    if(!empty($CloseOutResponseID)){

                        // Get whether the Due Date was Amended.
                        $CloseOutDueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47925));

                        if(!empty($CloseOutDueDateAnswer)){

                            // If the Due Date was amended.
                            if($CloseOutDueDateAnswer->choice_id == 'y'){

                                // Print the new Due Date on the CIAP.
                                $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $CloseOutResponseID, 'question_id' => 47927));
                                $date = new DateTime($dueDate->response);
                                $pdf->SetFont('helvetica', '', '14');
                                $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                $pdf->writeHTML('<br>');

                                // Set dateAmended to true.
                                $dateAmended = true;
                            }
                        }

                        // Get whether the Person Responsible was Amended.
                        $CloseOutPersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47935));

                        if(!empty($CloseOutPersonResponsibleAnswer)){

                            // If the Person Responsible was amended.
                            if($CloseOutPersonResponsibleAnswer->choice_id == 'y'){

                                // Print the new Person Responsible on the CIAP.
                                $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => 47936));
                                $pdf->SetFont('helvetica', 'B', '14');
                                $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                $pdf->writeHTML('<br>');

                                // Set personAmended to true.
                                $personAmended = true;
                            }
                        }
                    }

                    // If a Q4 Update was provided for this action.
                    if(!empty($Q4UpdateResponseID)){

                        if(!$dateAmended){

                            // Get whether the Due Date was Amended.
                            $Q4DueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45503));

                            if(!empty($Q4DueDateAnswer)){

                                // If the Due Date was amended.
                                if($Q4DueDateAnswer->choice_id == 'y'){

                                    // Print the new Due Date on the CIAP.
                                    $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45505));
                                    $date = new DateTime($dueDate->response);
                                    $pdf->SetFont('helvetica', '', '14');
                                    $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                    $pdf->writeHTML('<br>');

                                    // Set dateAmended to true.
                                    $dateAmended = true;
                                }
                            }
                        }

                        if(!$personAmended){

                            // Get whether the Person Responsible was Amended.
                            $Q4PersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45513));

                            if(!empty($Q4PersonResponsibleAnswer)){

                                // If the Person Responsible was amended.
                                if($Q4PersonResponsibleAnswer->choice_id == 'y'){

                                    // Print the new Person Responsible on the CIAP.
                                    $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45514));
                                    $pdf->SetFont('helvetica', 'B', '14');
                                    $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                    $pdf->writeHTML('<br>');
                                    // Set personAmended to true.
                                    $personAmended = true;
                                }
                            }
                        }
                    }

                    // If the Date wasn't amended in Q4
                    if(!$dateAmended){

                        // Print the original Due Date on the CIAP.
                        $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => 39314));
                        $date = new DateTime($dueDate->response);
                        $pdf->SetFont('helvetica', '', '14');
                        $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                        $pdf->writeHTML('<br>');
                    }

                    // If the Person wasn't amended in Q4
                    if(!$personAmended){

                        // Print the original Person Responsible on the CIAP.
                        $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => 39315));
                        $pdf->SetFont('helvetica', 'B', '14');
                        $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                        $pdf->writeHTML('<br>');
                    }
                }
                // If there was no Q4 update.
                else{

                    // Get the Due Date for this the action.
                    $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => 39314));
                    $date = new DateTime($dueDate->response);
                    $pdf->SetFont('helvetica', '', '14');
                    $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                    $pdf->writeHTML('<br>');

                    // Get the Person Responsible for this action.
                    $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => 39315));
                    $pdf->SetFont('helvetica', 'B', '14');
                    $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                }
                // If the action has an update.
                if($CloseOutActionStatus != "Update not Provided"){

                    // New Line.
                    $pdf->writeHTML('<br>');

                    // Set the text color to blue.
                    $pdf->SetTextColor(21, 128, 188);

                    // Set the Font.
                    $pdf->SetFont('helvetica', 'B', '16');

                    // Print the Close Out Action Status.
                    $pdf->writeHTML('Close Out Status: <span style="font-weight: normal; color: #000;"><i>' . $CloseOutActionStatus . '</i></span>');

                    // Set the Color to black.
                    $pdf->SetTextColor(0, 0, 0);

                    // Set the Font.
                    $pdf->SetFont('helvetica', 'I', '14');

                    // If the action is complete.
                    if($CloseOutActionStatus == "Complete"){

                        // Set the text color to blue.
                        $pdf->SetTextColor(21, 128, 188);

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '25');
                        
                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print Congradulations.
                        $pdf->writeHTML('<p style="text-align: center;">Congratulations!!</p>');

                        // Set the text color to black.
                        $pdf->SetTextColor(0, 0, 0);

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'I', '14');

                        $pdf->writeHTML('<p style="text-align: center;">Don’t forget to celebrate this success with your team.</p>');
                    
                    }
                    // If the action is in progress.
                    else if($Q4ActionStatus == "In Progress"){
                        
                        // Question 9.
                        $question9 = $DB->get_record('questionnaire_question', array('surveyid' => $Q4UpdateSurveyID, 'name' => 'OnTrack Progress', 'deleted' => 'n'));
                        $answer9 = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => $question9->id));
                        
                        // Whether the action is on track.
                        $ontrack;

                        // If the action is ontrack.
                        if($answer9->choice_id == 'y'){
                            $ontrack  = "Yes";
                        }
                        else{
                            $ontrack  = "No";
                        }

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '14');

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print the question.
                        $pdf->writeHTML('Are you on track to achieve this task by the commencement of the 2020 Going for Gold Survey? <span style="font-weight: normal;"><i>' . $ontrack . '</i></span>');
                    
                        // If the current action isn't on track.
                        if($ontrack == "No"){

                            // Question 10.
                            $question10 = $DB->get_record('questionnaire_question', array('surveyid' => $Q4UpdateSurveyID, 'name' => 'OnTrack Progress Why', 'deleted' => 'n'));
                            
                            // Find what action this update is for.
                            $sql = "SELECT qqc.*
                                    FROM {questionnaire_quest_choice} qqc
                                    JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                    AND qqc.id = qrs.choice_id
                                    AND qrs.response_id = $Q4UpdateResponseID
                                    WHERE qqc.question_id = :questionid";

                            // Get the answer to the first question.
                            $answer10 = $DB->get_record_sql($sql, array('questionid' => $question10->id));

                            // New Line.
                            $pdf->writeHTML('<br>');

                            // If ontrack why is other
                            if($answer10->content == "Other"){
                                
                                // Question 11.
                                $question11 = $DB->get_record('questionnaire_question', array('surveyid' => $Q4UpdateSurveyID, 'name' => 'OnTrack Progress Other', 'deleted' => 'n'));
                                $answer11 = $DB->get_record('questionnaire_response_text', array('response_id' => $Q4UpdateResponseID, 'question_id' => $question11->id));

                                // Print the question.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $answer11->response . '</i></span>');
                            }
                            else{

                                // Print the question.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $answer10->content . '</i></span>');
                            }
                        }
                    }
                    // If the action is Not yet Started.
                    else if($Q4ActionStatus == "Not yet Started"){

                        // Get whwther the action is on track.
                        $ontrackResponse = $DB->get_record('questionnaire_response_bool', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45488));
                        
                        // Whether the action is on track.
                        $ontrack;

                        if(!empty($ontrackResponse)){

                            // If the action is ontrack.
                            if($ontrackResponse->choice_id == 'y'){
                                $ontrack  = "Yes";
                            }
                            else{
                                $ontrack  = "No";
                            }
                        }

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '14');

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print the question.
                        $pdf->writeHTML('Are you on track to achieve this task by the commencement of the 2020 Going for Gold Survey? <span style="font-weight: normal;"><i>' . $ontrack . '</i></span>');
                        
                        // If the current action isn't on track.
                        if($ontrack == "No"){
                            
                            // Find what action this update is for.
                            $sql = "SELECT qqc.*
                                    FROM {questionnaire_quest_choice} qqc
                                    JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                    AND qqc.id = qrs.choice_id
                                    AND qrs.response_id = $Q4UpdateResponseID
                                    WHERE qqc.question_id = 45489";

                            // Get the answer to the first question.
                            $onTrackWhy = $DB->get_record_sql($sql);

                            // New Line.
                            $pdf->writeHTML('<br>');

                            // If ontrack why is other
                            if($onTrackWhy->content == "Other"){
                                
                                // Get other response
                                $other = $DB->get_record('questionnaire_response_text', array('response_id' => $Q4UpdateResponseID, 'question_id' => 45497));

                                // Print why the action isn't on track.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $other->response . '</i></span>');
                            }
                            else{

                                // Print why the action isn't on track.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $onTrackWhy->content . '</i></span>');
                            }
                        }

                    }
                    // If the action is No Longer Required.
                    else if($Q4ActionStatus == "No Longer Required"){

                        // Question 15.
                        $question15 = $DB->get_record('questionnaire_question', array('surveyid' => $Q4UpdateSurveyID, 'name' => 'Not Required', 'deleted' => 'n'));
                        $answer15 = $DB->get_record('questionnaire_response_text', array('response_id' => $Q4UpdateResponseID, 'question_id' => $question15->id));
                    
                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '14');

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print the question.
                        $pdf->writeHTML('Why is the action no longer required? <span style="font-weight: normal;"><i>' . $answer15->response . '</i></span>');
                    }
                }

                // New Line.
                $pdf->writeHTML('<br>');

                // Set the text color to blue.
                $pdf->SetTextColor(21, 128, 188);

                // Set the Font.
                $pdf->SetFont('helvetica', 'B', '16');

                // Print the current Action Number.
                $pdf->writeHTML('Q4 Where were we at: <span style="font-weight: normal; color: #000;"><i>' . $Q4ActionStatus . '</i></span>');

                // New Line.
                $pdf->writeHTML('<br>');

                // Print the current Action Number.
                $pdf->writeHTML('Q2 Where were we at: <span style="font-weight: normal; color: #000;"><i>' . $Q2ActionStatus . '</i></span>');

                // Set the Color to black.
                $pdf->SetTextColor(0, 0, 0);

                // Set the Font.
                $pdf->SetFont('helvetica', 'I', '14');

                // Add 1 to the action number.
                $actionnum++;
            }
        }

        //If the CIAP has any Q4 Actions.
        if(!empty($Q4Actions)){

            // Do this for each new action
            foreach($Q4Actions as $action){

                // If we have already completed this action.
                if (in_array($action->id, $completedactions)) {

                    // Skip this action.
                    continue;
                }

                // Add a page to the certificate.
                $pdf->AddPage();

                // Add this action to the completed actions.
                $completedactions[] = $action->id;

                // The status of this action in Close Out.
                $CloseOutActionStatus = "Update not Provided";

                // Set the actions status.
                $Q4ActionStatus = "New Action Added";

                // The action's Close Out response id.
                $CloseOutResponseID;

                // Set the text color to blue.
                $pdf->SetTextColor(21, 128, 188);

                // Print the Page heading.
                certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 25, '2018 Going for Gold Staff Survey');

                // Set the text color to black.
                $pdf->SetTextColor(0, 0, 0);

                // Print the Report Name.
                certificate_print_text($pdf, $x, $y + 12, 'C', 'Helvetica', 'B', 14, $reportName);

                // Print the service line.
                certificate_print_text($pdf, $x, $y + 20, 'C', 'Helvetica', 'B', 14, $serviceLine);

                //Print the division.
                certificate_print_text($pdf, $x, $y + 28, 'C', 'Helvetica', 'B', 14, $division);

                // Print the Culture Champion.
                certificate_print_text($pdf, $x, $y + 36, 'C', 'Helvetica', 'B', 14, '2018 GFG Survey Culture Champion: ' . $cultureChampion);

                // Set the text color back to blue.
                $pdf->SetTextColor(21, 128, 188);

                // Print the CIAP Heading.
                certificate_print_text($pdf, $x, $y + 43, 'C', 'Helvetica', 'B', 14, "Close out of your team's");
                certificate_print_text($pdf, $x, $y + 50, 'C', 'Helvetica', 'B', 18, 'Continuous Improvement Action Plan');
                certificate_print_text($pdf, $x, $y + 60, 'C', 'Helvetica', 'B', 14, '2018 / 2019');

                // Print some space.
                $pdf->writeHTML('<br>');
                $pdf->writeHTML('<br>');

                // Print the Image.
                $image = html_writer::img($CFG->wwwroot . '/mod/certificate/type/CIAP/BACS.jpg', 'Going for Gold', array('width' => 455, 'height' => 50));

                // Center the Image.
                $pdf->writeHTML(html_writer::tag('p', $image, array('style' => 'text-align: center;')));

                // Do this for each Close Out Update.
                foreach($CloseOutUpdates as $update){

                    // Find what action this update is for.
                    $sql = "SELECT qqc.*
                            FROM {questionnaire_quest_choice} qqc
                            JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                            AND qqc.id = qrs.choice_id
                            AND qrs.response_id = $update->id
                            WHERE qqc.question_id = :questionid";

                    // Get the answer to the first question.
                    $updatenumber = $DB->get_record_sql($sql, array('questionid' => 47899));
                    
                    // Setup the Action Number.
                    $actionnumber = "Action " . $actionnum;
                    
                    // If the update is for this action.
                    if($updatenumber->content == $actionnumber){

                        // Find what staus this update is.
                        $sql = "SELECT qqc.*
                                FROM {questionnaire_quest_choice} qqc
                                JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                AND qqc.id = qrs.choice_id
                                AND qrs.response_id = $update->id
                                WHERE qqc.question_id = 47900";

                        // Get the Action's Update Status.
                        $updatestatus = $DB->get_record_sql($sql);

                        // Set the actions status.
                        $CloseOutActionStatus = $updatestatus->content;

                        // Set the action's update id.
                        $CloseOutResponseID = $update->id;
                    }
                }

                // Set the Font.
                $pdf->SetFont('helvetica', 'B', '16');

                // If an update wasn't provided for the action.
                if($CloseOutActionStatus == "Update not Provided"){

                    // Print the current Action Number and Q4 Action Status.
                    $pdf->writeHTML('Action ' . $actionnum . " <i>(Update not Provided)</i>");
                }
                else{

                    // Print the current Action Number.
                    $pdf->writeHTML('Action ' . $actionnum);
                }

                // Set the Color to black.
                $pdf->SetTextColor(0, 0, 0);

                 // The SQL code.
                 $sql = "SELECT qqc.content
                 FROM {questionnaire_quest_choice} qqc
                 JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                 AND qqc.id = qrs.choice_id
                 AND qrs.response_id = $action->id
                 WHERE qqc.question_id = 46130";

                // Get the (Start, Stop, Keep) Initiative for this action.
                $initiative = $DB->get_record_sql($sql);

                // Set the Font.
                $pdf->SetFont('helvetica', 'I', '14');

                // Print whether the action is Start, Stop or Keep.
                $pdf->writeHTML($initiative->content);
                $pdf->writeHTML('<br>');

                // The SQL code.
                $sql = "SELECT qqc.content
                        FROM {questionnaire_quest_choice} qqc
                        JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                        AND qqc.id = qrs.choice_id
                        AND qrs.response_id = $action->id
                        WHERE qqc.question_id = 46131";
                
                $pillar = $DB->get_record_sql($sql);

                // Set the font.
                $pdf->SetFont('helvetica', 'B', '14');

                // Print the question.
                $pdf->writeHTML('What research program from the Staff Survey does this action link to?');
                
                // Set the font.
                $pdf->SetFont('helvetica', 'I', '14');

                // Print the answer.
                $pdf->writeHTML($pillar->content);

                // Print a space.
                $pdf->writeHTML('<br>');

                // Set the font.
                $pdf->SetFont('helvetica', 'B', '14');

                // Print the question.
                $pdf->writeHTML('What is the action in response to?');

                // The SQL code.
                $sql = "SELECT qqc.content
                        FROM {questionnaire_quest_choice} qqc
                        JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                        AND qqc.id = qrs.choice_id
                        AND qrs.response_id = $action->id
                        WHERE qqc.question_id = 46132";
                
                // If the action is in respose to a survey question
                $responseToSurvey = $DB->get_record_sql($sql);

                if($responseToSurvey->content == 'Yes'){

                    // Get the Survey Question that this action is in response to.
                    $surveyQuestion = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => 46133));

                    if(!empty($surveyQuestion)){

                        // Print the Survey Question.
                        $pdf->SetFont('helvetica', '', '14');
                        $pdf->writeHTML('<i>Survey Question: ' . $surveyQuestion->response . '</i>');
                    }
                }
                else{

                    // The SQL query.
                    $sql = "SELECT qqc.content
                              FROM {questionnaire_quest_choice} qqc
                              JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                               AND qrs.response_id = $action->id
                               AND qqc.id = qrs.choice_id
                             WHERE qqc.question_id = 46146";
                    
                    // Get what the action is in response to.
                    $responseTo = $DB->get_record_sql($sql);

                    if(!empty($responseTo)){
                        $pdf->SetFont('helvetica', 'I', '14');
                        $pdf->writeHTML($responseTo->content);
                    }
                }

                // Have a Break, Have a Kit Kat :).
                $pdf->writeHTML('<br>');

                // Get the Action Description from the database.
                $actionDescription = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => 46140));
                $pdf->SetFont('helvetica', 'B', '14');
                $pdf->writeHTML('What is the action your team has agreed to?');
                $pdf->SetFont('helvetica', 'I', '14');
                $pdf->writeHTML(strip_tags($actionDescription->response, '<ul><li><ol>'));
                $pdf->writeHTML('<br>');

                // If the action is in progress.
                if($CloseOutActionStatus == "In Progress"){

                    // Whether the Implementation Date for this action has been amended.
                    $dateAmended = false;

                    // Whether the Person Responsible for this action has been amended.
                    $personAmended = false;

                    // If a Close Out Update was provided for this action.
                    if(!empty($CloseOutResponseID)){

                        // Get whether the Due Date was Amended.
                        $CloseOutDueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47923));

                        if(!empty($CloseOutDueDateAnswer)){

                            // If the Due Date was amended.
                            if($CloseOutDueDateAnswer->choice_id == 'y'){

                                // Print the new Due Date on the CIAP.
                                $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $CloseOutResponseID, 'question_id' => 47921));
                                $date = new DateTime($dueDate->response);
                                $pdf->SetFont('helvetica', '', '14');
                                $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                $pdf->writeHTML('<br>');

                                // Set dateAmended to true.
                                $dateAmended = true;
                            }
                        }

                        // Get whether the Person Responsible was Amended.
                        $CloseOutPersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47930));

                        if(!empty($CloseOutPersonResponsibleAnswer)){

                            // If the Person Responsible was amended.
                            if($CloseOutPersonResponsibleAnswer->choice_id == 'y'){

                                // Print the new Person Responsible on the CIAP.
                                $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => 47933));
                                $pdf->SetFont('helvetica', 'B', '14');
                                $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                $pdf->writeHTML('<br>');

                                // Set personAmended to true.
                                $personAmended = true;
                            }
                        }
                    }
                }
                // If the action is in Not Yet Started.
                else if($CloseOutActionStatus == "Not yet Started"){

                    // Wheether the Implementation Date for this action has been amended.
                    $dateAmended = false;

                    // Whether the Person Responsible for this action has been amended.
                    $personAmended = false;

                    // If a Close Out Update was provided for this action.
                    if(!empty($CloseOutResponseID)){

                        // Get whether the Due Date was Amended.
                        $CloseOutDueDateAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47925));

                        if(!empty($CloseOutDueDateAnswer)){

                            // If the Due Date was amended.
                            if($CloseOutDueDateAnswer->choice_id == 'y'){

                                // Print the new Due Date on the CIAP.
                                $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $CloseOutResponseID, 'question_id' => 47927));
                                $date = new DateTime($dueDate->response);
                                $pdf->SetFont('helvetica', '', '14');
                                $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                                $pdf->writeHTML('<br>');

                                // Set dateAmended to true.
                                $dateAmended = true;
                            }
                        }

                        // Get whether the Person Responsible was Amended.
                        $CloseOutPersonResponsibleAnswer = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 47935));

                        if(!empty($CloseOutPersonResponsibleAnswer)){

                            // If the Person Responsible was amended.
                            if($CloseOutPersonResponsibleAnswer->choice_id == 'y'){

                                // Print the new Person Responsible on the CIAP.
                                $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => 47936));
                                $pdf->SetFont('helvetica', 'B', '14');
                                $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                                $pdf->writeHTML('<br>');

                                // Set personAmended to true.
                                $personAmended = true;
                            }
                        }
                    }

                    // If the Date wasn't amended in the Close Out Update
                    if(!$dateAmended){

                        // Print the original Due Date on the CIAP.
                        $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => 46142));
                        $date = new DateTime($dueDate->response);
                        $pdf->SetFont('helvetica', '', '14');
                        $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                        $pdf->writeHTML('<br>');
                    }

                    // If the Person wasn't amended in the Close Out Update
                    if(!$personAmended){

                        // Print the original Person Responsible on the CIAP.
                        $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => 46143));
                        $pdf->SetFont('helvetica', 'B', '14');
                        $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                        $pdf->writeHTML('<br>');
                    }
                }
                // If there was no Close Out Update.
                else {
              
                    // Print the original Due Date on the CIAP.
                    $dueDate = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => 46142));
                    $date = new DateTime($dueDate->response);
                    $pdf->SetFont('helvetica', '', '14');
                    $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
                    $pdf->writeHTML('<br>');

                    // Print the original Person Responsible on the CIAP.
                    $personResponsible = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => 46143));
                    $pdf->SetFont('helvetica', 'B', '14');
                    $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $personResponsible->response . '</i></span>');
                    $pdf->writeHTML('<br>');
                }

                // If the action has an update.
                if($CloseOutActionStatus != "Update not Provided"){
                
                    // New Line.
                    $pdf->writeHTML('<br>');

                    // Set the text color to blue.
                    $pdf->SetTextColor(21, 128, 188);

                    // Set the Font.
                    $pdf->SetFont('helvetica', 'B', '16');

                    // Print the close Out Action Status.
                    $pdf->writeHTML('Close Out Status: <span style="font-weight: normal; color: #000;"><i>' . $CloseOutActionStatus . '</i></span>');

                    // Set the Color to black.
                    $pdf->SetTextColor(0, 0, 0);

                    // Set the Font.
                    $pdf->SetFont('helvetica', 'I', '14');

                    // If the action is complete.
                    if($CloseOutActionStatus == "Complete"){

                        // Set the text color to blue.
                        $pdf->SetTextColor(21, 128, 188);

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '25');
                        
                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print Congradulations.
                        $pdf->writeHTML('<p style="text-align: center;">Congratulations!!</p>');

                        // Set the text color to black.
                        $pdf->SetTextColor(0, 0, 0);

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'I', '14');

                        $pdf->writeHTML('<p style="text-align: center;">Don’t forget to celebrate this success with your team.</p>');
                    
                    }
                    // If the action is in progress.
                    else if($CloseOutActionStatus == "In Progress"){
                        
                        // Question 9.
                        $question9 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'OnTrack Progress', 'deleted' => 'n'));
                        $answer9 = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => $question9->id));
                        
                        // Whether the action is on track.
                        $ontrack;

                        // If the action is ontrack.
                        if($answer9->choice_id == 'y'){
                            $ontrack  = "Yes";
                        }
                        else{
                            $ontrack  = "No";
                        }

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '14');

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print the question.
                        $pdf->writeHTML('Are you on track to achieve this task by the commencement of the 2020 Going for Gold Survey? <span style="font-weight: normal;"><i>' . $ontrack . '</i></span>');
                    
                        // If the current action isn't on track.
                        if($ontrack == "No"){

                            // Question 10.
                            $question10 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'OnTrack Progress Why', 'deleted' => 'n'));
                            
                            // Find what action this update is for.
                            $sql = "SELECT qqc.*
                                    FROM {questionnaire_quest_choice} qqc
                                    JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                    AND qqc.id = qrs.choice_id
                                    AND qrs.response_id = $CloseOutResponseID
                                    WHERE qqc.question_id = :questionid";

                            // Get the answer to the first question.
                            $answer10 = $DB->get_record_sql($sql, array('questionid' => $question10->id));

                            // New Line.
                            $pdf->writeHTML('<br>');

                            // If ontrack why is other
                            if($answer10->content == "Other"){
                                
                                // Question 11.
                                $question11 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'OnTrack Progress Other', 'deleted' => 'n'));
                                $answer11 = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => $question11->id));

                                // Print the question.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $answer11->response . '</i></span>');
                            }
                            else{

                                // Print the question.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $answer10->content . '</i></span>');
                            }
                        }
                    }
                    // If the action is Not yet Started.
                    else if($CloseOutActionStatus == "Not yet Started"){

                        // Get whwther the action is on track.
                        $ontrackResponse = $DB->get_record('questionnaire_response_bool', array('response_id' => $CloseOutResponseID, 'question_id' => 45488));
                        
                        // Whether the action is on track.
                        $ontrack;

                        if(!empty($ontrackResponse)){

                            // If the action is ontrack.
                            if($ontrackResponse->choice_id == 'y'){
                                $ontrack  = "Yes";
                            }
                            else{
                                $ontrack  = "No";
                            }
                        }

                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '14');

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print the question.
                        $pdf->writeHTML('Are you on track to achieve this task by the commencement of the 2020 Going for Gold Survey? <span style="font-weight: normal;"><i>' . $ontrack . '</i></span>');
                        
                        // If the current action isn't on track.
                        if($ontrack == "No"){
                            
                            // Find what action this update is for.
                            $sql = "SELECT qqc.*
                                    FROM {questionnaire_quest_choice} qqc
                                    JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                    AND qqc.id = qrs.choice_id
                                    AND qrs.response_id = $CloseOutResponseID
                                    WHERE qqc.question_id = 45489";

                            // Get the answer to the first question.
                            $onTrackWhy = $DB->get_record_sql($sql);

                            // New Line.
                            $pdf->writeHTML('<br>');

                            // If ontrack why is other
                            if($onTrackWhy->content == "Other"){
                                
                                // Get other response
                                $other = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => 45497));

                                // Print why the action isn't on track.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $other->response . '</i></span>');
                            }
                            else{

                                // Print why the action isn't on track.
                                $pdf->writeHTML('Why arent you on track? <span style="font-weight: normal;"><i>' . $onTrackWhy->content . '</i></span>');
                            }
                        }

                    }
                    // If the action is No Longer Required.
                    else if($CloseOutActionStatus == "No Longer Required"){

                        // Question 15.
                        $question15 = $DB->get_record('questionnaire_question', array('surveyid' => $CloseOutSurveyID, 'name' => 'Not Required', 'deleted' => 'n'));
                        $answer15 = $DB->get_record('questionnaire_response_text', array('response_id' => $CloseOutResponseID, 'question_id' => $question15->id));
                    
                        // Set the Font.
                        $pdf->SetFont('helvetica', 'B', '14');

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // Print the question.
                        $pdf->writeHTML('Why is the action no longer required? <span style="font-weight: normal;"><i>' . $answer15->response . '</i></span>');
                    }
                }

                // Add 1 to the action number.
                $actionnum++;
            }
        }
    }
}
else{

    // Add images and lines
    certificate_draw_frame($pdf, $certificate);

    // Set alpha to semi-transparency
    $pdf->SetAlpha(0.2);
    certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
    $pdf->SetAlpha(1);
    certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
    certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

    // Add a page to the certificate.
    $pdf->AddPage();

    // Set the text color to blue.
    $pdf->SetTextColor(21, 128, 188);

    // Print the Page heading.
    certificate_print_text($pdf, $x, $y + 100, 'C', 'Helvetica', 'B', 25, "You dont have access to any CIAP/s");
}