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

namespace mod_certificate\type\portfolio_temp;

use mod_certificate\type\Portfolio\portfolio_output_base;
use stdClass;
use TCPDF;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../Portfolio/portfolio_output_base.php');

/**
 * Portfolio output class.
 *
 * @package    mod_certificate
 * @copyright  2022 Gold Coast Health
 * @author     Nicholas Lambell
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @property stdClass|TCPDF $pdf
 */
class portfolio_output extends portfolio_output_base {

    /**
     * Root path for the portfolio implementation.
     */
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
        return 90;
    }

    /**
     * Output the unique cover page containing the intro text and images.
     *
     * @param stdClass $course Course to pull grade and outcome information from.
     * @return void
     */
    public function output_cover_page(stdClass $course): void {
        $this->output_cover_page_elements();

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
}
