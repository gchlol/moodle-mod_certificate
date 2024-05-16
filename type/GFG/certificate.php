<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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

/**
 * A4_non_embedded certificate type
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Majorly modified to allow certificate
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}
require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/completionlib.php");
require_once(__DIR__ . '/gfg_pdf.php');

$plan_id = required_param('ciap', PARAM_INT);
if ($plan_id == '999999') {
    output_data();
}

$plan = $DB->get_record('ciap_plans', [ 'id' => $plan_id ]);
$ciap = $DB->get_record('ciap', [ 'id' => $plan->ciapid ]);
$actions = $DB->get_records('ciap_actions', [ 'planid' => $plan->id ]);

$plan->custom_fields = get_custom_field_values('plans', $ciap->id, $plan->id);
foreach ($actions as $action) {
    $action->custom_fields = get_custom_field_values('actions', $ciap->id, $action->id);
}

$x = 10;
$y = 10;

$pdf = new gfg_pdf($ciap, $plan, $certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetTitle("$plan->name - Summary");
$pdf->SetAutoPageBreak(true, 25);
$pdf->setMargins($y, $x + 25);
$pdf->setFooterMargin(25);

$pdf->AddPage();

// Define variables
$action_logo_size = 20;
$action_logo_spacing = 5;
$page_center = $pdf->getPageWidth() / 2;


$actionno = 1;
$action_offset = 0;
foreach ($actions as $action) {
    if ($action_offset == 5) {
        $action_offset = 0;

        $pdf->AddPage();
    }

    if ($action_offset == 0) {
        $pdf->SetTextColor(16, 75, 118);

        certificate_print_text($pdf, $x + 5, $y + 50, 'l', 'Helvetica', 'B', 14, 'What is the action we have committed to?');
        certificate_print_text($pdf, $x + 180, $y + 50, 'l', 'Helvetica', 'B', 14, 'Value:');
        certificate_print_text($pdf, $x + 230, $y + 50, 'l', 'Helvetica', 'B', 14, 'Action status:');
    }

    [
        $actionhead,
        $actionbod,
    ] = get_action_content($action);
    if ($actionhead == NULL) {
        $actionhead = $actionbod;
        if (strlen($actionbod) > 140) {
            $actionhead = substr($actionbod, 0, 140) . '...';
        }
    }

    $x_offset = $y + 60 + ( $action_offset * 20 );

    $pdf->setTextColor(0, 0, 0);

    $action_number_width = $pdf->GetStringWidth($actionno, 'Helvetica', '', 14);
    $action_number_offset = round($action_number_width / 2);

    certificate_print_text($pdf, $x - $action_number_offset, $x_offset, 'l', 'Helvetica', '', 14, "$actionno.");
    certificate_print_text($pdf, $x + 5, $x_offset, 'l', 'Helvetica', '', 14, $actionhead, 160);

    if (isset($action->custom_fields->response)) {
        $response = str_replace(', ', '<br/>', $action->custom_fields->response);
        certificate_print_text($pdf, $x + 180, $x_offset, 'l', 'Helvetica', '', 14, $response);
    }

    $actionid = $action->id;
    $updates = $DB->get_records_sql(
        "
            SELECT  *
            FROM    {ciap_updates} cu
            WHERE   cu.actionid = :action
            ORDER BY cu.periodid
        ",
        [ 'action' => $actionid ]
    );

    $status = 'No update provided';
    $due = '';
    foreach ($updates as $update) {
        $perioddate = $DB->get_record('ciap_periods', [ 'id' => $update->periodid ]);
        switch ($update->status) {
            case '0':
                $status = 'Not yet started';
                $due_date = date('d/m/y', $action->duedate);
                $due = "(due $due_date)";

                break;
            case '1':
                $status = 'In progress';
                $due_date = date('d/m/y', $action->duedate);
                $due = "(due $due_date)";

                break;
            case '2':
                $status = 'Complete';

                break;
            case '3':
                $status = 'No longer required';

                break;
        }
    }

    certificate_print_text($pdf, $x + 230, $x_offset, 'l', 'Helvetica', '', 14, $status);
    certificate_print_text($pdf, $x + 230, $x_offset + 6, 'l', 'Helvetica', '', 11, $due);

    $actionno++;
    $action_offset++;
}

$pdf->set_is_summary_page(false);

$actionno = 1;
foreach ($actions as $action) {
    $pdf->set_action($action, $actionno);
    $pdf->AddPage();

    $actionid = $action->id;

    $updates = $DB->get_records_sql(
        "
            SELECT  *
            FROM    {ciap_updates} cu
            WHERE   cu.actionid = :action 
            ORDER BY cu.periodid
        ",
        [ 'action' => $actionid ]
    );

    if (!$updates) {
        certificate_print_text($pdf, $x + 10, $y + 70, 'l', 'Helvetica', 'B', 16, 'An update has not been provided for this action');
    }

    [
        $actionhead,
        $actionbody,
    ] = get_action_content($action);

    if (strlen($actionhead) > 85) {
        $actionhead = substr($actionhead, 0, 85) . '...';
    }

    $pdf->SetTextColor(0, 0, 0);
    certificate_print_text($pdf, $x + 10, $y + 27, 'l', 'Helvetica', 'i', 18, $actionhead, 240);

    $long_description = is_long_content($pdf, $action->description);
    if ($long_description) {
        $description_output = $actionbody;
        if (strlen($description_output) > 400) {
            $description_output = substr($description_output, 0, 400);
        }

        certificate_print_text($pdf, $x + 10, $y + 37, 'l', 'Helvetica', 'i', 12, "$description_output...", 240);

        $pdf->SetTextColor(187, 111, 122);
        certificate_print_text($pdf, $x + 170, $y + 53, 'l', 'Helvetica', 'B', 12, 'Further details available over the page', 240);

    } else {
        certificate_print_text($pdf, $x + 10, $y + 37, 'l', 'Helvetica', 'i', 12, $actionbody, 240);
    }

    if (isset($action->custom_fields->owner)) {
        $pdf->SetTextColor(16, 75, 118);
        certificate_print_text($pdf, $x + 10, $y + 60, 'l', 'Helvetica', 'B', 12, 'Who is responsible for this action?');
        $pdf->SetTextColor(0, 0, 0);
        certificate_print_text($pdf, $x + 83, $y + 60, 'l', 'Helvetica', '', 12, $action->custom_fields->owner, 75);
    }

    $pdf->SetTextColor(16, 75, 118);
    certificate_print_text($pdf, $x + 160, $y + 60, 'l', 'Helvetica', 'B', 12, 'When is this action due?');
    $pdf->SetTextColor(0, 0, 0);
    certificate_print_text($pdf, $x + 212, $y + 60, 'l', 'Helvetica', '', 12, date('j F Y', $action->duedate));

    $update_offset = 0;
    $complete = false;
    foreach ($updates as $update) {
        $perioddate = $DB->get_record('ciap_periods', [ 'id' => $update->periodid ]);
        switch ($update->status) {
            case '0':
                $ans = 'Not yet started';

                break;
            case '1':
                $ans = 'In progress';

                break;
            case '2':
                $ans = 'Complete';
                $complete = true;

                break;
            case '3':
                $ans = 'No longer required';

                break;
        }

        if ($update->duedate) {
            $due_date = date('j F Y', $update->duedate);
            $ans .= " ($due_date)";
        }

        $x_offset = $y + 70 + ($update_offset * 30);
        $update_number = $update_offset + 1;
        $end_date = date('F Y', $perioddate->enddate);

        certificate_print_text($pdf, $x + 10, $x_offset, 'l', 'Helvetica', '', 12, "<strong>Update $update_number</strong> ($end_date)");
        certificate_print_text($pdf, $x + 160, $x_offset, 'l', 'Helvetica', '', 12, "<strong>Status:</strong> $ans");
        certificate_print_text($pdf, $x + 10, $x_offset + 10, 'l', 'Helvetica', '', 12, $update->description);

        $update_offset++;
    }

    if ($complete) {
        $pdf->SetTextColor(187, 111, 122);
        certificate_print_text($pdf, $x + 10, $y + 160, 'l', 'Helvetica', 'B', 16, 'Congratulations on completing this action - make sure you celebrate this win with your team!');
        $pdf->SetTextColor(0, 0, 0);
    }

    if ($long_description) {
        $pdf->AddPage();

        $pdf->SetTextColor(0, 0, 0);
        certificate_print_text($pdf, $x + 10, $y + 35, 'l', 'Helvetica', 'B', 12, $action->name ?? $actionhead, 240);
        certificate_print_text($pdf, $x + 10, $y + 50, '', 'Helvetica', '', 12, $action->description);
    }

    $pdf->lastPage();

    $actionno++;
}

/**
 * Get distinct heading and body content from an action.
 *
 * @param stdClass $action Action
 * @return string[] Array containing heading then body. Heading may be null where it can't be determined.
 */
