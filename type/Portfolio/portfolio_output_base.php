<?php

namespace mod_certificate\type\Portfolio;

use coding_exception;
use moodle_exception;
use stdClass;
use TCPDF;

require_once(__DIR__ . '/../Portfolio/portfolio_offsets.php');
require_once(__DIR__ . '/../Portfolio/portfolio_colour.php');
require_once(__DIR__ . '/../Portfolio/portfolio_string_manager.php');

abstract class portfolio_output_base {

    /**
     * Y offset to account for the page header on non-cover pages
     */
    protected const HEADER_OFFSET = 20;

    /**
     * Primary font used for output.
     */
    protected const OUTPUT_FONT = 'Helvetica';

    protected const ROOT_PATH = __DIR__;

    /**
     * 1 January 1980 is used to credit long serving staff who have not formally completed training
     */
    protected const MAGIC_DATE = 315496800;

    protected stdClass $certificate;
    protected stdClass $course;
    protected portfolio_offsets $offsets;
    protected stdClass $record;
    protected stdClass $user;
    protected portfolio_string_manager $string_manager;

    /**
     * @var TCPDF|stdClass
     */
    protected $pdf;

    /**
     * @var int[][] Cache of parsed hex colours.
     */
    private array $colour_cache;


    /**
     * @param stdClass $certificate Certificate instance.
     * @param stdClass $record Specific certificate instance.
     * @param stdClass $user User the certificate has been issued to.
     * @param TCPDF $pdf PDF instance used for output.
     * @param portfolio_offsets $offsets Offsets tracking object.
     * @throws moodle_exception
     */
    public function __construct(stdClass $certificate, stdClass $record, stdClass $user, TCPDF $pdf, portfolio_offsets $offsets) {
        $this->certificate = $certificate;
        $this->record = $record;
        $this->offsets = $offsets;
        $this->pdf = $pdf;
        $this->user = $user;

        [ $this->course ] = get_course_and_cm_from_instance($certificate, 'certificate');
        $this->string_manager = static::init_string_manager();
    }

    /**
     * Initialise portfolio language string manager.
     *
     * @return portfolio_string_manager String manager instance.
     */
    private static function init_string_manager(): portfolio_string_manager {
        $lang_path = static::ROOT_PATH . '/lang';
        $local_lang_root = is_dir($lang_path) ? $lang_path : null;

        return new portfolio_string_manager($local_lang_root);
    }

    /**
     * Output the unique cover page containing the intro text and images.
     *
     * @param stdClass $course Course to pull grade and outcome information from.
     * @return void
     */
    public abstract function output_cover_page(stdClass $course): void;

    /**
     * Gets the number of available output rows on general pages before a new page is required.
     *
     * @see output_course()
     *
     * @return int Number of output rows on pages.
     */
    protected abstract function page_rows(): int;

    /**
     * Gets the starting y offset for course list output on the cover page.
     *
     * @return int Y offset.
     */
    protected abstract function cover_offset(): int;

    /**
     * Gets the font scale for course list output.
     *
     * @return float Font scale.
     */
    protected function course_font_scale(): float {
        return 3;
    }

    //region Utilities

    /**
     * Get a portfolio language string from the local language file.
     *
     * @param string $identifier Identifier / key of the language string.
     * @param string|object|array $a Value to be injected into the language string.
     * @return string Language string value.
     */
    protected function get_string(string $identifier, $a = null): string {
        return $this->get_other_string($identifier, 'certificate', $a);
    }

    /**
     * Get a language string from any component with local portfolio overrides applied.
     *
     * @param string $identifier Identifier / key of the language string.
     * @param string $component Module the string is associated with.
     * @param string|object|array $a Value to be injected into the language string.
     * @return string
     */
    protected function get_other_string(string $identifier, string $component = '', $a = null): string {
        return $this->string_manager->get_string($identifier, $component, $a);
    }

    /**
     * Parses a hex colour string into an object containing r, g, and b components.
     *
     * @param string $hex Input hexadecimal string.
     * @return int[] Colour array containing the parsed r, g, and b components.
     */
    private static function parse_hex_colour(string $hex): array {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return [$r, $g, $b];
    }

    /**
     * Get a colour object from a language value containing a hexadecimal colour string.
     *
     * @param string $identifier Identifier for the language string containing the hexadecimal colour string.
     * @return array Colour array containing the parsed r, g, and b components.
     */
    protected function get_colour(string $identifier): array {
        if (!isset($this->colour_cache[$identifier])) {
            $this->colour_cache[$identifier] = static::parse_hex_colour($this->get_string($identifier));
        }

        return $this->colour_cache[$identifier];
    }

