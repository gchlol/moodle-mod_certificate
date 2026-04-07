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

    $offsets->codey = 175;
    $offsets->datey = 200;
    $offsets->pagenumy = 200;
    $offsets->sealx = 240;
    $offsets->sealy = 150;
    $offsets->signaturex = 47;
    $offsets->signaturey = 155;
    $offsets->watermarkx = 40;
    $offsets->watermarky = 31;
    $offsets->watermarkw = 212;
    $offsets->watermarkh = 148;

} else {
    // Portrait
    $offsets->y = 68;

    $offsets->codey = 250;
    $offsets->datey = 254;
    $offsets->pagenumy = 254;
    $offsets->sealx = 160;
    $offsets->sealy = 78;
    $offsets->signaturex = 140;
    $offsets->signaturey = 239;
    $offsets->watermarkx = 26;
    $offsets->watermarky = 58;
    $offsets->watermarkw = 158;
    $offsets->watermarkh = 170;
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
