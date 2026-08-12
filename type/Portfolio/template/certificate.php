<?php
// This file is part of Moodle - http://moodle.org/
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
 * Portfolio implementation.
 *
 * @package     mod_certificate
 * @copyright   2022 Gold Coast Health
 * @author      Nicholas Lambell
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_certificate\type\Portfolio\portfolio_data;
use mod_certificate\type\Portfolio\portfolio_offsets;
use mod_certificate\type\portfolio_temp\portfolio_output;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/completionlib.php");

require_once(__DIR__ . '/../Portfolio/portfolio_offsets.php');
require_once(__DIR__ . '/../Portfolio/portfolio_data.php');
require_once(__DIR__ . '/portfolio_output.php');

if (
    !empty($action) &&
    $certificate->orientation == 'L'
) {
    throw new moodle_exception('landscape_unsupported', 'mod_certificate');
}

$userid = (int) $USER->id;
$user = $USER;

/** @var TCPDF|stdClass $pdf */
$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);
$pdf->SetRightMargin(15);
$pdf->AddPage();

// Define variables.
$offsets = new portfolio_offsets();
$offsets->load_pdf_dimensions($pdf);

$offsets->x = 15;
$offsets->y = 15;

$offsets->rowindent = 1;

$offsets->codey = 250;
$offsets->datey = 240;
$offsets->pagenumy = 273;
$offsets->sealx = 160;
$offsets->sealy = 78;
$offsets->signaturex = 140;
$offsets->signaturey = 239;
$offsets->siteservicey = 250;
$offsets->watermarkx = 26;
$offsets->watermarky = 58;
$offsets->watermarkw = 158;
$offsets->watermarkh = 170;

$certoutput = new portfolio_output(
    $certificate,
    $certrecord,
    $user,
    $pdf,
    $offsets
);

$coursesections = portfolio_data::get_course_section_data($userid);

$certoutput->output_cover_page($course);

foreach ($coursesections as $coursesection) {
    $certoutput->output_courses(
        $coursesection->courses,
        $coursesection->header,
        $coursesection->description,
        $coursesection->required
    );
}

$certoutput->finalise();