    /**
     * Calculate the y offset from row count.
     *
     * @return int The y offset.
     */
    protected function row_offset(): int {
        return $this->offsets->row_count * $this->line_height();
    }

    /**
     * Calculate the current y offset for an output line.
     *
     * @param int $additional Extra offset to apply to the calculated offset.
     * @return int Y offset for the current output line.
     */
    protected function page_offset(int $additional = 0): int {
        $y_offset = ($this->offsets->page == 1 ? $this->cover_offset() : $this->offsets->y(static::HEADER_OFFSET));
        $y_offset += $this->row_offset();
        $y_offset += $additional;

        return $y_offset;
    }

    /**
     * Calculate the number of output rows available on the cover page given the cover offset and line height.
     *
     * @return int Output rows available for the cover page.
     */
    protected function cover_rows(): int {
        $offset_difference = $this->cover_offset() - $this->offsets->y - static::HEADER_OFFSET;
        $row_difference = $offset_difference / $this->line_height();

        return $this->page_rows() - $row_difference;
    }

    /**
     * Get the number of output rows available for the current page.
     *
     * @return int Output rows available for the current page.
     */
    protected function current_page_rows(): int {
        if ($this->offsets->page == 1) {
            return $this->cover_rows();
        }

        return $this->page_rows();
    }

    //endregion Utilities

    //region Colours

    /**
     * Apply a colour to the PDF text given an identifier for a language string containing the hexadecimal colour string.
     *
     * @param string $identifier Identifier for the language string containing the hexadecimal colour string.
     * @return void
     */
    protected function apply_colour(string $identifier): void {
        $colour = $this->get_colour($identifier);

        $this->pdf->setTextColor(...$colour);
    }

    /**
     * Apply the colour in the `colour_primary` language string to the PDF text.
     *
     * @see apply_colour()
     *
     * @return void
     */
    protected function apply_primary_colour(): void {
        $this->apply_colour(portfolio_colour::PRIMARY);
    }

    /**
     * Apply the colour in the `colour_secondary` language string to the PDF text.
     *
     * @see apply_colour()
     *
     * @return void
     */
    protected function apply_secondary_colour(): void {
        $this->apply_colour(portfolio_colour::SECONDARY);
    }

    /**
     * Apply the colour in the `colour_base` language string to the PDF text.
     *
     * @see apply_colour()
     *
     * @return void
     */
    protected function apply_base_colour(): void {
        $this->apply_colour(portfolio_colour::BASE);
    }

    /**
     * Apply the colour in the `colour_minor` language string to the PDF text.
     *
     * @see apply_colour()
     *
     * @return void
     */
    protected function apply_minor_colour(): void {
        $this->apply_colour(portfolio_colour::MINOR);
    }

    //endregion Colours

    //region Document

    /**
     * Finalise the PDF document with any elements that require all pages to be present.
     *
     * @return void
     */
    public function finalise() {
        $this->output_page_numbers();
    }

    /**
     * Get the row line height for outputting course results.
     *
     * @return int Row line height.
     */
    protected function line_height(): int {
        return 4;
    }

    /**
     * Get the font size calculated from the line height.
     *
     * @param float $scale Base scale that is multiplied by the line height.
     * @return int Calculated font size including a 2pt reduction for padding.
     */
    protected function line_font_size(float $scale): int {
        return ( $scale * $this->line_height() ) - 2;
    }

    /**
     * Add a new page to the PDF.
     *
     * Updates offset values and draws the page border and frame.
     *
     * @return void
     * @throws coding_exception
     */
    protected function add_page(): void {
        // Add page
        $this->offsets->page++;
        $this->offsets->row_count = 0;
        $this->pdf->AddPage();

        // Draw new page elements. This must be before any other output otherwise text gets hidden
        $this->output_page_elements();

        // Output base page content
        $this->output_page_header();
        $this->output_page_footer();
        $this->output_page_footer_dynamic($this->course);
    }

