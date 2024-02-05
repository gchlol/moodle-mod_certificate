<?php

class gfg_pdf extends TCPDF {

    private ?stdClass $action = null;
    private int $action_number;
    private stdClass $ciap;
    private stdClass $plan;
    private ?stdClass $previous_action = null;

    private ?string $division = null;
    private bool $is_summary_page = true;

    /**
     * @param stdClass $ciap CIAP instance data.
     * @param stdClass $plan Target CIAP plan.
     * @param string $orientation page orientation. Possible values are (case insensitive):<ul><li>P or Portrait (default)</li><li>L or Landscape</li><li>'' (empty string) for automatic orientation</li></ul>
     * @param string $unit User measure unit. Possible values are:<ul><li>pt: point</li><li>mm: millimeter (default)</li><li>cm: centimeter</li><li>in: inch</li></ul><br />A point equals 1/72 of inch, that is to say about 0.35 mm (an inch being 2.54 cm). This is a very common unit in typography; font sizes are expressed in that unit.
     * @param mixed $format The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters specified at setPageFormat().
     * @param boolean $unicode TRUE means that the input text is unicode (default = true)
     * @param string $encoding Charset encoding (used only when converting back html entities); default is UTF-8.
     * @param boolean $diskcache DEPRECATED FEATURE
     * @param false|integer $thisa If not false, set the document to PDF/A mode and the good version (1 or 3).
     * @throws dml_exception
     */
    public function __construct(stdClass $ciap, stdClass $plan, $orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $thisa = false) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $thisa);

        $this->ciap = $ciap;
        $this->plan = $plan;

        $this->populate_owner_info();
    }

    /**
     * Populate information relevant to the target plan's owner for use in output.
     *
     * @return void
     * @throws dml_exception
     */
    private function populate_owner_info(): void {
        global $DB;

        $division_field = $DB->get_record('user_info_field', [ 'shortname' => 'level8' ]);
        if (!$division_field) {
            return;
        }

        $plan_owner = $DB->get_record('ciap_owners', [ 'planid' => $this->plan->id ], '*', IGNORE_MULTIPLE);
        if ($plan_owner->type == 0) {
            $user_id = $plan_owner->userid;

        } else {
            $position_field = $DB->get_record('user_info_field', [ 'shortname' => 'posid' ]);
            if (!$position_field) {
                return;
            }

            $user_id = $DB->get_field_sql(
                "
                SELECT  userid
                FROM    {user_info_data}
                WHERE   fieldid = :field and
                        " . $DB->sql_compare_text('data') . " = " . $DB->sql_compare_text(':data')
                ,
                [
                    'field' => $position_field->id,
                    'data' => $plan_owner->value,
                ],
                IGNORE_MULTIPLE
            );
        }

        $division_data = $DB->get_record(
            'user_info_data',
            [
                'fieldid' => $division_field->id,
                'userid' => $user_id,
            ]
        );

        if ($division_data) {
            $this->division = $division_data->data;
        }
    }

    /**
     * Set the current action and associated action number.
     *
     * @param stdClass|null $action Current action.
     * @param int $action_number Action number.
     * @return void
     */
    public function set_action(?stdClass $action, int $action_number = 0): void {
        $this->action = $action;
        $this->action_number = $action_number;
    }

    /**
     * Set whether following pages are summary or details pages.
     *
     * @param bool $is_summary_page Is summary page.
     * @return void
     */
    public function set_is_summary_page(bool $is_summary_page): void {
        $this->is_summary_page = $is_summary_page;
    }

    /**
     * @inheritDoc
     */
    public function Header(): void {
        $auto_page_break = $this->getAutoPageBreak();
        $break_margin = $this->getBreakMargin();
        $this->setAutoPageBreak(false);

        $this->apply_background();
        if ($this->is_summary_page) {
            $this->print_summary_header();
        }

        if ($this->action) {
            $this->print_action_header();
        }

        $this->setAutoPageBreak($auto_page_break, $break_margin);
        $this->setPageMark();
    }

    /**
     * @inheritDoc
     */
    public function Footer(): void {
        $footer_start = $this->getPageHeight() - $this->getFooterMargin();
        $page_number = $this->PageNo();
        $page_number_padding = str_repeat(' ', strlen($page_number));

        $this->SetTextColor(0, 0, 0);
        $this->setFont('Helvetica', 'B', 11);

        $this->Text(0, $footer_start, $this->ciap->name . ' - Summary', 0 , false, true, 0, 0 , 'C');
        $this->Text(0, $footer_start + 5, $page_number_padding . $this->plan->idnumber . ' ' . $this->plan->name . "  -  Page $page_number", 0 , false, true, 0, 0 , 'C');
        $this->Text(0, $footer_start + 10, 'Printed on ' . date('j F Y', time()), 0 , false, true, 0, 0 , 'C');
    }

    /**
     * Apply dynamic page background image.
     *
     * @return void
     */
    private function apply_background(): void {
        $background_image = $this->is_summary_page ? 'CIAP P1' : 'CIAP P2';
        $background_path = __DIR__ . "/images/$background_image.jpg";

        $this->Image($background_path, 0, 0, $this->getPageWidth(), $this->getPageHeight());
    }

    /**
     * Print summary page specific header.
     *
     * @return void
     */
    private function print_summary_header(): void {
        $this->SetTextColor(255, 255, 255);

        certificate_print_text($this,
            $this->lMargin + 95, 10,
            'l', 'Helvetica', 'B', 18,
            $this->plan->idnumber . ' ' . $this->plan->name
        );

        if ($this->division) {
            certificate_print_text($this,
                $this->lMargin + 95, 20,
                'l', 'Helvetica', '', 12,
                $this->division
            );
        }

        if (isset($this->plan->custom_fields->mergein)) {
            certificate_print_text($this,
                $this->lMargin + 95, 27,
                'l', 'Helvetica', '', 12,
                $this->plan->custom_fields->mergein
            );
        }

        certificate_print_text($this,
            $this->lMargin + 95, 35,
            'l', 'Helvetica', 'B', 18,
            $this->ciap->name . ' - Summary'
        );
    }

    /**
     * Print action specific header.
     *
     * @return void
     */
    private function print_action_header(): void {
        $this->SetTextColor(16, 75, 118);
        certificate_print_text($this, $this->lMargin + 10, 5, 'l', 'Helvetica', 'B', 37, "Action $this->action_number");

        if ($this->action === $this->previous_action) {
            $this->SetTextColor(187, 111, 122);
            certificate_print_text($this, $this->lMargin + 10, 20, 'l', 'Helvetica', 'B', 24, 'Appendix');
        }

        if (isset($this->action->custom_fields->response)) {
            $this->setTextColor(0, 0, 0);
            certificate_print_text($this, $this->lMargin, 23, 'C', 'Helvetica', '', 9, 'This action promotes the GCH value of');
            certificate_print_text($this, $this->lMargin, 31, 'C', 'Helvetica', '', 9, 'within our work unit');

            $this->SetTextColor(16, 75, 118);
            certificate_print_text($this, $this->lMargin, 27, 'C', 'Helvetica', 'B', 9, $this->action->custom_fields->response);

            $this->print_action_value_logos($this->action->custom_fields->response);
        }

        $this->previous_action = $this->action;
    }

    /**
     * Print dynamic action header logos.
     *
     * @param string $values Comma delimited list of action values. Values should correspond to images in the `images` subdirectory.
     * @return void
     */
    private function print_action_value_logos(string $values): void {
        $action_logo_size = 20;
        $action_logo_spacing = 5;
        $page_center = $this->getPageWidth() / 2;

        $responses = explode(', ', $values);
        $response_count = count($responses);
        $logos_size = ( $action_logo_size * $response_count ) + ( $action_logo_spacing * ( $response_count - 1 ));
        $logos_start = $page_center - ( $logos_size / 2 );

        foreach ($responses as $index => $response) {
            $logo_path = __DIR__ . "/images/$response.png";
            $logo_x = $logos_start + ( $action_logo_size * $index ) + ( $action_logo_spacing * $index );

            $this->Image($logo_path, $logo_x, 2, $action_logo_size, $action_logo_size);
        }
    }
}