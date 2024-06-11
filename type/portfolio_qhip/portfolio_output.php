<?php

namespace mod_certificate\type\portfolio_qhip;

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

    protected function page_rows(): int {
        return 47;
    }

    protected function cover_offset(): int {
        return 90;
    }

    public function output_cover_page(stdClass $course): void {
        $this->output_cover_page_elements();

        // Add text
        $this->apply_primary_colour();
        $this->output_text($this->get_string('site'), 0, 0, 37.5, 'C', 'B');

        $this->apply_secondary_colour();
        $this->output_text($this->get_string('title'), 0, 15, 28, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('preuser'), 0, 40, 16, 'C');

        $this->apply_primary_colour();
        $this->output_text(fullname($this->user), 0, 48, 32, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('postuser'), 0, 64, 16, 'C');

        $this->output_page_footer();
        $this->output_page_footer_dynamic($course);
    }

    /**
     * @inheritDoc
     */
    protected function output_course_result(stdClass $course) {
        // Blank course name if it's the same as the previous one.
        static $previous_course_name;
        if ($previous_course_name === $course->fullname) {
            $course->fullname = '';

        } else {
            $previous_course_name = $course->fullname;

            if ($previous_course_name !== null) {
                $this->offsets->add_row();
            }
        }

        parent::output_course_result($course);
    }
}