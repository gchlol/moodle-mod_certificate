<?php

namespace mod_certificate\type\portfolio_qhip;

use coding_exception;
use mod_certificate\type\Portfolio\portfolio_colour;
use mod_certificate\type\Portfolio\portfolio_output_base;
use stdClass;
use TCPDF;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../Portfolio/portfolio_output_base.php');

/**
 * @property stdClass|TCPDF $pdf
 */
class portfolio_output extends portfolio_output_base {

    protected const ROOT_PATH = __DIR__;

    /**
     * Gets the number of available output rows on general pages before a new page is required.
     *
     * @see output_course()
     *
     * @return int Number of output rows on pages.
     */
    protected function page_rows(): int {
        return 47;
    }

    /**
     * Gets the starting y offset for course list output on the cover page.
     *
     * @return int Y offset.
     */
    protected function cover_offset(): int {
        return 100;
    }

    /**
     * Gets the font scale for course list output.
     *
     * @return float Font scale.
     */
    protected function course_font_scale(): float {
        return 3.25;
    }

    /**
     * Output the unique cover page containing the intro text and images.
     *
     * @param stdClass $course Course to pull grade and outcome information from.
     * @return void
     * @throws coding_exception
     */
    public function output_cover_page(stdClass $course): void {
        $this->output_cover_page_elements();

        $this->apply_primary_colour();
        $this->output_text($this->get_string('title'), 0, 8, 28, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('preuser'), 0, 33, 16, 'C');

        $this->apply_primary_colour();
        $this->output_text(fullname($this->user), 0, 43, 32, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('postuser'), 0, 61, 16, 'C');

        $this->output_page_footer();
        $this->output_page_footer_dynamic($course);
    }

    /**
     * Output the standard page footer elements.
     *
     * Can be overridden to control exactly which elements are output.
     *
     * @return void
     */
    protected function output_page_footer(): void {
        // Intentionally empty
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
        // Intentionally empty
    }

    /**
     * Output the certificate code element.
     *
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_code(string $colour = portfolio_colour::MINOR): void {
        $this->apply_colour($colour);

        $code = certificate_get_code($this->certificate, $this->record);
        if (empty($code)) {
            return;
        }

        $code_output = $this->get_string('code', $code);

        $this->output_text_static(
            $code_output,
            $this->offsets->x,
            $this->offsets->code_y,
            12, 'C'
        );

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
     */
    protected function output_course_completion(stdClass $course, ?stdClass $previous_course, ?stdClass $next_course, string $colour = portfolio_colour::BASE): void {
        $completion_output = userdate($course->timecompleted, '%d/%m/%Y');
        if ($course->timecompleted == self::MAGIC_DATE) {
            $completion_output = $this->get_string('magiccomplete');
        }

        $this->apply_colour($colour);

        $this->output_text_static(
            $completion_output,
            0,
            $this->page_offset(),
            $this->line_font_size($this->course_font_scale()),
            'R'
        );

        $this->apply_base_colour();
    }

    /**
     * Output course CPD time to the page.
     *
     * @param stdClass $course Course instance to output CPD for.
     * @param stdClass|null $previous_course Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $next_course Next course instance to be output or null if this is the last course.
     * @param string $colour Optional text colour override from {@link portfolio_colour} class constants.
     * @return void
     */
    protected function output_course_cpd(stdClass $course, ?stdClass $previous_course, ?stdClass $next_course, string $colour = portfolio_colour::BASE): void {
        $this->apply_colour($colour);

        $this->output_text_static(
            "CPD: $course->cpd minutes",
            0, $this->page_offset(),
            $this->line_font_size($this->course_font_scale()),
            'R'
        );

        $this->apply_base_colour();

        $this->offsets->add_row();
    }

    /**
     * Output course result row to the page.
     *
     * @param stdClass $course Course instance to output results for.
     * @param stdClass|null $previous_course Previous course instance that was output or null if this is the first course.
     * @param stdClass|null $next_course Next course instance to be output or null if this is the last course.
     * @return void
     */
    protected function output_course_result(stdClass $course, ?stdClass $previous_course, ?stdClass $next_course): void {
        if (
            $previous_course !== null &&
            $previous_course->fullname === $course->fullname
        ) {
            $course->fullname = '';
        }

        $this->output_course_completion($course, $previous_course, $next_course);
        $this->output_course_name($course, $previous_course, $next_course);

        if (
            $next_course === null ||
            $course->fullname !== $next_course->fullname
        ) {
            if ($course->cpd) {
                $this->output_course_cpd($course, $previous_course, $next_course);
            }

            $this->offsets->add_row();
        }
    }
}