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
    /**
     * @var int Baseline x offset.
     */
    public int $x;

    /**
     * @var int Baseline y offset.
     */
    public int $y;

    // Counters.
    /**
     * @var int Current page counter.
     */
    public int $page;

    /**
     * @var int Current row counter.
     */
    public int $rowcount;

    // Options.
    /**
     * @var int Indent to apply to non-header output rows.
     */
    public int $rowindent;

    // Offsets.
    /**
     * @var int Border x position.
     */
    public int $borderx;

    /**
     * @var int Border y position.
     */
    public int $bordery;

    /**
     * @var int Border width.
     */
    public int $borderw;

    /**
     * @var int Border height.
     */
    public int $borderh;

    /**
     * @var int Code y position.
     */
    public int $codey;

    /**
     * @var int Date y position.
     */
    public int $datey;

    /**
     * @var int Page number y position.
     */
    public int $pagenumy;

    /**
     * @var int Seal x position.
     */
    public int $sealx;

    /**
     * @var int Seal y position.
     */
    public int $sealy;

    /**
     * @var int Signature x position.
     */
    public int $signaturex;

    /**
     * @var int Signature y position.
     */
    public int $signaturey;

    /**
     * @var int Site service y position.
     */
    public int $siteservicey;

    /**
     * @var int Watermark x position.
     */
    public int $watermarkx;

    /**
     * @var int Watermark y position.
     */
    public int $watermarky;

    /**
     * @var int Watermark width.
     */
    public int $watermarkw;

    /**
     * @var int Watermark height.
     */
    public int $watermarkh;

    /**
     * Constructor
     */
    public function __construct() {
        $this->page = 1;
        $this->rowcount = 1;

        $this->rowindent = 0;

        $this->borderx = 0;
        $this->bordery = 0;
    }

    /**
     * Load dimensions from a PDF instance into relevant offset fields.
     *
     * @param TCPDF $pdf PDF instance to load from.
     * @return void
     */
    public function load_pdf_dimensions(TCPDF $pdf): void {
        $this->borderw = $pdf->getPageWidth();
        $this->borderh = $pdf->getPageHeight();
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
        $this->rowcount += $rows;
    }
}
