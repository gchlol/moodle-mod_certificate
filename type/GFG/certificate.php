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

$ciapid = optional_param('ciap', 0, PARAM_INT);

if ($ciapid == '999999') {
    outputdata();
}

$plan = $DB->get_record('ciap_plans', [ 'id' => $ciapid ]);
$ciap = $DB->get_record('ciap', [ 'id' => $plan->ciapid ]);
$actions = $DB->get_records('ciap_actions', [ 'planid' => $plan->id ]);

$plan->custom_fields = get_custom_field_values('plans', $ciap->id, $plan->id);
foreach ($actions as $action) {
    $action->custom_fields = get_custom_field_values('actions', $ciap->id, $action->id);
}

$division_field = $DB->get_record('user_info_field', [ 'shortname' => 'level8' ]);
$position_field = $DB->get_record('user_info_field', [ 'shortname' => 'posid' ]);

$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetTitle("$plan->name - Summary");
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);

// Define variables
$page = 0;
$x = 10;
$y = 10;
$sealx = 175;
$sealy = 5;
$sealw = 25;
$sealh = 25;
$sigx = 140;
$sigy = 239;
$custx = 30;
$custy = 230;
$wmarkx = 26;
$wmarky = 58;
$wmarkw = 158;
$wmarkh = 170;
$codey = 250;
$datex = 20;
$datey = 254;
$head1y = 70;
$head2y = 140;
$head3y = 210;
$box1 = 40;
$box2 = 88;
$box3 = 136;
$box4 = 184;
$box5 = 232;
$action_logo_size = 20;
$action_logo_spacing = 5;
$page_center = $pdf->getPageWidth() / 2;


printhead1($ciap, $plan);