    /**
     * Print text to the PDF document at given static x and y values,
     *
     * Utility wrapper around {@link certificate_print_text()} that provides better param ordering and defaults.
     *
     * @param string $text Text to be printed.
     * @param int $x X position to output at.
     * @param int $y Y position to output at.
     * @param int $size Font size.
     * @param string $align Text alignment; L=left, C=center, R=right.
     * @param string $style Font style; ''=normal, B=bold, I=italic, U=underline.
     * @param string|null $font Output font. If null {@link OUTPUT_FONT} will be used.
     * @return void
     */
    protected function output_text_static(string $text, int $x, int $y, int $size = 10, string $align = 'L', string $style = '', string $font = null): void {
        if ($font === null) {
            $font = static::OUTPUT_FONT;
        }

        /** @noinspection PhpParamsInspection */
        certificate_print_text(
            $this->pdf,
            $x,
            $y,
            $align,
            $font,
            $style,
            $size,
            $text
        );
    }

    /**
     * Print text to the PDF document at given offsets from base x and y values.
     *
     * @param string $text Text to be printed.
     * @param int $x_offset Offset from the base X value.
     * @param int $y_offset Offset from the base Y value.
     * @param int $size Font size.
     * @param string $align Text alignment; L=left, C=center, R=right.
     * @param string $style Font style; ''=normal, B=bold, I=italic, U=underline.
     * @param string|null $font Output font. If null {@link OUTPUT_FONT} will be used.
     * @return void
     */
    protected function output_text(string $text, int $x_offset, int $y_offset, int $size = 10, string $align = 'L', string $style = '', string $font = null): void {
        $this->output_text_static(
            $text,
            $this->offsets->x($x_offset),
            $this->offsets->y($y_offset),
            $size,
            $align,
            $style,
            $font
        );
    }

    /**
     * Output the standard elements used on every page.
     *
     * @return void
     */
    protected function output_page_elements(): void {
        // Output border frame
        certificate_print_image(
            $this->pdf,
            $this->certificate,
            CERT_IMAGE_BORDER,
            $this->offsets->border_x,
            $this->offsets->border_y,
            $this->offsets->border_w,
            $this->offsets->border_h
        );
        certificate_draw_frame($this->pdf, $this->certificate);
    }

    /**
     * Output the standardised cover page elements configurable in the module.
     *
     * @return void
     */
    protected function output_cover_page_elements(): void {
        $this->output_page_elements();

        // Output semi-transparent watermark
        $this->pdf->SetAlpha(0.2);
        certificate_print_image($this->pdf, $this->certificate, CERT_IMAGE_WATERMARK, $this->offsets->watermark_x, $this->offsets->watermark_y, $this->offsets->watermark_w, $this->offsets->watermark_h);
        $this->pdf->SetAlpha();

        // Output regular image elements
        certificate_print_image($this->pdf, $this->certificate, CERT_IMAGE_SEAL, $this->offsets->seal_x, $this->offsets->seal_y, '', '');
        certificate_print_image($this->pdf, $this->certificate, CERT_IMAGE_SIGNATURE, $this->offsets->signature_x, $this->offsets->signature_y, '', '');
    }

    /**
     * Print the Page x of x output for the current page.
     *
     * This must be called once all pages have been added to the document.
     * It is done this way so that center alignment will behave correctly.
     * Using getAliasNbPages results in incorrect alignment due to aligning on the template string not the final number.
     *
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_page_number(string $colour = portfolio_colour::MINOR): void {
        $this->apply_colour($colour);

        $this->output_text_static(
            'Page ' . $this->pdf->getPage() . ' of ' . $this->pdf->getNumPages(),
            $this->offsets->x,
            $this->offsets->page_num_y,
            10, 'C'
        );

        $this->apply_base_colour();
    }

    /**
     * Print page numbers for all pages in the document.
     *
     * This must be called once all pages have been added to the document.
     * It is done this way so that center alignment will behave correctly.
     * Using getAliasNbPages results in incorrect alignment due to aligning on the template string not the final number.
     *
     * @return void
     */
    protected function output_page_numbers() {
        $page_count = $this->pdf->getNumPages();

        // Don't print page count if we only have a single page
        if ($page_count == 1) {
            return;
        }

        for ($page = 1; $page <= $page_count; $page++) {
            $this->pdf->setPage($page);

            $this->output_page_number();
        }
    }

