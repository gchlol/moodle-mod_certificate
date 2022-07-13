<?php

namespace mod_certificate\type\portfolio_gch;

use mod_certificate\type\Portfolio\portfolio_colour;
use mod_certificate\type\Portfolio\portfolio_output_base;
use stdClass;
use TCPDF;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/certificate/type/Portfolio/portfolio_output_base.php');

/**
 * @property stdClass|TCPDF $pdf
 */
class portfolio_output extends portfolio_output_base {

    protected function get_identifier(): string {
        return 'portfolio_gch';
    }

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

    protected function output_printed_date(string $colour = portfolio_colour::SECONDARY): void {
        parent::output_printed_date($colour);
    }
}