$actionno = $posno = 0;
foreach ($actions as $action) {
    $actionno++;
    $posno++;

    if ($posno == 6) {
        $posno = 1;
        printhead1($ciap, $plan);
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

    certificate_print_text($pdf, $y, $x + 40 + ($posno * 20), 'l', 'Helvetica', '', 14, $actionno);
    certificate_print_text($pdf, $y + 5, $x + 40 + ($posno * 20), 'l', 'Helvetica', '', 14, $actionhead, 160);

    if (isset($action->custom_fields->response)) {
        $response = str_replace(', ', '<br/>', $action->custom_fields->response);
        certificate_print_text($pdf, $y + 180, $x + 40 + ($posno * 20), 'l', 'Helvetica', '', 14, $response);
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

    certificate_print_text($pdf, $y + 230, $x + 40 + ($posno * 20), 'l', 'Helvetica', '', 14, $status);
    certificate_print_text($pdf, $y + 230, $x + 46 + ($posno * 20), 'l', 'Helvetica', '', 11, $due);
}

$actionno = 0;
foreach ($actions as $action) {
    $actionno++;
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
        certificate_print_text($pdf, $y + 10, $x + 70, 'l', 'Helvetica', 'B', 16, 'An update has not been provided for this action');
    }

    $pos = $complete = 0;
    foreach ($updates as $update) {
        $pos = 0;
        $actionbody = $actionhead = '';

        printhead2($plan);

        [
            $actionhead,
            $actionbody,
        ] = get_action_content($action);

        if (strlen($actionhead) > 85) {
            $actionhead = substr($actionhead, 0, 85) . '...';
        }

        $pdf->SetTextColor(16, 75, 118);
        certificate_print_text($pdf, $y + 10, $x + 10, 'l', 'Helvetica', 'B', 37, 'Action ' . $actionno);
        $pdf->SetTextColor(0, 0, 0);
        certificate_print_text($pdf, $y + 10, $x + 27, 'l', 'Helvetica', 'i', 18, $actionhead, 240);

        if (strlen($actionbody) > 400) {
            certificate_print_text($pdf, $y + 10, $x + 37, 'l', 'Helvetica', 'i', 12, (substr($actionbody, 0, 400) . '...'), 240);
            $pdf->SetTextColor(187, 111, 122);
            certificate_print_text($pdf, $y + 170, $x + 53, 'l', 'Helvetica', 'B', 12, 'Further details available over the page', 240);
            $pdf->SetTextColor(0, 0, 0);

        } else {
            certificate_print_text($pdf, $y + 10, $x + 37, 'l', 'Helvetica', 'i', 12, $actionbody, 240);
        }

        if (isset($action->custom_fields->response)) {
            certificate_print_text($pdf, $y, $x + 13, 'C', 'Helvetica', '', 9, 'This action promotes the GCH value of');
            certificate_print_text($pdf, $y, $x + 21, 'C', 'Helvetica', '', 9, 'within our work unit');

            $pdf->SetTextColor(16, 75, 118);
            certificate_print_text($pdf, $y, $x + 17, 'C', 'Helvetica', 'B', 9, $action->custom_fields->response);

            $responses = explode(', ', $action->custom_fields->response);
            $response_count = count($responses);
            $logos_size = ( $action_logo_size * $response_count ) + ( $action_logo_spacing * ( $response_count - 1 ));
            $logos_start = $page_center - ( $logos_size / 2 );

            foreach ($responses as $index => $response) {
                $logo_path = "$CFG->dirroot/mod/certificate/type/GFG/$response.png";
                $logo_x = $logos_start + ( $action_logo_size * $index ) + ( $action_logo_spacing * $index );

                $pdf->Image($logo_path, $logo_x, 2, $action_logo_size, $action_logo_size);
            }
        }

        if (isset($action->custom_fields->owner)) {
            $pdf->SetTextColor(16, 75, 118);
            certificate_print_text($pdf, $y + 10, $x + 60, 'l', 'Helvetica', 'B', 12, 'Who is responsible for this action?');
            $pdf->SetTextColor(0, 0, 0);
            certificate_print_text($pdf, $y + 83, $x + 60, 'l', 'Helvetica', '', 12, $action->custom_fields->owner, 75);
        }

        $pdf->SetTextColor(16, 75, 118);
        certificate_print_text($pdf, $y + 160, $x + 60, 'l', 'Helvetica', 'B', 12, 'When is this action due?');
        $pdf->SetTextColor(0, 0, 0);
        certificate_print_text($pdf, $y + 212, $x + 60, 'l', 'Helvetica', '', 12, date('j F Y', $action->duedate));

        $pos++;
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
                $complete = 1;

                break;
            case '3':
                $ans = 'No longer required';

                break;
        }

        $end_date = date('F Y', $perioddate->enddate);
        $due_date = date('j F Y', $update->duedate);

        certificate_print_text($pdf, $y + 10, $x + 40 + ($pos * 30), 'l', 'Helvetica', 'B', 12, "Update $update->periodid");
        certificate_print_text($pdf, $y + 30, $x + 40 + ($pos * 30), 'l', 'Helvetica', '', 12, "($end_date)");
        certificate_print_text($pdf, $y + 160, $x + 40 + ($pos * 30), 'l', 'Helvetica', 'B', 12, 'Status:');
        certificate_print_text($pdf, $y + 180, $x + 40 + ($pos * 30), 'l', 'Helvetica', '', 12, $ans);

        if ($update->duedate) {
            certificate_print_text($pdf, $y + 202, $x + 40 + ($pos * 30), 'l', 'Helvetica', '', 12, "($due_date)");
        }

        certificate_print_text($pdf, $y + 10, $x + 50 + ($pos * 30), 'l', 'Helvetica', '', 12, $update->description);
    }

    if ($complete) {
        $pdf->SetTextColor(187, 111, 122);
        certificate_print_text($pdf, $y + 10, $x + 160, 'l', 'Helvetica', 'B', 16, 'Congratulations on completing this action - make sure you celebrate this win with your team!');
        $pdf->SetTextColor(0, 0, 0);
    }

    if (strlen($actionbody) > 400) {
        printhead2($plan);

        $pdf->Image($logo, 138, 3, 20, 20);
        certificate_print_text($pdf, $y, $x + 13, 'C', 'Helvetica', '', 9, 'This action promotes the GCH value of');
        certificate_print_text($pdf, $y, $x + 21, 'C', 'Helvetica', '', 9, 'within our work unit');
        $pdf->SetTextColor(16, 75, 118);
        certificate_print_text($pdf, $y, $x + 17, 'C', 'Helvetica', 'B', 9, $value);
        certificate_print_text($pdf, $y + 10, $x + 10, 'l', 'Helvetica', 'B', 37, "Action $actionno");
        $pdf->SetTextColor(187, 111, 122);
        certificate_print_text($pdf, $y + 10, $x + 25, 'l', 'Helvetica', 'B', 24, 'Appendix');
        $pdf->SetTextColor(0, 0, 0);
        certificate_print_text($pdf, $y + 10, $x + 40, 'l', 'Helvetica', 'B', 12, $actionhead, 240);
        certificate_print_text($pdf, $y + 10, $x + 50, 'l', 'Helvetica', 'i', 12, $actionbody, 240);

    }
}