function get_action_content(stdClass $action): array {
    $repl = [ " </p>", " /n", "</p>", "/n" ];
    $repl2 = [ '..', '.  .', '.  .', '. .', '.  .' ];

    $text1 = str_replace($repl, '.', $action->description);
    $text2 = preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $text1);
    $text3 = strip_tags($text2);
    $text4 = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", ' ', $text3)));
    $text5 = str_replace($repl2, '. ', $text4);

    // Distinct name/title and description/body.
    if (!empty($action->name)) {
        return [
            $action->name,
            $text5,
        ];
    }

    // Try to determine heading from description.
    $head = null;
    $body = $text5;

    $pos = strpos($text5, '.');
    if (
        $pos < 80 &&
        $pos > 5
    ) {
        $head = substr($text5, 0, $pos + 1);
        $body = substr($text5, $pos + 1);
    }

    return [
        $head,
        $body,
    ];
}

/**
 * Directly output all CIAP action data.
 *
 * @return void
 * @throws dml_exception
 */
function output_data(): void {
    global $DB;

    $actions = $DB->get_records('ciap_actions');
    foreach ($actions as $action) {
        [ $head, $body ] = get_action_content($action);

        echo "
            <strong>Action: </strong> $action->id<br>
            <strong>Plan: </strong> $action->planid<br>
            <strong>Title: </strong> $head<br>
            <strong>Stripped:</strong><br>
            <div style='border: 1px solid black'>$body</div><br>
            <strong>Content:</strong><br>
            <pre style='border: 1px solid black'>$action->description</pre><br>
            <br><br>
            <hr>
        ";
    }

    exit();
}

