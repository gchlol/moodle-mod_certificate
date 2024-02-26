<?php

namespace mod_certificate\type\portfolio_dd;

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
        return 45;
    }

    protected function cover_offset(): int {
        return 102;
    }

    public function output_cover_page(stdClass $course): void {
        $this->output_cover_page_elements();

        // Add text
        $this->apply_primary_colour();
        $this->output_text($this->get_string('siteservice'), 0, 0, 20, 'C', 'B');
        $this->output_text($this->get_string('title'), 0, 8, 56, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('preuser'), 0, 30, 16, 'C');

        $this->apply_secondary_colour();
        $this->output_text(fullname($this->user), 0, 40, 32, 'C', 'B');

        $this->apply_minor_colour();
        $this->output_text($this->get_string('postuser'), 0, 58, 16, 'C');

        $this->output_page_footer();
        $this->output_page_footer_dynamic($course);
    }

    protected function output_page_elements(): void {
        $pix_root = __DIR__ . '/pix';

        // Draw header image partially covered by a white rectangle
        $this->pdf->Image("$pix_root/header-background.png", 0, -50, 210, 0, '', '', 'M');
        $this->pdf->Rect(0, 30, 220, 60, 'F', [], [255, 255, 255]);

        // Draw purple footer rectangle
        $this->pdf->Rect(0, 270, 220, 60, 'F', [], $this->get_colour(portfolio_colour::SECONDARY));

        // Draw page logos
        $this->pdf->Image("$pix_root/darlingdownshealth.png", 10, 20, 80, 0, '', '', 'M');
        $this->pdf->Image("$pix_root/qg-coa.png", 120, 277, 80, 0, '', '', 'M');

        parent::output_page_elements();
    }

    protected function output_site_service(string $colour = portfolio_colour::PRIMARY): void {
        $this->apply_colour($colour);

        $this->output_text_static(
            $this->get_string('siteservicelabel'),
            $this->offsets->x,
            $this->offsets->site_service_y,
            12, 'C', 'B'
        );

        $this->output_text_static(
            $this->get_string('siteservice'),
            $this->offsets->x,
            $this->offsets->site_service_y + 5,
            16, 'C', 'B'
        );

        $this->apply_base_colour();
    }

    protected function output_page_number(string $colour = portfolio_colour::BASE): void {
        parent::output_page_number($colour);
    }

    protected function output_printed_date(string $colour = portfolio_colour::BASE): void {
        parent::output_printed_date($colour);
    }
}