<?php

namespace mod_certificate\type\Portfolio;

use coding_exception;
use stdClass;
use TCPDF;

/**
 * Do not use this class as an example for portfolio output!
 * Instead, the templates and readme in mod/certificate/type/Portfolio/template/
 */
class portfolio_output {

    /**
     * @var stdClass The course containing this portfolio.
     */
    protected $course;

    /**
     * @var stdClass Module instance.
     */
    private $certificate;

    /**
     * @var stdClass Specific certificate instance.
     */
    private $record;

    /**
     * @var portfolio_offsets Offsets tracking object.
     */
    private $offsets;

    /**
     * @var TCPDF|stdClass PDF instance used for output.
     */
    private $pdf;

    /**
     * @var stdClass User the certificate has been issued to.
     */
    private $user;

    public function __construct(stdClass $certificate, stdClass $record, stdClass $user, TCPDF $pdf, portfolio_offsets $offsets) {
        $this->certificate = $certificate;
        $this->record = $record;
        $this->offsets = $offsets;
        $this->pdf = $pdf;
        $this->user = $user;

        [ $this->course ] = get_course_and_cm_from_instance($certificate, 'certificate');
    }

    /**
     * Output a list of courses under a given heading.
     *
     * @param stdClass[] $courses List of courses to output.
     * @param string $identifier Lang identifier for the heading. e.g. coursemandatory links to portfolio_coursemandatory {@see output_course_header()}.
     * @return void
     * @throws coding_exception
     */
    public function output_courses(array $courses, string $identifier): void {
        $this->output_course_header($identifier);

        foreach ($courses as $course) {
            $this->output_course($course, $identifier);
        }

        $this->offsets->row_count += 2;
    }

    /**
     * Output the unique cover page containing the intro text and images.
     *
     * @param stdClass $course Course to pull grade and outcome information from.
     * @return void
     * @throws coding_exception
     */
    public function output_cover_page(stdClass $course): void {
        // Add images and lines
        certificate_print_image($this->pdf, $this->certificate, CERT_IMAGE_BORDER, $this->offsets->border_x, $this->offsets->border_y, $this->offsets->border_w, $this->offsets->border_h);
        certificate_draw_frame($this->pdf, $this->certificate);

        // Set alpha to semi-transparency
        $this->pdf->SetAlpha(0.2);
        certificate_print_image($this->pdf, $this->certificate, CERT_IMAGE_WATERMARK, $this->offsets->watermark_x, $this->offsets->watermark_y, $this->offsets->watermark_w, $this->offsets->watermark_h);

        $this->pdf->SetAlpha();
        certificate_print_image($this->pdf, $this->certificate, CERT_IMAGE_SEAL, $this->offsets->seal_x, $this->offsets->seal_y, '', '');
        certificate_print_image($this->pdf, $this->certificate, CERT_IMAGE_SIGNATURE, $this->offsets->signature_x, $this->offsets->signature_y, '', '');

        // Add text
        $this->pdf->SetTextColor(0, 60, 105);
        $this->print_text(get_string('portfolio_site', 'certificate'), 10, 8, 37.5);
        $this->print_text(get_string('portfolio_service', 'certificate'), 10, 22, 37.5);
        $this->print_text(get_string('portfolio_title', 'certificate'), 10, 36, 37.5);

        $this->pdf->SetTextColor(128, 128, 128);
        $this->print_text(get_string('portfolio_preuser', 'certificate'), 0, 60, 16, 'C');

        $this->pdf->SetTextColor(0, 60, 105);
        $this->print_text(fullname($this->user), 0, 70, 32, 'C', 'B');

        $this->pdf->SetTextColor(128, 128, 128);
        $this->print_text(get_string('portfolio_postuser', 'certificate'), 0, 90, 16, 'C');

        $this->output_page_footer();
        $this->output_page_footer_dynamic($course);
    }

    /**
     * Calculate the y offset from row count.
     *
     * @return int Y offset.
     */
    private function row_offset(): int {
        return $this->offsets->row_count * 4;
    }

    /**
     * Add a new page to the PDF.
     *
     * Updates offset values and draws the page border and frame.
     *
     * @return void
     */
    private function add_page(): void {
        // The first page only gets a page number if there's more than one page, so we need to print it here
        if ($this->offsets->page == 1) {
            $this->print_page_number();
        }

        // Add page
        $this->offsets->page++;
        $this->offsets->row_count = 0;
        $this->pdf->AddPage();

        // Output page count
        $this->print_page_number();

        // Draw new page elements
        certificate_print_image($this->pdf, $this->certificate, CERT_IMAGE_BORDER, $this->offsets->border_x, $this->offsets->border_y, $this->offsets->border_w, $this->offsets->border_h);
        certificate_draw_frame($this->pdf, $this->certificate);
    }

    /**
     * Print the Page x of x output for the current page.
     *
     * @return void
     */
    private function print_page_number(): void {
        /** @noinspection PhpParamsInspection */
        certificate_print_text(
            $this->pdf,
            $this->offsets->x,
            $this->offsets->page_num_y,
            'C', 'Helvetica', '', 10,
            'Page ' . $this->offsets->page . ' of ' . $this->pdf->getAliasNbPages()
        );
    }