/**
 * Get custom field values for the defined CIAP item.
 *
 * @param string $area Custom field data area. e.g. 'plans', 'actions'.
 * @param int $ciap_id ID of the parent CIAP instance.
 * @param int $item_id ID of the item relevant to the area. e.g. Plan ID.
 * @return stdClass Custom field value object where key is the field shortname and value is the exported field value.
 * @throws moodle_exception
 */
function get_custom_field_values(string $area, int $ciap_id, int $item_id): stdClass {
    $handler = \core_customfield\handler::get_handler('mod_ciap', $area, $ciap_id);

    $custom_field_values = [];
    $custom_fields = $handler->get_instance_data($item_id);
    foreach ($custom_fields as $custom_field) {
        $field = $custom_field->get_field();
        $field_name = $field->get('shortname');

        $custom_field_values[$field_name] = $custom_field->export_value();
    }

    return (object)$custom_field_values;
}

/**
 * Determine whether the given content is classified as long content.
 *
 * @param TCPDF $pdf PDF instance being written to.
 * @param string $content Content to check.
 * @return bool Whether content is long.
 */
function is_long_content(TCPDF $pdf, string $content): bool {
    // Remove trailing empty paragraph tags
    $trimmed_content = preg_replace('/(<p dir="ltr" style="text-align: left;"><br><\/p>)+$/', '', $content);

    $paragraph_count = substr_count($trimmed_content, '<p');
    if ($paragraph_count > 1) {
        return true;
    }

    $contains_big_tags = preg_match('/<div>|<img>|<li>|<table>/i', $trimmed_content);
    if ($contains_big_tags) {
        return true;
    }

    $content_lines = $pdf->getNumLines($trimmed_content, 200);
    if ($content_lines > 3) {
        return true;
    }

    return false;
}
