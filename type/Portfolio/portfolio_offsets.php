<?php

namespace mod_certificate\type\Portfolio;

use TCPDF;

class portfolio_offsets {
    // Base
    public $x;
    public $y;

    // Counters
    public $page;
    public $row_count;

    // Options
    public $row_indent;

    // Offsets
    public $border_x;
    public $border_y;
    public $border_w;
    public $border_h;
    public $code_y;
    public $date_y;
    public $page_num_y;
    public $seal_x;
    public $seal_y;
    public $signature_x;
    public $signature_y;
    public $site_service_y;
    public $watermark_x;
    public $watermark_y;
    public $watermark_w;
    public $watermark_h;

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
    public function load_pdf_dimensions(TCPDF $pdf) {
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