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

// Get the current users Division.
$userDivision = mod_certificate_get_division($USER->id);

// Get the report names for the division.
$reports = mod_certificate_get_reports($userDivision);

//echo html_writer::tag('pre', print_r($reports, true));die;

//If the position ID has atleast 1 report.
if(!empty($reports)){

    //Do this for each report.
    foreach($reports as $report){

        // Get the Report Name.
        $reportName = mod_certificate_get_report_name($report);

        // Get the Report Owner.
        $reportOwner = mod_certificate_get_report_owner($report);

        // Get the Service Line.
        $serviceLine = mod_certificate_get_service_line($reportOwner);

        // Get the Division.
        $division = mod_certificate_get_division($reportOwner);
        
        // Get the Culture Champion.
        $cultureChampion = mod_certificate_get_culture_champion($reportName);

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

        // Get the Initial CIAP Step 2: Add Actions responses with the report name.
        $sql = "SELECT qr.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND (qq.surveyid = 1316 OR qq.surveyid = 1336)
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content = :reportname";

        // Get all actions related to the report.
        $actions = $DB->get_records_sql($sql, ['reportname' => $reportName]);

        // Get the CIAP Quarterly Update 2 - Step 1: Updated Actions responses with the report name.
        $sql = "SELECT *
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND (qq.surveyid = 1475)
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content = :reportname";

        // Get the update related to the action.
        $updates = $DB->get_records_sql($sql, ['reportname' => $reportName]);

        // Do this for each action.
        foreach($actions as $action){

            // If we have already completed this action.
            if (in_array($action->id, $completedactions)) {

                // Skip this action.
                continue;
            }

            // Add a page to the certificate.
            $pdf->AddPage();

            // Add this action to the completed actions.
            $completedactions[] = $action->id;

            // The status of this action.
            $actionstatus = "Update not Provided";

            // The action's update id.
            $updateid;

            // The action's update's questionaire id.
            $updatequestionnaireid;

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
            certificate_print_text($pdf, $x, $y + 50, 'C', 'Helvetica', 'B', 18, 'Continuous Improvement Action Plan');
            certificate_print_text($pdf, $x, $y + 60, 'C', 'Helvetica', 'B', 14, 'Quarterly Update 2 (1 October - 31 December)');
        
            // Print some space.
            $pdf->writeHTML('<br>');
            $pdf->writeHTML('<br>');

            // Print the Image.
            $image = html_writer::img($CFG->wwwroot . '/mod/certificate/type/CIAP/BACS.jpg', 'Going for Gold', array('width' => 455, 'height' => 50));
            
            // Center the Image.
            $pdf->writeHTML(html_writer::tag('p', $image, array('style' => 'text-align: center;')));
            
            // Do this for each update.
            foreach($updates as $update){

                // Get the action number question from the database.
                $updatenumberquestion = $DB->get_record('questionnaire_question', array('surveyid' => $update->questionnaireid, 'name' => 'Action Number', 'deleted' => 'n'));

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
                    $updatestatusquestion = $DB->get_record('questionnaire_question', array('surveyid' => $update->questionnaireid, 'name' => 'Action Status', 'deleted' => 'n'));
                
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
                    $actionstatus = $updatestatus->content;

                    // Set the action's update id.
                    $updateid = $update->id;

                    // Set the action's update questionaire id.
                    $updatequestionnaireid = $update->questionnaireid;
                }
            }

            // Set the Font.
            $pdf->SetFont('helvetica', 'B', '16');

            // If an update wasn't provided for the action.
            if($actionstatus == "Update not Provided"){

                // Print the current Action Number and Action Status.
                $pdf->writeHTML('Action ' . $actionnum . " (<i>" . $actionstatus . "</i>)");
            }
            else{

                // Print the current Action Number.
                $pdf->writeHTML('Action ' . $actionnum);
            }

            // Set the Color to black.
            $pdf->SetTextColor(0, 0, 0);

            // Get the first question from the database.
            $question1 = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Start, Stop or Keep', 'deleted' => 'n'));
            
            // The SQL code.
            $sql = "SELECT qqc.*
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
            $question2 = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Related to Pillar', 'deleted' => 'n'));
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
            $question3 = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'What is it in response to', 'deleted' => 'n'));
            
            // The SQL query.
            $sql = "SELECT qqc.*
                      FROM {questionnaire_quest_choice} qqc
                      JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                       AND qrs.response_id = $action->id
                       AND qqc.id = qrs.choice_id
                     WHERE qqc.question_id = $question3->id";

            // Question 4
            $question4 = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Survey Q it relates to', 'deleted' => 'n'));
            
            if ($answer4 = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $question4->id))) {
                $pdf->SetFont('helvetica', '', '14');
                $pdf->writeHTML('<i>Survey Question: ' . $answer4->response . '</i>');
            } else if ($answer5 = $DB->get_record_sql($sql)) {
                $pdf->SetFont('helvetica', 'I', '14');
                $pdf->writeHTML($answer5->content);
            }
            $pdf->writeHTML('<br>');

            // Question 6.
            $question6 = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Describe Action', 'deleted' => 'n'));
            $answer6 = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $question6->id));
            $pdf->SetFont('helvetica', 'B', '14');
            $pdf->writeHTML('What is the action your team has agreed to?');
            $pdf->SetFont('helvetica', 'I', '14');
            $pdf->writeHTML(strip_tags($answer6->response, '<ul><li><ol>'));
            $pdf->writeHTML('<br>');

            // Question 7.
            $question7 = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Implemented by', 'deleted' => 'n'));
            $answer7 = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => $question7->id));
            $date = new DateTime($answer7->response);
            $pdf->SetFont('helvetica', '', '14');
            $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
            $pdf->writeHTML('<br>');

            // Question 8.
            $question8 = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Responsible Person', 'deleted' => 'n'));
            $answer8 = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $question8->id));
            $pdf->SetFont('helvetica', 'B', '14');
            $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $answer8->response . '</i></span>');
            
            // If the action has an update.
            if($actionstatus != "Update not Provided"){

                // New Line.
                $pdf->writeHTML('<br>');

                // Set the text color to blue.
                $pdf->SetTextColor(21, 128, 188);

                // Set the Font.
                $pdf->SetFont('helvetica', 'B', '16');

                // Print the current Action Number.
                $pdf->writeHTML('Where are we at? <span style="font-weight: normal; color: #000;"><i>' . $actionstatus . '</i></span>');

                // Set the Color to black.
                $pdf->SetTextColor(0, 0, 0);

                // Set the Font.
                $pdf->SetFont('helvetica', 'I', '14');

                // If the action is complete.
                if($actionstatus == "Complete"){

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

                    // Print Message.
                    $pdf->writeHTML('<p style="text-align: center;">Don’t forget to celebrate this success with your team.</p>');
                }
                // If the action is in progress.
                else if($actionstatus == "In Progress"){
                    
                    // Question 9.
                    $question9 = $DB->get_record('questionnaire_question', array('surveyid' => $updatequestionnaireid, 'name' => 'OnTrack Progress', 'deleted' => 'n'));
                    $answer9 = $DB->get_record('questionnaire_response_bool', array('response_id' => $updateid, 'question_id' => $question9->id));
                    
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
                    $pdf->writeHTML('Are you on track to achieve this task by the due date? <span style="font-weight: normal;"><i>' . $ontrack . '</i></span>');
                
                    // If the current action isn't on track.
                    if($ontrack == "No"){

                        // Question 10.
                        $question10 = $DB->get_record('questionnaire_question', array('surveyid' => $updatequestionnaireid, 'name' => 'OnTrack Progress Why', 'deleted' => 'n'));
                        
                        // Find what action this update is for.
                        $sql = "SELECT qqc.*
                                  FROM {questionnaire_quest_choice} qqc
                                  JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                   AND qqc.id = qrs.choice_id
                                   AND qrs.response_id = $updateid
                                 WHERE qqc.question_id = :questionid";

                        // Get the answer to the first question.
                        $answer10 = $DB->get_record_sql($sql, array('questionid' => $question10->id));

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // If ontrack why is other
                        if($answer10->content == "Other"){
                            
                            // Question 11.
                            $question11 = $DB->get_record('questionnaire_question', array('surveyid' => $updatequestionnaireid, 'name' => 'OnTrack Progress Other', 'deleted' => 'n'));
                            $answer11 = $DB->get_record('questionnaire_response_text', array('response_id' => $updateid, 'question_id' => $question11->id));

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
                else if($actionstatus == "Not yet Started"){
                    
                    // Question 12.
                    $question12 = $DB->get_record('questionnaire_question', array('surveyid' => $updatequestionnaireid, 'name' => 'OnTrack NotStarted', 'deleted' => 'n'));
                    $answer12 = $DB->get_record('questionnaire_response_bool', array('response_id' => $updateid, 'question_id' => $question12->id));
                    
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
                    $pdf->writeHTML('Are you on track to achieve this task by the due date? <span style="font-weight: normal;"><i>' . $ontrack . '</i></span>');
                    
                    // If the current action isn't on track.
                    if($ontrack == "No"){

                        // Question 13.
                        $question13 = $DB->get_record('questionnaire_question', array('surveyid' => $updatequestionnaireid, 'name' => 'OnTrack NotStarted Why', 'deleted' => 'n'));
                        
                        // Find what action this update is for.
                        $sql = "SELECT qqc.*
                                  FROM {questionnaire_quest_choice} qqc
                                  JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                                   AND qqc.id = qrs.choice_id
                                   AND qrs.response_id = $updateid
                                 WHERE qqc.question_id = :questionid";

                        // Get the answer to the first question.
                        $answer13 = $DB->get_record_sql($sql, array('questionid' => $question13->id));

                        // New Line.
                        $pdf->writeHTML('<br>');

                        // If ontrack why is other
                        if($answer13->content == "Other"){
                            
                            // Question 14.
                            $question14 = $DB->get_record('questionnaire_question', array('surveyid' => $updatequestionnaireid, 'name' => 'OnTrack NotStarted Other', 'deleted' => 'n'));
                            $answer14 = $DB->get_record('questionnaire_response_text', array('response_id' => $updateid, 'question_id' => $question14->id));

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
                else if($actionstatus == "No Longer Required"){

                    // Question 15.
                    $question15 = $DB->get_record('questionnaire_question', array('surveyid' => $updatequestionnaireid, 'name' => 'Not Required', 'deleted' => 'n'));
                    $answer15 = $DB->get_record('questionnaire_response_text', array('response_id' => $updateid, 'question_id' => $question15->id));
                
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

        // Get the New Action responses with the report name.
        $sql = "SELECT qr.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND qq.surveyid = 1484
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content = :reportname";

        // Get all actions related to the report.
        $newactions = $DB->get_records_sql($sql, ['reportname' => $report]);

        // Do this for each new action
        foreach($newactions as $newaction){

            // If we have already completed this action.
            if (in_array($newaction->id, $completedactions)) {

                // Skip this action.
                continue;
            }

            // Add a page to the certificate.
            $pdf->AddPage();

            // Add this action to the completed actions.
            $completedactions[] = $newaction->id;

            // Set the actions status.
            $actionstatus = "New Action Added";

            // Set the text color to blue.
            $pdf->SetTextColor(21, 128, 188);

            // Print the Page heading.
            certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 25, '2018 Going for Gold Staff Survey');

            // Set the text color to black.
            $pdf->SetTextColor(0, 0, 0);

            // Print the Report Name.
            certificate_print_text($pdf, $x, $y + 12, 'C', 'Helvetica', 'B', 14, $report);

            // Print the service line.
            certificate_print_text($pdf, $x, $y + 20, 'C', 'Helvetica', 'B', 14, $serviceLine);

            //Print the division.
            certificate_print_text($pdf, $x, $y + 28, 'C', 'Helvetica', 'B', 14, $division);

            // Print the Culture Champion.
            certificate_print_text($pdf, $x, $y + 36, 'C', 'Helvetica', 'B', 14, '2018 GFG Survey Culture Champion: '); //. $champname->response);

            // Set the text color back to blue.
            $pdf->SetTextColor(21, 128, 188);

            // Print the CIAP Heading.
            certificate_print_text($pdf, $x, $y + 50, 'C', 'Helvetica', 'B', 18, 'Continuous Improvement Action Plan');
            certificate_print_text($pdf, $x, $y + 60, 'C', 'Helvetica', 'B', 14, 'Quarterly Update 2 (1 October - 31 December)');

            // Print some space.
            $pdf->writeHTML('<br>');
            $pdf->writeHTML('<br>');

            // Print the Image.
            $image = html_writer::img($CFG->wwwroot . '/mod/certificate/type/CIAP/BACS.jpg', 'Going for Gold', array('width' => 455, 'height' => 50));

            // Center the Image.
            $pdf->writeHTML(html_writer::tag('p', $image, array('style' => 'text-align: center;')));

            // Set the Font.
            $pdf->SetFont('helvetica', 'B', '16');

            // Print the current Action Number.
            $pdf->writeHTML('Action ' . $actionnum . " (<i>" . $actionstatus . "</i>)");

            // Set the Color to black.
            $pdf->SetTextColor(0, 0, 0);

            // Get the first question from the database.
            $question1 = $DB->get_record('questionnaire_question', array('surveyid' => $newaction->questionnaireid, 'name' => 'Initiative', 'deleted' => 'n'));

            // The SQL code.
            $sql = "SELECT qqc.*
                      FROM {questionnaire_quest_choice} qqc
                      JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                       AND qqc.id = qrs.choice_id
                       AND qrs.response_id = $newaction->id
                     WHERE qqc.question_id = :questionid";

            // Get the answer to the first question.
            $answer1 = $DB->get_record_sql($sql, array('questionid' => $question1->id));

            // Set the Font.
            $pdf->SetFont('helvetica', 'I', '14');

            // Print the 
            $pdf->writeHTML($answer1->content);
            $pdf->writeHTML('<br>');

            $question2 = $DB->get_record('questionnaire_question', array('surveyid' => $newaction->questionnaireid, 'name' => 'Pillar', 'deleted' => 'n'));
            $answer2 = $DB->get_record_sql($sql, array('questionid' => $question2->id));

            // Question 2.
            $pdf->SetFont('helvetica', 'B', '14');
            $pdf->writeHTML('What research program from the Staff Survey does this action link to?');
            $pdf->SetFont('helvetica', 'I', '14');
            $pdf->writeHTML($answer2->content);
            $pdf->writeHTML('<br>');

            // Set the Font.
            $pdf->SetFont('helvetica', 'B', '14');

            // Print Question.
            $pdf->writeHTML('What is the action in response to?');

            // Question 3.
            $question3 = $DB->get_record('questionnaire_question', array('surveyid' => $newaction->questionnaireid, 'name' => 'Response to survey', 'deleted' => 'n'));
            
            $sql = "SELECT qqc.*
                      FROM {questionnaire_quest_choice} qqc
                      JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                       AND qrs.response_id = $newaction->id
                       AND qqc.id = qrs.choice_id
                     WHERE qqc.question_id = :questionid";

            // Whether the action is in response to a survey question.
            $answer3 = $DB->get_record_sql($sql, array('questionid' => $question3->id));

            // If the action is in response to a survey question.
            if($answer3->content == 'Yes'){
        
                // Get the Related survey question.
                $question4 = $DB->get_record('questionnaire_question', array('surveyid' => $newaction->questionnaireid, 'name' => 'Related Survey Question', 'deleted' => 'n'));
                $answer4 = $DB->get_record('questionnaire_response_text', array('response_id' => $newaction->id, 'question_id' => $question4->id));
                $pdf->SetFont('helvetica', 'I', '14');
                $pdf->writeHTML('Survey Question: ' . $answer4->response);
            }
            else{
                // Get the action response question.
                $question5 = $DB->get_record('questionnaire_question', array('surveyid' => $newaction->questionnaireid, 'name' => 'Response', 'deleted' => 'n'));
                
                // The SQL code.
                $sql = "SELECT qqc.*
                      FROM {questionnaire_quest_choice} qqc
                      JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                       AND qrs.response_id = $newaction->id
                       AND qqc.id = qrs.choice_id
                     WHERE qqc.question_id = :questionid";

                // Get what the action is in response too.
                $answer5 = $DB->get_record_sql($sql, array('questionid' => $question5->id));
                
                // Set the font.
                $pdf->SetFont('helvetica', 'I', '14');

                // Print the answer.
                $pdf->writeHTML($answer5->content);
            }

            $pdf->writeHTML('<br>');

            // Question 6.
            $question6 = $DB->get_record('questionnaire_question', array('surveyid' => $newaction->questionnaireid, 'name' => 'Action Description', 'deleted' => 'n'));
            $answer6 = $DB->get_record('questionnaire_response_text', array('response_id' => $newaction->id, 'question_id' => $question6->id));
            $pdf->SetFont('helvetica', 'B', '14');
            $pdf->writeHTML('What is the action your team has agreed to?');
            $pdf->SetFont('helvetica', 'I', '14');
            $pdf->writeHTML(strip_tags($answer6->response, '<ul><li><ol>'));
            $pdf->writeHTML('<br>');

            // Question 7.
            $question7 = $DB->get_record('questionnaire_question', array('surveyid' => $newaction->questionnaireid, 'name' => 'Implemented by', 'deleted' => 'n'));
            $answer7 = $DB->get_record('questionnaire_response_date', array('response_id' => $newaction->id, 'question_id' => $question7->id));
            $date = new DateTime($answer7->response);
            $pdf->SetFont('helvetica', '', '14');
            $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
            $pdf->writeHTML('<br>');

            // Question 8.
            $question8 = $DB->get_record('questionnaire_question', array('surveyid' => $newaction->questionnaireid, 'name' => 'Person Responsible', 'deleted' => 'n'));
            $answer8 = $DB->get_record('questionnaire_response_text', array('response_id' => $newaction->id, 'question_id' => $question8->id));
            $pdf->SetFont('helvetica', 'B', '14');
            $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $answer8->response . '</i></span>');

            // Add 1 to the action number.
            $actionnum++;
        }
    }
}
else{
    echo "<pre>You dont have access to any CIAPS.<pre>";
}