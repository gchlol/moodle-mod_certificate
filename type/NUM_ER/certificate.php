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

$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->SetProtection(array('modify'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->SetMargins(10, 0, 10, true);
$pdf->AddPage();

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
    $y = 40;
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

// Add images and lines

certificate_draw_frame($pdf, $certificate);
// Set alpha to semi-transparency
$pdf->SetAlpha(0.2);
certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
$pdf->SetAlpha(1);
certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

// Add text
$pdf->SetTextColor(0, 0, 0);

$questionnaire = $DB->get_record('questionnaire', array('id' => 1204));

// Get most recent attempt.
$sql = "SELECT * FROM {questionnaire_response} WHERE questionnaireid = $questionnaire->id AND complete = 'y' AND userid = $USER->id ORDER BY submitted DESC";
$response = $DB->get_record_sql($sql);

$table = new html_table();
$table->attributes = array('border' => 1, 'style' => 'padding: 5px;');

$cell = new html_table_cell();
$cell->style = 'font-weight: bold; color: white; padding: 10px; font-size: 14; text-align: center;';
$cell->attributes = array('bgcolor' => 'rgb(192, 192, 192)');
$cell->colspan = 2;
$cell->text = 'Education and Research';

$table->head = array($cell);

$max = 16;
$current = 0;

$used = array();

$timeframes = array('1-3 months', '4-11 months', '12 months or more');
foreach ($timeframes as $key => $timeframe) {
    $cell2 = new html_table_cell();
    $cell2->colspan = 2;
    $cell2->text = $timeframe;

    $row = new html_table_row(array($cell2));
    $current++;
    $row->attributes = array('bgcolor' => 'rgb(0, 102, 153)');
    $row->style = 'font-weight: bold; font-size: 12; color: white;';

    if ($current >= $max) {
        $current = 0;
        break;
    }

    $table->data[] = $row;

    $sql = "SELECT qqp.*
              FROM {questionnaire_resp_single} qrs
              JOIN {questionnaire_quest_choice} qqc ON qrs.choice_id = qqc.id
                   AND qqc.content = '$timeframe'
              JOIN {questionnaire_question} qq ON qrs.question_id = qq.id
              JOIN {questionnaire_dependency} qd ON qq.id = qd.questionid
              JOIN {questionnaire_question} qqp ON qd.dependquestionid = qqp.id
             WHERE qrs.response_id = :responseid";
    $actions = $DB->get_records_sql($sql, array('responseid' => $response->id));

    if (empty($actions)) {
        $cell3 = new html_table_cell();
        $cell3->colspan = 2;
        $cell3->text = 'You have not selected any actions for this timeframe.';

        $row = new html_table_row();
        $row->style = 'font-size: 11; background-color: white;';
        $row->cells = array($cell3);

        if ($current >= $max) {
            $current = 0;
            break;
        }

        $table->data[] = $row;
        $current++;
    }

    foreach ($actions as $action) {
        $cell3 = new html_table_cell();
        $cell3->style = 'width: 32.5%;';
        $cell3->text = $action->name;

        $cell4 = new html_table_cell();
        $cell4->style = 'width: 67.5%;';

        $row = new html_table_row();
        $row->style = 'font-size: 11; background-color: white;';
        $row->cells = array($cell3, $cell4);

        if ($current >= $max) {
            $current = 0;
            break 2;
        }

        $table->data[] = $row;
        $used[] = $action->name;
        $current++;

        unset($actions[$action->id]);
    }

    unset($timeframes[$key]);
}

$date = new DateTime();
$date->setTimestamp($response->submitted);

$pdf->SetFont('helvetica');
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::empty_tag('br'));

$pdf->writeHTML(html_writer::tag('h1', format_string($questionnaire->name), array('style' => 'text-align: center;')));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::tag('h2', fullname($USER), array('style' => 'text-align: center;')));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::tag('h3', $date->format('jS F Y'), array('style' => 'text-align: center;')));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::tag(
    'p',
    'This learning plan is a point in time reflection of your own identified learning needs. As you progress and gain more experience your learning needs will change. You may go back and redo the orientation/onboarding gap analysis tool for a revised learning plan.',
    array('style' => 'text-align: center;')));
$pdf->writeHTML(html_writer::empty_tag('br'));
$pdf->writeHTML(html_writer::table($table));

if (!empty($timeframes)) {
    $table = new html_table();
    $table->attributes = array('border' => 1, 'style' => 'padding: 5px;');

    $cell = new html_table_cell();
    $cell->style = 'font-weight: bold; color: white; padding: 10px; font-size: 14; text-align: center;';
    $cell->attributes = array('bgcolor' => 'rgb(192, 192, 192)');
    $cell->colspan = 2;
    $cell->text = 'Direct Care';

    $table->head = array($cell);

    foreach ($timeframes as $key => $timeframe) {
        $sql = "SELECT qqp.*
                  FROM {questionnaire_resp_single} qrs
                  JOIN {questionnaire_quest_choice} qqc ON qrs.choice_id = qqc.id
                       AND qqc.content = '$timeframe'
                  JOIN {questionnaire_question} qq ON qrs.question_id = qq.id
                  JOIN {questionnaire_dependency} qd ON qq.id = qd.questionid
                  JOIN {questionnaire_question} qqp ON qd.dependquestionid = qqp.id
                 WHERE qrs.response_id = :responseid";
        $actions = $DB->get_records_sql($sql, array('responseid' => $response->id));

        foreach ($actions as $actionkey => $action) {
            if (array_search($action->name, $used)) {
                unset($actions[$actionkey]);
            }
        }

        if (!empty($actions)) {
            $cell2 = new html_table_cell();
            $cell2->colspan = 2;
            $cell2->text = $timeframe;

            $row = new html_table_row(array($cell2));
            $current++;
            $row->attributes = array('bgcolor' => 'rgb(0, 102, 153)');
            $row->style = 'font-weight: bold; font-size: 12; color: white;';

            $table->data[] = $row;
        }

        foreach ($actions as $action) {
            $cell3 = new html_table_cell();
            $cell3->style = 'width: 25%;';
            $cell3->text = $action->name;

            $cell4 = new html_table_cell();
            $cell4->style = 'width: 75%;';

            $row = new html_table_row();
            $row->style = 'font-size: 11; background-color: white;';
            $row->cells = array($cell3, $cell4);

            $table->data[] = $row;
        }
    }

    $pdf->AddPage();

    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));
    $pdf->writeHTML(html_writer::empty_tag('br'));

    $pdf->writeHTML(html_writer::table($table));
}

$pages = $pdf->getNumPages();

for ($i = 1; $i <= $pages; $i++) {
    $pdf->setPage($i);
    certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
}
