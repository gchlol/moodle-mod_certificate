<?php

class gfg_pdf extends TCPDF {

    private ?stdClass $action = null;
    private int $action_number;
    private stdClass $ciap;
    private stdClass $plan;
    private ?stdClass $previous_action = null;

    private ?string $division = null;
    private bool $is_summary_page = true;

    public function __construct(stdClass $ciap, stdClass $plan, $orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $thisa = false) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $thisa);

        $this->ciap = $ciap;
        $this->plan = $plan;

        $this->populate_owner_info();
    }

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
    public function Header() {
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

    public function Footer() {
        $footer_start = $this->getPageHeight() - $this->getFooterMargin();
        $page_number_text = $this->get_page_number_text();
        $page_number_padding = str_repeat(' ', strlen($page_number_text));

        $this->SetTextColor(0, 0, 0);
        $this->setFont('Helvetica', 'B', 11);

        $this->Text(0, $footer_start, $this->ciap->name . ' - Summary', 0 , false, true, 0, 0 , 'C');
        $this->Text(0, $footer_start + 5, $page_number_padding . $this->plan->idnumber . ' ' . $this->plan->name . "  -  Page $page_number_text", 0 , false, true, 0, 0 , 'C');
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

    private function print_summary_header(): void {
        $this->SetTextColor(255, 255, 255);

        certificate_print_text($this,
            $this->lMargin + 95, $this->tMargin,
            'l', 'Helvetica', 'B', 18,
            $this->plan->idnumber . ' ' . $this->plan->name
        );


        if ($this->division) {
            certificate_print_text($this,
                $this->lMargin + 95, $this->tMargin + 10,
                'l', 'Helvetica', '', 12,
                $this->division
            );
        }

        if (isset($plan->custom_fields->mergein)) {
            certificate_print_text($this,
                $this->lMargin + 95, $this->tMargin + 17,
                'l', 'Helvetica', '', 12,
                $plan->custom_fields->mergein
            );
        }

        certificate_print_text($this,
            $this->lMargin + 95, $this->tMargin + 25,
            'l', 'Helvetica', 'B', 18,
            $this->ciap->name . ' - Summary'
        );
    }

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

    function print_action_value_logos(string $values): void {
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

    private function get_page_number_text(): string {
        $w_page = isset($this->l['w_page']) ? $this->l['w_page'] . ' ' : '';

        if (!empty($this->pagegroups)) {
            return $w_page . $this->getPageNumGroupAlias();
        }

        return $w_page . $this->getAliasNumPage();
    }
}