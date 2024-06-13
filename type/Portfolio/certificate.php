<?php

use mod_certificate\type\Portfolio\portfolio_offsets;
use mod_certificate\type\Portfolio\portfolio_output;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

require_once(__DIR__ . '/../Portfolio/portfolio_offsets.php');
require_once(__DIR__ . '/../Portfolio/portfolio_output.php');

$userid = optional_param('userid', $USER->id, PARAM_INT);
$user = $DB->get_record('user', ['id' => $userid]);

/** @var TCPDF|stdClass $pdf */
$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();
$page = 1;

// Define variables
$offsets = new portfolio_offsets();
$offsets->load_pdf_dimensions($pdf);

$offsets->x = 10;

if ($certificate->orientation == 'L') {
    // Landscape
    $offsets->y = 30;

    $offsets->code_y = 175;
    $offsets->date_y = 200;
    $offsets->page_num_y = 200;
    $offsets->seal_x = 240;
    $offsets->seal_y = 150;
    $offsets->signature_x = 47;
    $offsets->signature_y = 155;
    $offsets->watermark_x = 40;
    $offsets->watermark_y = 31;
    $offsets->watermark_w = 212;
    $offsets->watermark_h = 148;

} else {
    // Portrait
    $offsets->y = 68;

    $offsets->code_y = 250;
    $offsets->date_y = 254;
    $offsets->page_num_y = 254;
    $offsets->seal_x = 160;
    $offsets->seal_y = 78;
    $offsets->signature_x = 140;
    $offsets->signature_y = 239;
    $offsets->watermark_x = 26;
    $offsets->watermark_y = 58;
    $offsets->watermark_w = 158;
    $offsets->watermark_h = 170;
}

$cert_output = new portfolio_output(
    $certificate,
    $certrecord,
    $user,
    $pdf,
    $offsets
);

// Get completion for mandatory courses.
$sql = "
    SELECT  c.fullname,
            c.id,
            cc.timecompleted
    FROM {course_completions} cc
        JOIN {course} c ON c.id = cc.course
    WHERE   cc.timecompleted > 0 AND
            cc.userid = ? AND
            (
                c.category = 63 OR
                c.category = 9
            )
    ORDER BY c.idnumber DESC, c.fullname
";
$mandatorycompletions = $DB->get_records_sql($sql, [$userid]);

// Get completion for other courses.
$sql = "
    SELECT  c.fullname,
            c.id,
            cc.timecompleted
    FROM {course_completions} cc
        JOIN {course} c ON c.id = cc.course
    WHERE   cc.timecompleted > 0 AND
            cc.userid = ? AND
            c.category <> 97 AND
            c.category <> 63 AND
            c.category <> 9 AND
            c.idnumber <> 'GCUH' AND
            c.category <> 98
    ORDER BY cc.timecompleted  DESC
";
$othercompletions = $DB->get_records_sql($sql, [$userid]);

// Get completion for additional learning modules.
$sql = "
    SELECT  c.fullname,
            c.id,
            cc.timecompleted
    FROM {course_completions} cc
        JOIN {course} c ON c.id = cc.course
    WHERE   cc.timecompleted > 0 AND
            cc.userid = ? AND
            c.category = 98
    ORDER BY c.idnumber DESC, c.fullname
";
$additionalcompletions = $DB->get_records_sql($sql, [$userid]);


$cert_output->output_cover_page($course);

//Print Details mandatory
$cert_output->output_courses($mandatorycompletions, 'coursemandatory');

// Print details non mandatory
$cert_output->output_courses($othercompletions, 'courseother');

//Print details additional learning modules
$cert_output->output_courses($additionalcompletions, 'courseadditional');
