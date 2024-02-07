<?php

namespace mod_certificate\type\portfolio_wm;

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

    protected const HEADER_OFFSET = 15;

    /**
     * @inheritDoc
     */
    protected function get_identifier(): string {
        return 'portfolio_wm';
    }

    /**
     * @inheritDoc
     */
    protected function page_rows(): int {
        return 40;
    }

    /**
     * @inheritDoc
     */
    protected function cover_offset(): int {
        return 120;
    }

    /**
     * @inheritDoc
     */
    public function output_cover_page(stdClass $course): void {
        $this->output_cover_page_elements();

        $this->apply_minor_colour();
        $this->output_text($this->get_string('preuser'), 0, 0, 16, 'C');

        $this->apply_primary_colour();
        $this->output_text(fullname($this->user), 0, 8, 32, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('postuser'), 0, 24, 16, 'C');

        $this->output_page_footer();
        $this->output_page_footer_dynamic($course);
    }

    /**
     * @inheritDoc
     */
    protected function output_site_service(string $colour = portfolio_colour::PRIMARY): void {
        // Intentionally do nothing.
    }

    /**
     * @inheritDoc
     */
    protected function output_page_header(string $colour = portfolio_colour::PRIMARY): void {
        $this->apply_colour($colour);

        $this->output_text($this->get_string('title_contfor', fullname($this->user)), 0, 0, 12, 'C', 'B');

        $this->apply_base_colour();
    }

    /**
     * @inheritDoc
     */
    protected function output_printed_date(string $colour = portfolio_colour::MINOR): void {
        $this->apply_colour($colour);

        $this->output_text_static(
            $this->get_string('printedon', date('j F Y')),
            $this->offsets->x,
            $this->offsets->date_y,
            10, 'C'
        );

        $this->apply_base_colour();
    }
}