    /**
     * Print text to the PDF document at given offsets from base x and y values.
     *
     * Utility wrapper around {@link certificate_print_text()} that provides better param ordering and defaults.
     *
     * @param string $text Text to be printed.
     * @param int $x_offset Offset from the base X value.
     * @param int $y_offset Offset from the base Y value.
     * @param int $size Font size.
     * @param string $align Text alignment; L=left, C=center, R=right.
     * @param string $style Font style; ''=normal, B=bold, I=italic, U=underline.
     * @param string $font Output font.
     * @return void
     */
    private function print_text(string $text, int $x_offset, int $y_offset, int $size = 10, string $align = 'L', string $style = '', string $font ='Helvetica'): void {
        /** @noinspection PhpParamsInspection */
        certificate_print_text(
            $this->pdf,
            $this->offsets->x($x_offset),
            $this->offsets->y($y_offset),
            $align,
            $font,
            $style,
            $size,
            $text
        );
    }

    /**
     * Output the page header designed for pages after the first.
     *
     * Contains the site service name and user's name.
     *
     * @return void
     * @throws coding_exception
     */
    private function output_page_header(): void {
        $this->pdf->SetTextColor(0, 60, 105);

        $this->print_text(get_string('portfolio_siteservice', 'certificate'), 0, 0, 16, 'C', 'B');
        $this->print_text(get_string('portfolio_title_contfor', 'certificate', fullname($this->user)), 0, 6, 12, 'C', 'B');

        $this->pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Output the page footer.
     *
     * Contains the current date and site service name.
     *
     * @return void
     * @throws coding_exception
     */
    private function output_page_footer(): void {
        $this->pdf->SetTextColor(0, 0, 0);
        /** @noinspection PhpParamsInspection */
        certificate_print_text($this->pdf, $this->offsets->x, $this->offsets->date_y, 'R', 'Helvetica', '', 10, get_string('portfolio_printedon', 'certificate', date('j F Y')));

        $this->pdf->SetTextColor(128, 128, 128);
        $this->print_text(get_string('portfolio_siteservicelabel', 'certificate'), 0, 190, 16, 'C');

        $this->pdf->SetTextColor(0, 60, 105);
        $this->print_text(get_string('portfolio_siteservice', 'certificate'), 0, 196, 16, 'C', 'B');

        $this->pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Output the more dynamic page footer content.
     *
     * Contains grade information as well as certificate specific information like the hours and code.
     *
     * @param stdClass $course Course used for grade and outcome information.
     * @return void
     * @throws coding_exception
     */
    private function output_page_footer_dynamic(stdClass $course): void {
        $this->pdf->SetTextColor(0, 60, 105);

        $this->print_text(certificate_get_grade($this->certificate, $course), 0, 102, 10, 'C', '', 'Times');
        $this->print_text(certificate_get_outcome($this->certificate, $course), 0, 112, 10, 'C', '', 'Times');

        if ($this->certificate->printhours) {
            $this->print_text(get_string('credithours', 'certificate') . ': ' . $this->certificate->printhours, 0, 122, 10, 'C', '', 'Times');
        }

        /** @noinspection PhpParamsInspection */
        certificate_print_text($this->pdf, $this->offsets->x, $this->offsets->code_y, 'C', 'Times', '', 10, certificate_get_code($this->certificate, $this->record));

        $this->pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Output the course list header.
     *
     * Optionally outputs the continued header variant.
     *
     * @param string $identifier Lang identifier for the heading. e.g. coursemandatory links to portfolio_coursemandatory.
     * @param bool $continued When true the alternate continued variant will be used.
     * @return void
     * @throws coding_exception
     */
    private function output_course_header(string $identifier, bool $continued = false): void {
        $y_offset = ($this->offsets->page == 1 ? 100 : 20);

        $this->pdf->SetTextColor(0, 0, 0);
        $this->print_text(get_string("portfolio_$identifier" . ($continued ? '_cont' : ''), 'certificate'), 10, $y_offset + $this->row_offset(), 10, 'L', 'B');
    }

    /**
     * Output a course result row with the course name and completion date.
     *
     * @param stdClass $course The course instance to output.
     * @return void
     * @throws coding_exception
     */
    private function output_course_result(stdClass $course) {
        $completion_output = userdate($course->timecompleted, get_string('strftimedate'));
        if ($course->timecompleted == 315496800) {
            $completion_output = "Completed";
        }

        $y_offset = ($this->offsets->page == 1 ? 100 : 20);

        $this->print_text($course->fullname, 12.5, $y_offset + $this->row_offset());
        $this->print_text($completion_output, 145, $y_offset + $this->row_offset(), 10, 'R');
    }

    /**
     * Output a course result to the page.
     *
     * Dynamically adds pages as required depending on the number of rows.
     *
     * @param stdClass $course The course instance to output results for.
     * @param string $identifier Lang identifier for the heading. e.g. coursemandatory links to portfolio_coursemandatory.
     * @return void
     * @throws coding_exception
     */
    private function output_course(stdClass $course, string $identifier): void {
        if (!$course->timecompleted) {
            return;
        }

        $this->offsets->row_count++;

        // Simple result output on the current page
        if (
            (
                $this->offsets->page == 1 &&
                $this->offsets->row_count < 16
            ) ||
            (
                $this->offsets->page > 1 &&
                $this->offsets->row_count < 30
            )
        ) {
            $this->output_course_result($course);

            return;
        }

        // Result output on new page
        $this->add_page();
        $this->output_page_header();

        // Output course type header
        $this->output_course_header($identifier, true);

        $this->offsets->row_count++;

        // Output results
        $this->output_course_result($course);

        $this->output_page_footer();
        $this->output_page_footer_dynamic($this->course);
    }
}