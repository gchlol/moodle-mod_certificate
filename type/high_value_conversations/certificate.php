<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}

if (
    !empty($action) &&
    $certificate->orientation == 'P'
) {
    throw new moodle_exception('portrait_unsupported', 'mod_certificate');
}

$userid = optional_param('userid', $USER->id, PARAM_INT);
$user = $DB->get_record('user', ['id' => $userid]);

/** @var TCPDF|stdClass $pdf */
$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->SetProtection([ 'modify' ]);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

$x = 10;
$y = 170;
$course_y = 112;
$date_y = 125;
$name_y = 80;

$fontsans = get_config('certificate', 'fontsans');

certificate_print_image(
    $pdf,
    $certificate,
    CERT_IMAGE_BORDER,
    0, 0,
    $pdf->getPageWidth(),
    $pdf->getPageHeight()
);

$pdf->setTextColor(23, 50, 116);
certificate_print_text($pdf, $x, $name_y, 'C', $fontsans, '', 50, fullname($user));
certificate_print_text($pdf, $x, $course_y, 'C', $fontsans, 'B', 25, format_string($course->fullname));
certificate_print_text($pdf, $x, $date_y, 'C', $fontsans, '', 20, certificate_get_date($certificate, $certrecord, $course));
