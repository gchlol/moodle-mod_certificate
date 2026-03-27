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

use TCPDF;

/**
 * Portfolio offsets data container.
 *
 * @package    mod_certificate
 * @copyright  2022 Gold Coast Health
 * @author     Nicholas Lambell
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class portfolio_offsets {
    // Base.
    public int $x;
    public int $y;

    // Counters.
    public int $page;
    public int $row_count;

    // Options.
    public int $row_indent;

    // Offsets.
    public int $border_x;
    public int $border_y;
    public int $border_w;
    public int $border_h;
    public int $code_y;
    public int $date_y;
    public int $page_num_y;
    public int $seal_x;
    public int $seal_y;
    public int $signature_x;
    public int $signature_y;
    public int $site_service_y;
    public int $watermark_x;
    public int $watermark_y;
    public int $watermark_w;
    public int $watermark_h;

    /**
     * Constructor
     */
    public function __construct() {
        $this->page = 1;
        $this->row_count = 1;

        $this->row_indent = 0;

        $this->border_x = 0;
        $this->border_y = 0;
    }

    /**
     * Load dimensions from a PDF instance into relevant offset fields.
     *
     * @param TCPDF $pdf PDF instance to load from.
     * @return void
     */
    public function load_pdf_dimensions(TCPDF $pdf): void {
        $this->border_w = $pdf->getPageWidth();
        $this->border_h = $pdf->getPageHeight();
    }

    /**
     * Returns the x baseline with the given offset applied.
     *
     * @param int $offset Integer to offset the value by.
     * @return int Offset value.
     */
    public function x(int $offset): int {
        return $this->x + $offset;
    }

    /**
     * Returns the y baseline with the given offset applied.
     *
     * @param int $offset Integer to offset the value by.
     * @return int Offset value.
     */
    public function y(int $offset): int {
        return $this->y + $offset;
    }

    /**
     * Add a single row to the row count.
     *
     * @return void
     */
    public function add_row(): void {
        $this->add_rows(1);
    }

    /**
     * Add the provided number of rows to the row count.
     *
     * @param int $rows Rows to add.
     * @return void
     */
    public function add_rows(int $rows): void {
        $this->row_count += $rows;
    }
}