    /**
     * Output the printed on date page element.
     *
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_printed_date(string $colour = portfolio_colour::MINOR): void {
        $this->apply_colour($colour);

        $this->output_text_static(
            $this->get_string('printedon', date('j F Y')),
            $this->offsets->x,
            $this->offsets->date_y,
            10, 'R'
        );

        $this->apply_base_colour();
    }

    /**
     * Output the site service label page element.
     *
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_site_service(string $colour = portfolio_colour::PRIMARY): void {
        $this->apply_colour($colour);

        $this->output_text_static(
            $this->get_string('siteservicelabel', '<strong>' . $this->get_string('siteservice') . '</strong>'),
            $this->offsets->x,
            $this->offsets->site_service_y,
            14, 'C'
        );

        $this->apply_base_colour();
    }

    /**
     * Output the configured certificate grade element.
     *
     * @param stdClass $course Course used for grade and outcome information.
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_grade(stdClass $course, string $colour = portfolio_colour::MINOR): void {
        $this->apply_colour($colour);

        $this->output_text(
            certificate_get_grade($this->certificate, $course),
            0, 102,
            10, 'C'
        );

        $this->apply_base_colour();
    }

    /**
     * Output the configured certificate outcome element.
     *
     * @param stdClass $course Course used for grade and outcome information.
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_outcome(stdClass $course, string $colour = portfolio_colour::MINOR): void {
        $this->apply_colour($colour);

        $this->output_text(
            certificate_get_outcome($this->certificate, $course),
            0, 112,
            10, 'C'
        );

        $this->apply_base_colour();
    }

    /**
     * Output the configured certificate hours element.
     *
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     * @throws coding_exception
     */
    protected function output_hours(string $colour = portfolio_colour::MINOR): void {
        $this->apply_colour($colour);

        $this->output_text(
            get_string('credithours', 'certificate') . ': ' . $this->certificate->printhours,
            0, 122,
            10, 'C'
        );

        $this->apply_base_colour();
    }