function printhead1($ciap, $plan) {
    global $pdf, $DB, $CFG, $x, $y, $page, $division_field, $position_field;

    $pdf->AddPage();
    $page++;
    $pdf->Image("$CFG->dirroot/mod/certificate/type/GFG/CIAP P1.jpg", 0, 0, 297, 210);

    $pdf->SetTextColor(255, 255, 255);
    certificate_print_text($pdf, $y + 95, $x, 'l', 'Helvetica', 'B', 18, $plan->idnumber . ' ' . $plan->name);

    $plan_owner = $DB->get_record('ciap_owners', [ 'planid' => $plan->id ], '*', IGNORE_MULTIPLE);
    if ($plan_owner->type == 0) {
        $user_id = $plan_owner->userid;

    } else {
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

    if ($division_field) {
        $division = $DB->get_record('user_info_data', [ 'fieldid' => $division_field->id, 'userid' => $user_id ]);
        if ($division) {
            certificate_print_text($pdf, $y + 95, $x + 10, 'l', 'Helvetica', '', 12, $division->data);
        }
    }

    if (isset($plan->custom_fields->mergein)) {
        certificate_print_text($pdf, $y + 95, $x + 17, 'l', 'Helvetica', '', 12, $plan->custom_fields->mergein);
    }

    certificate_print_text($pdf, $y + 95, $x + 25, 'l', 'Helvetica', 'B', 18, "$ciap->name - Summary");
    $pdf->SetTextColor(16, 75, 118);

    certificate_print_text($pdf, $y + 5, $x + 50, 'l', 'Helvetica', 'B', 14, 'What is the action we have committed to?');
    certificate_print_text($pdf, $y + 180, $x + 50, 'l', 'Helvetica', 'B', 14, 'Value:');
    certificate_print_text($pdf, $y + 230, $x + 50, 'l', 'Helvetica', 'B', 14, 'Action status:');

    $pdf->SetTextColor(0, 0, 0);
    certificate_print_text($pdf, $y, $x + 175, 'C', 'Helvetica', 'B', 11, "$ciap->name - Summary");
    certificate_print_text($pdf, $y, $x + 180, 'C', 'Helvetica', 'B', 11, "$plan->idnumber $plan->name  -  page $page");
    certificate_print_text($pdf, $y, $x + 185, 'C', 'Helvetica', 'B', 11, 'Printed on ' . date('j F Y', time()));
}

function printhead2($plan) {
    global $pdf, $DB, $CFG, $x, $y, $page;

    $ciap = $DB->get_record('ciap', [ 'id' => $plan->ciapid ]);

    $pdf->AddPage();
    $page++;
    $pdf->Image("$CFG->dirroot/mod/certificate/type/GFG/CIAP P2.jpg", 0, 0, 297, 210);

    certificate_print_text($pdf, $y, $x + 175, 'C', 'Helvetica', 'B', 11, $ciap->name . ' - Summary');
    certificate_print_text($pdf, $y, $x + 180, 'C', 'Helvetica', 'B', 11, $plan->idnumber . ' ' . $plan->name . '  -  page ' . $page);
    certificate_print_text($pdf, $y, $x + 185, 'C', 'Helvetica', 'B', 11, 'Printed on ' . date('j F Y', time()));
}

function get_action_content(stdClass $action) {
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

function outputdata() {
    global $pdf, $DB, $CFG, $x, $y, $page;

    $actions = $DB->get_records('ciap_actions');
    foreach ($actions as $action) {
        echo "
            <br>$action->planid<br>
            <b>Original</b><br>
            $action->description<br>
        ";

        $repl = [ " </p>", " /n", "</p>", "/n" ];
        $repl2 = [ '..', '.  .', '.  .', '. .', '.  .' ];
        $action1 = str_replace($repl, '.', $action->description);
        $action2 = preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $action1);
        $action3 = strip_tags($action2);
        $actionstxt = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", ' ', $action3)));
        $actiontxt = str_replace($repl2, '. ', $actionstxt);
        echo "<br><b>Stripped</b><br>$actiontxt<br>";

        $pos = strpos($actiontxt, '.');
        if ($pos < 80 && $pos > 5) {
            $actionhead = substr($actiontxt, 0, $pos + 1);
            $actionbody = substr($actiontxt, $pos + 1);
            echo "<br><b>Header</b><br>$actionhead<br>";
            echo "<br><b>Body</b><br>$actionbody<br>";
        }

        echo '<br>';
    }

    exit();
}

/**
 *
 *
 * @param string $area
 * @param int $ciap_id
 * @param int $item_id
 * @return stdClass
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
