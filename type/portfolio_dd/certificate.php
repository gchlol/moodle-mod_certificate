<?php

use mod_certificate\type\Portfolio\portfolio_data;
use mod_certificate\type\Portfolio\portfolio_offsets;
use mod_certificate\type\portfolio_dd\portfolio_output;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

require_once($CFG->dirroot . '/mod/certificate/type/Portfolio/portfolio_offsets.php');
require_once($CFG->dirroot . '/mod/certificate/type/Portfolio/portfolio_data.php');
require_once($CFG->dirroot . '/mod/certificate/type/portfolio_dd/portfolio_output.php');

if (
    !empty($action) &&
    $certificate->orientation == 'L'
) {
    throw new moodle_exception('landscape_unsupported', 'mod_certificate');
}

$userid = optional_param('userid', $USER->id, PARAM_INT);
$user = $DB->get_record('user', ['id' => $userid]);

/** @var TCPDF|stdClass $pdf */
$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);
$pdf->SetRightMargin(15);
$pdf->AddPage();

// Define variables
$offsets = new portfolio_offsets();
$offsets->load_pdf_dimensions($pdf);

$offsets->x = 10;
$offsets->y = 35;

$offsets->row_indent = 1;

$offsets->code_y = 250;
$offsets->date_y = 260;
$offsets->page_num_y = 260;
$offsets->seal_x = 160;
$offsets->seal_y = 78;
$offsets->signature_x = 140;
$offsets->signature_y = 239;
$offsets->site_service_y = 245;
$offsets->watermark_x = 26;
$offsets->watermark_y = 58;
$offsets->watermark_w = 158;
$offsets->watermark_h = 170;

$cert_output = new portfolio_output(
    $certificate,
    $certrecord,
    $user,
    $pdf,
    $offsets
);

$course_sections = portfolio_data::get_course_section_data($userid);

$cert_output->output_cover_page($course);

foreach ($course_sections as $course_section) {
    $cert_output->output_courses(
        $course_section->courses,
        $course_section->header,
        $course_section->description,
        $course_section->required
    );
}

$cert_output->finalise();
