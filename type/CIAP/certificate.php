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
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @var TCPDF $pdf
 */
$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->SetProtection(array('modify'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_FOOTER);
$pdf->SetMargins(10, 20, 10, true);

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

$contentareaheight = 200;

// Get team information responses.
$responses = $DB->get_records('questionnaire_response', array('questionnaireid' => 1318, 'userid' => $USER->id, 'complete' => 'y'));

$completedactions = [];

foreach ($responses as $response) {
    $sql = "SELECT qqc.*
              FROM {questionnaire_quest_choice} qqc
              JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND qq.name = 'Report Name'
              JOIN {questionnaire_resp_single} qrs ON qrs.response_id = $response->id
                   AND qrs.question_id = qq.id
                   AND qqc.id = qrs.choice_id
             LIMIT 1";
    $reportname = $DB->get_record_sql($sql);

    $sql = "SELECT qqc.*
              FROM {questionnaire_quest_choice} qqc
              JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND qq.surveyid = 1320
              JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
              JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.userid = $USER->id
                   AND qr.complete = 'y'
             WHERE qqc.content = :reportname";
    if (!$DB->record_exists_sql($sql, ['reportname' => $reportname->content])) {
        continue;
    }

    // $pdf->AddPage();

    // Add images and lines
    certificate_draw_frame($pdf, $certificate);

    // Set alpha to semi-transparency
    $pdf->SetAlpha(0.2);
    certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
    $pdf->SetAlpha(1);
    certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
    certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

    $actionnum = 1;
    $k = 1;

    $sql = "SELECT qr.*
              FROM {questionnaire_quest_choice} qqc
              JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                   AND (qq.surveyid = 1316 OR qq.surveyid = 1336)
              JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                   AND qqc.id = qrs.choice_id
              JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                   AND qr.userid = $USER->id
                   AND qr.complete = 'y'
             WHERE qqc.content = :reportname";
    $actions = $DB->get_records_sql($sql, ['reportname' => $reportname->content]);

    foreach ($actions as $action) {
        if (in_array($action->id, $completedactions)) {
            continue;
        }

        $pdf->AddPage();

        $completedactions[] = $action->id;
        $pdf->SetTextColor(21, 128, 188);
        certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', 'B', 25, '2018 Going for Gold Staff Survey');
        $pdf->SetTextColor(0, 0, 0);

        certificate_print_text($pdf, $x, $y + 12, 'C', 'Helvetica', 'B', 14, $reportname->content);

        $sql = "SELECT qqc.*
              FROM {questionnaire_quest_choice} qqc
              JOIN {questionnaire_resp_single} qrs ON qrs.response_id = $response->id
                   AND qqc.id = qrs.choice_id
              JOIN {questionnaire_question} qq ON qrs.question_id = qq.id
                   AND qq.name LIKE 'Service Line %'
             LIMIT 1";
        $serviceline = $DB->get_record_sql($sql);

        certificate_print_text($pdf, $x, $y + 20, 'C', 'Helvetica', 'B', 14, $serviceline->content);

        $sql = "SELECT qqc.*
              FROM {questionnaire_quest_choice} qqc
              JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                   AND qrs.response_id = $response->id
                   AND qqc.id = qrs.choice_id
             WHERE qqc.question_id = 34434";
        $division = $DB->get_record_sql($sql);

        certificate_print_text($pdf, $x, $y + 28, 'C', 'Helvetica', 'B', 14, $division->content);

        $champname = $DB->get_record('questionnaire_response_text', array('response_id' => $response->id, 'question_id' => 34573));
        certificate_print_text($pdf, $x, $y + 36, 'C', 'Helvetica', 'B', 14, '2018 GFG Survey Culture Champion: ' . $champname->response);

        $pdf->SetTextColor(21, 128, 188);
        certificate_print_text($pdf, $x, $y + 50, 'C', 'Helvetica', 'B', 18, 'Continuous Improvement Action Plan');

        $pdf->writeHTML('<br>');
        $pdf->writeHTML('<br>');

        $image = html_writer::img($CFG->wwwroot . '/mod/certificate/type/CIAP/BACS.jpg', 'Going for Gold', array('width' => 455, 'height' => 50));
        $pdf->writeHTML(html_writer::tag('p', $image, array('style' => 'text-align: center;')));

        $pdf->SetFont('helvetica', 'B', '16');
        $pdf->writeHTML('Action ' . $actionnum);
        $pdf->SetTextColor(0, 0, 0);

        // Answer 1.
        $firstquestion = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Start, Stop or Keep', 'deleted' => 'n'));
        $sql = "SELECT qqc.*
                  FROM {questionnaire_quest_choice} qqc
                  JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                       AND qqc.id = qrs.choice_id
                       AND qrs.response_id = $action->id
                 WHERE qqc.question_id = :questionid";
        $answerone = $DB->get_record_sql($sql, array('questionid' => $firstquestion->id));
        $pdf->SetFont('helvetica', 'I', '14');
        $pdf->writeHTML($answerone->content);
        $pdf->writeHTML('<br>');

        $secondquestion = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Related to Pillar', 'deleted' => 'n'));
        $answertwo = $DB->get_record_sql($sql, array('questionid' => $secondquestion->id));

        // Question 2.
        $pdf->SetFont('helvetica', 'B', '14');
        $pdf->writeHTML('What research program from the Staff Survey does this action link to?');
        $pdf->SetFont('helvetica', 'I', '14');
        $pdf->writeHTML($answertwo ->content);
        $pdf->writeHTML('<br>');

        $pdf->SetFont('helvetica', 'B', '14');
        $pdf->writeHTML('What is the action in response to?');

        $q17 = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'What is it in response to', 'deleted' => 'n'));
        $query = "SELECT qqc.*
                    FROM {questionnaire_quest_choice} qqc
                    JOIN {questionnaire_resp_single} qrs ON qqc.question_id = qrs.question_id
                         AND qrs.response_id = $action->id
                         AND qqc.id = qrs.choice_id
                   WHERE qqc.question_id = $q17->id";

        $q18 = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Survey Q it relates to', 'deleted' => 'n'));
        if ($a18 = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $q18->id))) {
            $pdf->SetFont('helvetica', '', '14');
            $pdf->writeHTML('<i>Survey Question: ' . $a18->response . '</i>');
        } else if ($a19 = $DB->get_record_sql($query)) {
            $pdf->SetFont('helvetica', 'I', '14');
            $pdf->writeHTML($a19->content);
        }
        $pdf->writeHTML('<br>');

        // Question 5.
        $fifthquestion = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Describe Action', 'deleted' => 'n'));
        $answerfive = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $fifthquestion->id));
        $pdf->SetFont('helvetica', 'B', '14');
        $pdf->writeHTML('What is the action your team has agreed to?');
        $pdf->SetFont('helvetica', 'I', '14');
        $pdf->writeHTML(strip_tags($answerfive->response, '<ul><li><ol>'));
        $pdf->writeHTML('<br>');

        // Question 6.
        $sixthquestion = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Implemented by', 'deleted' => 'n'));
        $answersix = $DB->get_record('questionnaire_response_date', array('response_id' => $action->id, 'question_id' => $sixthquestion->id));
        $date = new DateTime($answersix->response);
        // $pdf->writeHTML('Implemented by: ' . html_writer::tag('i', $date->format('d/m/Y')));
        $pdf->SetFont('helvetica', '', '14');
        $pdf->writeHTML('<b>Implemented by:</b> <i>' . $date->format('d/m/Y') . '</i>');
        $pdf->writeHTML('<br>');

        // Question 7.
        $seventhquestion = $DB->get_record('questionnaire_question', array('surveyid' => $action->questionnaireid, 'name' => 'Responsible Person', 'deleted' => 'n'));
        $answerseven = $DB->get_record('questionnaire_response_text', array('response_id' => $action->id, 'question_id' => $seventhquestion->id));
        $pdf->SetFont('helvetica', 'B', '14');
        $pdf->writeHTML('Who will be responsible for implementing the action? <span style="font-weight: normal;"><i>' . $answerseven->response . '</i></span>');

        if ($actionnum < count($actions)) {
            // $pdf->AddPage();
        }

        $actionnum++;
    }
}
