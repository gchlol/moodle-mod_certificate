<?php

namespace mod_certificate\type\portfolio_nw;

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

    protected function page_rows(): int {
        return 47;
    }

    protected function cover_offset(): int {
        return 100;
    }

    public function output_cover_page(stdClass $course): void {
        $this->output_cover_page_elements();

        // Add text
        $this->apply_primary_colour();
        $this->output_text($this->get_string('title'), 0, 5, 42, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('preuser'), 0, 25, 16, 'C');

        $this->apply_primary_colour();
        $this->output_text(fullname($this->user), 0, 32, 32, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('postuser'), 0, 47, 16, 'C');

        $this->output_page_footer();
        $this->output_page_footer_dynamic($course);
    }

    protected function output_page_header(string $colour = portfolio_colour::PRIMARY): void {
        $this->apply_colour($colour);

        $this->output_text($this->get_string('title_contfor', fullname($this->user)), 0, 6, 12, 'C', 'B');

        $this->apply_base_colour();
    }
}