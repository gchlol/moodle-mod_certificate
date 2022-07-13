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
require_once(__DIR__ . '/lib.php');

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

$completedactions = [];

// Get the current users position id.
$positionid = get_position_id($USER->id);

// Get the report names for the position id.
$reports = get_reports($positionid);

//If the position ID has atleast 1 report.
if(!empty($reports)){

    //Do this for each report.
    foreach($reports as $report){

        $reportName = get_report_name($report);
        $reportOwner = get_report_owner($report);

        //echo  "<pre>" . print_r($reportName) . "</pre>";

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

        // Get the Action responses with the report name.
        $sql = "SELECT qr.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND qq.surveyid = 1475
                  JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
                  JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.complete = 'y'
                 WHERE qqc.content = :reportname";

        // Get all actions related to the report.
        $actions = $DB->get_records_sql($sql, ['reportname' => $reportName]);

        //echo  "<pre>" . print_r($actions) . "</pre>";die;

        // Do this for each action.
        foreach($actions as $action){

            // If we have already completed this action.
            if (in_array($action->id, $completedactions)) {

                //Skip this action.
                continue;
            }

            // Add a page to the certificate.
            $pdf->AddPage();

            // Add this action to the completed actions.
            $completedactions[] = $action->id;

            // Set the text color to blue.
            $pdf->SetTextColor(21, 128, 188);

            // Print the Page heading.
            certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 25, '2018 Going for Gold Staff Survey');
            
            // Set the text color to black.
            $pdf->SetTextColor(0, 0, 0);

            // Print the Report Name.
            certificate_print_text($pdf, $x, $y + 12, 'C', 'Helvetica', 'B', 14, $reportName);

            // Print the service line.
            certificate_print_text($pdf, $x, $y + 20, 'C', 'Helvetica', 'B', 14, get_service_line($reportOwner));

            //Print the division.
            certificate_print_text($pdf, $x, $y + 28, 'C', 'Helvetica', 'B', 14, get_division($reportOwner));
            
            // Get the Culture Champion.
            $champname = $DB->get_record('questionnaire_response_text', array('response_id' => $response->id, 'question_id' => 34573));
            
            // Print the Culture Champion.
            certificate_print_text($pdf, $x, $y + 36, 'C', 'Helvetica', 'B', 14, '2018 GFG Survey Culture Champion: ' . $champname->response);

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
            $pdf->writeHTML('Action ' . $actionnum);

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

            // Add 1 to the action number.
            $actionnum++;
        }
        echo "<pre>You dont have access to any CIAPS.<pre>";

    }
    echo "<pre>You dont have access to any CIAPS.<pre>";
}
else{
    echo "<pre>You dont have access to any CIAPS.<pre>";
}