    /**
     * Output the certificate code element.
     *
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_code(string $colour = portfolio_colour::MINOR): void {
        $this->apply_colour($colour);

        $this->output_text_static(
            certificate_get_code($this->certificate, $this->record),
            $this->offsets->x,
            $this->offsets->code_y,
            10, 'C'
        );

        $this->apply_base_colour();
    }

    /**
     * Output the page header designed for pages after the first.
     *
     * Contains the site service name and user's name.
     *
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_page_header(string $colour = portfolio_colour::PRIMARY): void {
        $this->apply_colour($colour);

        $this->output_text($this->get_string('siteservice'), 0, 0, 16, 'C', 'B');
        $this->output_text($this->get_string('title_contfor', fullname($this->user)), 0, 6, 12, 'C', 'B');

        $this->apply_base_colour();
    }

    /**
     * Output the standard page footer elements.
     *
     * Can be overridden to control exactly which elements are output.
     *
     * @return void
     */
    protected function output_page_footer(): void {
        $this->output_site_service();
        $this->output_printed_date();
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
    protected function output_page_footer_dynamic(stdClass $course): void {
        if ($this->certificate->printgrade > 0) {
            $this->output_grade($course);
        }

        if ($this->certificate->printoutcome > 0) {
            $this->output_outcome($course);
        }

        if (!empty($this->certificate->printhours)) {
            $this->output_hours();
        }

        if ($this->certificate->printnumber) {
            $this->output_code();
        }
    }

    //endregion Document

    //region Output

    /**
     * Output a list of courses under a given heading.
     *
     * @param stdClass[] $courses List of courses to output.
     * @param string $header Header string to output.
     * @param string $subheader Subheader conditionally output if not empty.
     * @param bool $display_empty When true the header and a special output will be displayed for headers with no courses.
     * @return void
     * @throws coding_exception
     */
    public function output_courses(array $courses, string $header, string $subheader, bool $display_empty): void {
        // Handle empty course list
        if (empty($courses)) {
            if ($display_empty) {
                $this->output_empty_course($header, $subheader);
            }

            return;
        }

        // If output is close to the end of the page create a new page for the courses
        if (( $this->offsets->row_count + 5 ) >= $this->current_page_rows()) {
            $this->add_page();
        }

        $this->output_course_header($header, $subheader);

        $course_values = array_values($courses);
        for ($index = 0; $index < count($course_values); $index++) {
            $course = $course_values[$index];
            $previous_course = $course_values[$index - 1] ?? null;
            $next_course = $course_values[$index + 1] ?? null;

            $this->output_course($course, $previous_course, $next_course, $header, $subheader);
        }

        $this->offsets->add_rows(3);
    }

    /**
     * Output course section with no completed courses message.
     *
     * @param string $header Header string passed to {@link output_course_header()}.
     * @param string $subheader Subheader string passed to {@link output_course_header()}.
     * @return void
     */
    protected function output_empty_course(string $header, string $subheader) {
        $this->output_course_header($header, $subheader);

        $this->apply_base_colour();

        $this->output_text_static($this->get_string('nonecomplete', $header), $this->offsets->x, $this->page_offset(2), $this->line_font_size(3.5), 'C');
        $this->offsets->add_rows(4);
    }

    /**
     * Output the course list header.
     *
     * Optionally outputs the continued header variant.
     *
     * @param string $header Header string to output.
     * @param string $subheader Subheader conditionally output if not empty.
     * @param bool $continued When true the alternate continued variant will be used.
     * @return void
     */
    protected function output_course_header(string $header, string $subheader, bool $continued = false): void {
        $course_header = $header;
        if ($continued) {
            $course_header .= ' ' . $this->get_string('continued');
        }

        $this->apply_primary_colour();

        // Shift the header up 2 units to account for the size
        $this->output_text_static($course_header, $this->offsets->x, $this->page_offset(-2), $this->line_font_size(4), 'L', 'B');
        $this->offsets->add_row();

        if (!empty($subheader)) {
            $this->apply_secondary_colour();
            $this->output_text_static($subheader, $this->offsets->x, $this->page_offset());
            $this->offsets->add_row();
        }

        $this->apply_base_colour();
    }

    /**
     * Output course completion date to the page.
     *
     * @param stdClass $course Course instance to output completion for.
     * @param stdClass|null $previous_course Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $next_course Next course instance to be output or null if this is the last course.
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     * @throws coding_exception
     */
    protected function output_course_completion(stdClass $course, ?stdClass $previous_course, ?stdClass $next_course, string $colour = portfolio_colour::BASE): void {
        $completion_output = userdate($course->timecompleted, get_string('strftimedate'));
        if ($course->timecompleted == self::MAGIC_DATE) {
            $completion_output = $this->get_string('magiccomplete');
        }

        $completion_offset = $this->pdf->getPageWidth() - $this->pdf->getMargins()['right'] - 35;

        $this->apply_colour($colour);

        $this->output_text_static(
            $completion_output,
            $completion_offset,
            $this->page_offset(),
            $this->line_font_size($this->course_font_scale())
        );

        $this->apply_base_colour();
    }

    /**
     * Output course name to the page.
     *
     * @param stdClass $course Course instance to output the name for.
     * @param stdClass|null $previous_course Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $next_course Next course instance to be output or null if this is the last course.
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_course_name(stdClass $course, ?stdClass $previous_course, ?stdClass $next_course, string $colour = portfolio_colour::BASE): void {
        // Automatically wrap the course name over as many lines as required as to not overlap the date
        $break_string = '%break%';
        $course_name_pieces = explode(
            $break_string,
            wordwrap(
                $course->fullname,
                80,
                $break_string
            )
        );

        $this->apply_colour($colour);

        foreach ($course_name_pieces as $course_name_piece) {
            $this->output_text_static(
                $course_name_piece,
                $this->offsets->x($this->offsets->row_indent),
                $this->page_offset(),
                $this->line_font_size($this->course_font_scale())
            );

            $this->offsets->add_row();
        }

        $this->apply_base_colour();
    }

    /**
     * Output course result row to the page.
     *
     * @param stdClass $course Course instance to output results for.
     * @param stdClass|null $previous_course Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $next_course Next course instance to be output or null if this is the last course.
     * @return void
     * @throws coding_exception
     */
    protected function output_course_result(stdClass $course, ?stdClass $previous_course, ?stdClass $next_course): void {
        $this->output_course_completion($course, $previous_course, $next_course);
        $this->output_course_name($course, $previous_course, $next_course);
    }

    /**
     * Output a course to the page.
     *
     * Dynamically adds pages as required depending on the number of rows.
     *
     * @param stdClass $course Course instance to output.
     * @param stdClass|null $previous_course Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $next_course Next course instance to be output or null if this is the last course.
     * @param string $header Header string passed to {@link output_course_header()}.
     * @param string $subheader Subheader string passed to {@link output_course_header()}.
     * @return void
     * @throws coding_exception
     */
    protected function output_course(stdClass $course, ?stdClass $previous_course, ?stdClass $next_course, string $header, string $subheader): void {
        if (!$course->timecompleted) {
            return;
        }

        // Simple result output on the current page
        if ($this->offsets->row_count <= $this->current_page_rows()) {
            $this->output_course_result($course, $previous_course, $next_course);

            return;
        }

        // Result output on new page
        $this->add_page();

        $this->output_course_header($header, $subheader, true);
        $this->output_course_result($course, $previous_course, $next_course);
    }

    //endregion Output
}