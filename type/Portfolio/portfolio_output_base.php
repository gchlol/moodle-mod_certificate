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

namespace mod_certificate\type\Portfolio;

use stdClass;
use TCPDF;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../Portfolio/portfolio_offsets.php');
require_once(__DIR__ . '/../Portfolio/portfolio_colour.php');
require_once(__DIR__ . '/../Portfolio/portfolio_string_manager.php');

/**
 * Base portfolio output class.
 *
 * @package    mod_certificate
 * @copyright  2022 Gold Coast Health
 * @author     Nicholas Lambell
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class portfolio_output_base {

    /**
     * @var int Y offset to account for the page header on non-cover pages
     */
    protected const HEADER_OFFSET = 20;

    /**
     * @var string Primary font used for output.
     */
    protected const OUTPUT_FONT = 'Helvetica';

    /**
     * @var string Root path for the portfolio implementation.
     */
    protected const ROOT_PATH = __DIR__;

    /**
     * @var int Additional right offset for CPD output.
     */
    protected const RIGHT_OFFSET_CPD = 2;

    /**
     * @var int Right offset padding to ensure text does not get too close to the right edge of the page.
     */
    protected const RIGHT_OFFSET_PADDING = 35;

    /**
     * @var int 1 January 1980 is used to credit long serving staff who have not formally completed training
     */
    protected const MAGIC_DATE = 315496800;

    /**
     * @var stdClass Module instance.
     */
    protected stdClass $certificate;

    /**
     * @var stdClass Course containing this portfolio.
     */
    protected stdClass $course;

    /**
     * @var portfolio_offsets Offsets tracking object.
     */
    protected portfolio_offsets $offsets;

    /**
     * @var TCPDF|stdClass PDF instance used for output.
     */
    protected stdClass|TCPDF $pdf;

    /**
     * @var stdClass Specific certificate instance.
     */
    protected stdClass $record;

    /**
     * @var stdClass User the certificate has been issued to.
     */
    protected stdClass $user;

    /**
     * @var portfolio_string_manager Language string manager instance.
     */
    protected portfolio_string_manager $stringmanager;

    /**
     * @var int[][] Cache of parsed hex colours.
     */
    private array $colourcache;


    /**
     * Constructor.
     *
     * @param stdClass $certificate
     * @param stdClass $record
     * @param stdClass $user
     * @param TCPDF $pdf
     * @param portfolio_offsets $offsets
     */
    public function __construct(stdClass $certificate, stdClass $record, stdClass $user, TCPDF $pdf, portfolio_offsets $offsets) {
        $this->certificate = $certificate;
        $this->record = $record;
        $this->offsets = $offsets;
        $this->pdf = $pdf;
        $this->user = $user;

        [ $this->course ] = get_course_and_cm_from_instance($certificate, 'certificate');
        $this->stringmanager = static::init_string_manager();
    }

    /**
     * Initialise portfolio language string manager.
     *
     * @return portfolio_string_manager String manager instance.
     */
    protected static function init_string_manager(): portfolio_string_manager {
        $langpath = static::ROOT_PATH . '/lang';
        $locallangroot = is_dir($langpath) ? $langpath : null;

        return new portfolio_string_manager($locallangroot);
    }

    /**
     * Output the unique cover page containing the intro text and images.
     *
     * @param stdClass $course Course to pull grade and outcome information from.
     * @return void
     */
    abstract public function output_cover_page(stdClass $course): void;

    /**
     * Gets the number of available output rows on general pages before a new page is required.
     *
     * @see output_course()
     *
     * @return int Number of output rows on pages.
     */
    abstract protected function page_rows(): int;

    /**
     * Gets the starting y offset for course list output on the cover page.
     *
     * @return int Y offset.
     */
    abstract protected function cover_offset(): int;

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
        return $this->stringmanager->get_string($identifier, $component, $a);
    }

    /**
     * Parses a hex colour string into an object containing r, g, and b components.
     *
     * @param string $hex Input hexadecimal string.
     * @return int[] Colour array containing the parsed r, g, and b components.
     */
    protected static function parse_hex_colour(string $hex): array {
        return sscanf($hex, '#%02x%02x%02x');
    }

    /**
     * Get a colour object from a language value containing a hexadecimal colour string.
     *
     * @param string $identifier Identifier for the language string containing the hexadecimal colour string.
     * @return array Colour array containing the parsed r, g, and b components.
     */
    protected function get_colour(string $identifier): array {
        if (!isset($this->colourcache[$identifier])) {
            $this->colourcache[$identifier] = static::parse_hex_colour($this->get_string($identifier));
        }

        return $this->colourcache[$identifier];
    }

    /**
     * Calculate the y offset from row count.
     *
     * @return int The y offset.
     */
    protected function row_offset(): int {
        return $this->offsets->rowcount * $this->line_height();
    }

    /**
     * Calculate the current y offset for an output line.
     *
     * @param int $additional Extra offset to apply to the calculated offset.
     * @return int Y offset for the current output line.
     */
    protected function page_offset(int $additional = 0): int {
        $yoffset = ($this->offsets->page == 1 ? $this->cover_offset() : $this->offsets->y(static::HEADER_OFFSET));
        $yoffset += $this->row_offset();
        $yoffset += $additional;

        return $yoffset;
    }

    /**
     * Calculate an x offset for left aligned text to be output on the right side of the page.
     *
     * @param int $additional Extra offset to apply to the calculated offset.
     * @return int X offset.
     */
    protected function right_offset(int $additional = 0): int {
        $offset = static::RIGHT_OFFSET_PADDING + $additional;

        return $this->pdf->getPageWidth() - $this->pdf->getMargins()['right'] - $offset;
    }

    /**
     * Calculate the number of output rows available on the cover page given the cover offset and line height.
     *
     * @return int Output rows available for the cover page.
     */
    protected function cover_rows(): int {
        $offsetdifference = $this->cover_offset() - $this->offsets->y - static::HEADER_OFFSET;
        $rowdifference = $offsetdifference / $this->line_height();

        return $this->page_rows() - $rowdifference;
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
    public function finalise(): void {
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
     */
    protected function add_page(): void {
        // Add page.
        $this->offsets->page++;
        $this->offsets->rowcount = 0;
        $this->pdf->AddPage();

        // Draw new page elements. This must be before any other output otherwise text gets hidden.
        $this->output_page_elements();

        // Output base page content.
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
    protected function output_text_static(
        string $text,
        int $x,
        int $y,
        int $size = 10,
        string $align = 'L',
        string $style = '',
        ?string $font = null
    ): void {
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
     * @param int $xoffset Offset from the base X value.
     * @param int $yoffset Offset from the base Y value.
     * @param int $size Font size.
     * @param string $align Text alignment; L=left, C=center, R=right.
     * @param string $style Font style; ''=normal, B=bold, I=italic, U=underline.
     * @param string|null $font Output font. If null {@link OUTPUT_FONT} will be used.
     * @return void
     */
    protected function output_text(
        string $text,
        int $xoffset,
        int $yoffset,
        int $size = 10,
        string $align = 'L',
        string $style = '',
        ?string $font = null
    ): void {
        $this->output_text_static(
            $text,
            $this->offsets->x($xoffset),
            $this->offsets->y($yoffset),
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
        // Output border frame.
        certificate_print_image(
            $this->pdf,
            $this->certificate,
            CERT_IMAGE_BORDER,
            $this->offsets->borderx,
            $this->offsets->bordery,
            $this->offsets->borderw,
            $this->offsets->borderh
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

        // Output semi-transparent watermark.
        $this->pdf->SetAlpha(0.2);
        certificate_print_image(
            $this->pdf,
            $this->certificate,
            CERT_IMAGE_WATERMARK,
            $this->offsets->watermarkx,
            $this->offsets->watermarky,
            $this->offsets->watermarkw,
            $this->offsets->watermarkh
        );
        $this->pdf->SetAlpha();

        // Output regular image elements.
        certificate_print_image(
            $this->pdf,
            $this->certificate,
            CERT_IMAGE_SEAL,
            $this->offsets->sealx,
            $this->offsets->sealy,
            '',
            ''
        );
        certificate_print_image(
            $this->pdf,
            $this->certificate,
            CERT_IMAGE_SIGNATURE,
            $this->offsets->signaturex,
            $this->offsets->signaturey,
            '',
            ''
        );
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
            $this->offsets->pagenumy,
            10,
            'C'
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
    protected function output_page_numbers(): void {
        $pagecount = $this->pdf->getNumPages();

        // Don't print page count if we only have a single page.
        if ($pagecount == 1) {
            return;
        }

        for ($page = 1; $page <= $pagecount; $page++) {
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
            $this->offsets->datey,
            10,
            'R'
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
            $this->offsets->siteservicey,
            14,
            'C'
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
            $this->offsets->codey,
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
     * @param bool $displayempty When true the header and a special output will be displayed for headers with no courses.
     * @return void
     */
    public function output_courses(array $courses, string $header, string $subheader, bool $displayempty): void {
        // Handle empty course list.
        if (empty($courses)) {
            if ($displayempty) {
                $this->output_empty_course($header, $subheader);
            }

            return;
        }

        // If output is close to the end of the page create a new page for the courses.
        if (( $this->offsets->rowcount + 5 ) >= $this->current_page_rows()) {
            $this->add_page();
        }

        $this->output_course_header($header, $subheader);

        $coursevalues = array_values($courses);
        for ($index = 0; $index < count($coursevalues); $index++) {
            $course = $coursevalues[$index];
            $previouscourse = $coursevalues[$index - 1] ?? null;
            $nextcourse = $coursevalues[$index + 1] ?? null;

            $this->output_course($course, $previouscourse, $nextcourse, $header, $subheader);
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
    protected function output_empty_course(string $header, string $subheader): void {
        $this->output_course_header($header, $subheader);

        $this->apply_base_colour();

        $this->output_text_static(
            $this->get_string('nonecomplete', $header),
            $this->offsets->x,
            $this->page_offset(2),
            $this->line_font_size(3.5),
            'C'
        );
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
        $courseheader = $header;
        if ($continued) {
            $courseheader .= ' ' . $this->get_string('continued');
        }

        $this->apply_primary_colour();

        // Shift the header up 2 units to account for the size.
        $this->output_text_static(
            $courseheader,
            $this->offsets->x,
            $this->page_offset(-2),
            $this->line_font_size(4),
            'L',
            'B'
        );
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
     * @param stdClass|null $previouscourse Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $nextcourse Next course instance to be output or null if this is the last course.
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_course_completion(
        stdClass $course,
        ?stdClass $previouscourse,
        ?stdClass $nextcourse,
        string $colour = portfolio_colour::BASE
    ): void {
        $completionoutput = userdate($course->timecompleted, get_string('strftimedate'));
        if ($course->timecompleted == static::MAGIC_DATE) {
            $completionoutput = $this->get_string('magiccomplete');
        }

        $this->apply_colour($colour);

        $this->output_text_static(
            $completionoutput,
            static::right_offset(),
            $this->page_offset(),
            $this->line_font_size($this->course_font_scale())
        );

        $this->apply_base_colour();
    }

    /**
     * Output course name to the page.
     *
     * @param stdClass $course Course instance to output the name for.
     * @param stdClass|null $previouscourse Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $nextcourse Next course instance to be output or null if this is the last course.
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_course_name(
        stdClass $course,
        ?stdClass $previouscourse,
        ?stdClass $nextcourse,
        string $colour = portfolio_colour::BASE
    ): void {
        if (
            $previouscourse !== null &&
            $previouscourse->id === $course->id
        ) {
            $this->offsets->add_row();

            return;
        }

        // Automatically wrap the course name over as many lines as required as to not overlap the date.
        $breakstring = '%break%';
        $coursenamepieces = explode(
            $breakstring,
            wordwrap(
                $course->fullname,
                80,
                $breakstring
            )
        );

        $this->apply_colour($colour);

        foreach ($coursenamepieces as $coursenamepiece) {
            $this->output_text_static(
                $coursenamepiece,
                $this->offsets->x($this->offsets->rowindent),
                $this->page_offset(),
                $this->line_font_size($this->course_font_scale())
            );

            $this->offsets->add_row();
        }

        $this->apply_base_colour();
    }

    /**
     * Output course CPD to the page.
     *
     * @param stdClass $course Course instance to output CPD for.
     * @param stdClass|null $previouscourse Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $nextcourse Next course instance to be output or null if this is the last course.
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_course_cpd(
        stdClass $course,
        ?stdClass $previouscourse,
        ?stdClass $nextcourse,
        string $colour = portfolio_colour::BASE
    ): void {
        if (
            empty($course->cpd) ||
            (
                $nextcourse !== null &&
                $nextcourse->id === $course->id
            )
        ) {
            return;
        }

        $this->apply_colour($colour);

        $this->output_text_static(
            $this->get_string('coursecpd', $course->cpd),
            static::right_offset(static::RIGHT_OFFSET_CPD),
            $this->page_offset(),
            $this->line_font_size($this->course_font_scale()),
        );

        $this->apply_base_colour();

        $this->offsets->add_row();
    }

    /**
     * Output course result row to the page.
     *
     * @param stdClass $course Course instance to output results for.
     * @param stdClass|null $previouscourse Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $nextcourse Next course instance to be output or null if this is the last course.
     * @return void
     */
    protected function output_course_result(stdClass $course, ?stdClass $previouscourse, ?stdClass $nextcourse): void {
        $this->output_course_completion($course, $previouscourse, $nextcourse);
        $this->output_course_name($course, $previouscourse, $nextcourse);
        $this->output_course_cpd($course, $previouscourse, $nextcourse);
    }

    /**
     * Output a course to the page.
     *
     * Dynamically adds pages as required depending on the number of rows.
     *
     * @param stdClass $course Course instance to output.
     * @param stdClass|null $previouscourse Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $nextcourse Next course instance to be output or null if this is the last course.
     * @param string $header Header string passed to {@link output_course_header()}.
     * @param string $subheader Subheader string passed to {@link output_course_header()}.
     * @return void
     */
    protected function output_course(
        stdClass $course,
        ?stdClass $previouscourse,
        ?stdClass $nextcourse,
        string $header,
        string $subheader
    ): void {
        if (!$course->timecompleted) {
            return;
        }

        // Simple result output on the current page.
        if ($this->offsets->rowcount <= $this->current_page_rows()) {
            $this->output_course_result($course, $previouscourse, $nextcourse);

            return;
        }

        // Result output on new page.
        $this->add_page();

        $this->output_course_header($header, $subheader, true);
        $this->output_course_result($course, $previouscourse, $nextcourse);
    }

    //endregion Output
}
