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

$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
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
    $y = 10;
    $sealx = 150;
    $sealy = 220;
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
	$datey = 244;
}

// Add images and lines
// certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
certificate_draw_frame($pdf, $certificate);
// Set alpha to semi-transparency
$pdf->SetAlpha(0.2);
certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
$pdf->SetAlpha(1);
certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

// Add text
$pdf->SetTextColor(0, 60, 105);
certificate_print_text($pdf, $x, $y, 'C', 'Helvetica', '', 26, 'Peer review summary for');
$pdf->SetTextColor(0, 60, 105);
certificate_print_text($pdf, $x, $y + 10, 'C', 'Helvetica', 'B', 26, fullname($USER));

certificate_print_text($pdf, $x + 10, $y + 30, 'L', 'Helvetica', 'B', 10, "Peer no");
certificate_print_text($pdf, $x + 30, $y + 30, 'L', 'Helvetica', 'B', 10, "Peer name");
certificate_print_text($pdf, $x + 70, $y + 30, 'L', 'Helvetica', 'B', 10, "Peer email");

$where = "r.course = $course->id";
$sql = "SELECT rr.*
          FROM {recommend_request} rr
          JOIN {recommend} r ON r.id = rr.recommendid
         WHERE rr.userid = $USER->id
               AND r.course = $course->id
               AND rr.status = 5
               AND $where";
$peers = $DB->get_records_sql($sql);
$numrequests = count($peers);

$k = 1;

foreach ($peers as $peer) {
	certificate_print_text($pdf, $x + 10, $y + 30 + ($k * 4), 'L', 'Helvetica', '', 10, $k);
	certificate_print_text($pdf, $x + 30, $y + 30 + ($k * 4), 'L', 'Helvetica', '', 10, $peer->name);
	certificate_print_text($pdf, $x + 70, $y + 30 + ($k * 4), 'L', 'Helvetica', '', 10, $peer->email);
	$k++;
}

$legend = new html_table();
$legend->attributes['border'] = '1';
$legend->attributes['cellspacing'] = '0';
$legend->attributes['cellpadding'] = '1';
$legend->attributes['align'] = 'center';
$legend->attributes['style'] = 'width: 200px; margin: auto;';
$legend->head = array('Rating');
$legend->data = array(
    array('1 - Opportunity for Improvement'),
    array('2 - Meets Expectations'),
    array('3 - Exceeds Expectations')
);

$pdf->SetFontSize(8);
$pdf->SetXY($x,  $y + 50);

$sqlquestions = "SELECT rq.*
                   FROM {recommend_question} rq
                   JOIN {recommend} r ON rq.recommendid = r.id
                        AND r.course = $course->id
                        AND rq.type = 'label'
               ORDER BY rq.sortorder";
$q = 1;
$questions = $DB->get_records_sql($sqlquestions);

$table = new html_table();

$table->data[] = array('Question', 'Peer', 'Rating', 'Comments');
$table->attributes['border'] = '1';
$table->attributes['cellspacing'] = '0';
$table->attributes['cellpadding'] = '1';
$table->attributes['align'] = 'center';

// echo html_writer::tag('pre', print_r($questions, true));die;

foreach ($questions as $question) {
    if (empty($question->addinfo)) {
        continue;
    }

    $p = 1;

    $scoreorder = $question->sortorder + 1;
    $replyorder = $question->sortorder + 2;

    $cell1 = new html_table_cell();
    $cell1->text = $question->addinfo;
    $cell1->rowspan = $numrequests;

    $peers2 = $peers;
    $firstpeer = array_shift($peers2);

    if (isset($firstpeer->id)) {
        $conditions = array('recommendid' => $question->recommendid, 'sortorder' => $scoreorder);
        $scorequestion = $DB->get_record('recommend_question', $conditions);

        $conditions = array(
            'recommendid' => $firstpeer->recommendid,
            'requestid'   => $firstpeer->id,
            'questionid'  => $scorequestion->id
        );
        $score = $DB->get_record('recommend_reply', $conditions);

        // echo html_writer::tag('pre', print_r($score, true));

        $conditions = array('recommendid' => $question->recommendid, 'sortorder' => $replyorder);
        $replyquestion = $DB->get_record('recommend_question', $conditions);

        $conditions = array(
            'recommendid' => $firstpeer->recommendid,
            'requestid'   => $firstpeer->id,
            'questionid'  => $replyquestion->id
        );
        $firstreply = $DB->get_record('recommend_reply', $conditions);
    } else {
        $firstreply = new stdClass();
        $firstreply->reply = '';

        $score = new stdClass();
        $score->reply = '';
    }

    $table->data[] = array($cell1, $p, $score->reply, $firstreply->reply);

    foreach ($peers2 AS $peer) {
        $p++;

        $conditions = array('recommendid' => $question->recommendid, 'sortorder' => $scoreorder);
        $scorequestion = $DB->get_record('recommend_question', $conditions);

        $conditions = array(
            'recommendid' => $peer->recommendid,
            'requestid'   => $peer->id,
            'questionid'  => $scorequestion->id
        );
        $score = $DB->get_record('recommend_reply', $conditions);

        // echo html_writer::tag('pre', print_r($score, true));

        $conditions = array('recommendid' => $question->recommendid, 'sortorder' => $replyorder);
        $replyquestion = $DB->get_record('recommend_question', $conditions);

        $conditions = array(
            'recommendid' => $peer->recommendid,
            'requestid'   => $peer->id,
            'questionid'  => $replyquestion->id
        );
        $reply = $DB->get_record('recommend_reply', $conditions);

        $table->data[] = array($p, $score->reply, $reply->reply);
    }
}

$pdf->writeHTML(html_writer::table($legend));
$pdf->writeHTML(html_writer::table($table));