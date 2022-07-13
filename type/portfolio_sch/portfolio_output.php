<?php

namespace mod_certificate\type\portfolio_sch;

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
        return 'portfolio_sch';
    }

    protected function page_rows(): int {
        return 44;
    }

    protected function cover_offset(): int {
        return 120;
    }

    public function output_cover_page(stdClass $course): void {
        $this->output_cover_page_elements();

        // Add text
        $this->apply_primary_colour();
        $this->output_text($this->get_string('site'), 0, 8, 30, 'C', 'B');
        $this->output_text($this->get_string('service'), 0, 22, 30, 'C', 'B');
        $this->output_text($this->get_string('title'), 0, 36, 30, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('preuser'), 0, 60, 16, 'C');

        $this->apply_primary_colour();
        $this->output_text(fullname($this->user), 0, 68, 32, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('postuser'), 0, 84, 16, 'C');

        $this->output_page_footer();
        $this->output_page_footer_dynamic($course);
    }

    protected function output_page_footer(): void {
        $this->output_printed_date();
    }
}