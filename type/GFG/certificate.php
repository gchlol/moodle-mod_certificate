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
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

$ciapid = optional_param('ciap', 0, PARAM_INT);

if ($ciapid == "999999") {
	outputdata();
}

$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle("2020 CIAP Summary");
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$page = 0;

// Define variables

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

$plan=$DB->get_record(ciap_plans, array('id' => $ciapid));

printhead1($plan);

$actions=$DB->get_records(ciap_actions, array('planid' => $plan->id));
$actionno=0;
$posno=0;
FOREACH ($actions AS $action) {
	$actionno++;
	$posno++;
	if ($posno==6) {
		$posno=1;
		printhead1($plan);
	}
	$final=headbod($action->description);
	$actionhead=$final[0];
	$actionbod=$final[1];
	
	if($actionhead == NULL) {
	$length=strlen($actionbod);
		if (strlen($actionbod)>140) {
		$actionhead=(substr($actionbod,0,140).'...');
		}
		ELSE {
		$actionhead=$actionbod;
		}
	}
	certificate_print_text($pdf, $y, $x+40+($posno*20), 'l', 'Helvetica', '' ,14, $actionno);
	
	certificate_print_text($pdf, $y+5, $x+40+($posno*20), 'l', 'Helvetica', '' ,14, $actionhead, 160);
	$values=$DB->get_record(customfield_data, array('fieldid' => 55, 'instanceid' => $action->id));
	switch ($values->value) {
			CASE "1":
				$value="Integrity";
				break;
			CASE "2":
				$value="Community First";
				break;
			CASE "3":
				$value="Excellence";
				break;
			CASE "4":
				$value="Respect";
				break;
			CASE "5":
				$value="Compassion";
				break;
			CASE "6":
				$value="Empower";
				break;
	}
	certificate_print_text($pdf, $y+180, $x+40+($posno*20), 'l', 'Helvetica', '' ,14, $value);

	$actionid=$action->id;
	$sql="SELECT * FROM {ciap_updates} cu WHERE cu.actionid=$actionid ORDER BY cu.periodid ASC";
	$updates=$DB->get_records_sql($sql);
	IF (!$updates) {
		$status="No update provided";
		$due='';
		}
	FOREACH ($updates AS $update) {
		$pos++;
		$perioddate=$DB->get_record(ciap_periods, array('id' => $update->periodid));
		switch ($update->status) {
			CASE "0":
				$status="Not yet started";
				$due='(due '.date('d/m/y',$action->duedate).')';
				break;
			CASE "1":
				$status="In progress";
				$due='(due '.date('d/m/y',$action->duedate).')';
				break;
			CASE "2":
				$status="Complete";
				$due='';
				break;
			CASE "3":
				$status="No longer required ";
				$due='';
				break;

		}
	}
	certificate_print_text($pdf, $y+230, $x+40+($posno*20), 'l', 'Helvetica', '' ,14, $status);
	certificate_print_text($pdf, $y+230, $x+45+($posno*20), 'l', 'Helvetica', '' ,11, $due);

	
}

$actionno=0;
FOREACH ($actions AS $action) {

	$actionno++;
	$actionid=$action->id;
	$sql="SELECT * FROM {ciap_updates} cu WHERE cu.actionid=$actionid ORDER BY cu.periodid ASC";
	$updates=$DB->get_records_sql($sql);
	$pos=0;
	$complete=0;
	IF (!$updates) {
		certificate_print_text($pdf, $y+10, $x+70, 'l', 'Helvetica', 'B' ,16, 'An update has not been provided for this action');
	}
	FOREACH ($updates AS $update) {

	$pos=0;
	$actionbody="";
	$actionhead="";
	printhead2($plan);
	$final=headbod($action->description);
	$actionhead=$final[0];
	$actionbody=$final[1];

	
	$actiondate=$action->duedate;
	$pdf->SetTextColor(16, 75, 118);
	certificate_print_text($pdf, $y+10, $x+10, 'l', 'Helvetica', 'B' ,37, 'Action '.$actionno);
	$pdf->SetTextColor(0, 0, 0);
	certificate_print_text($pdf, $y+10, $x+27, 'l', 'Helvetica', 'i' ,18, $actionhead, 240);
	if (strlen($actionbody)>400) {
		certificate_print_text($pdf, $y+10, $x+37, 'l', 'Helvetica', 'i' ,12, (substr($actionbody,0,400).'...'), 240);
		$pdf->SetTextColor(187, 111, 122);
		certificate_print_text($pdf, $y+170, $x+53, 'l', 'Helvetica', 'B' ,12, "Further details available over the page", 240);
		$pdf->SetTextColor(0, 0, 0);
	} else {
		certificate_print_text($pdf, $y+10, $x+37, 'l', 'Helvetica', 'i' ,12, $actionbody, 240);
	}
	
	$response=$DB->get_record(customfield_data, array('fieldid' => 58, 'instanceid' => $action->id));
	$values=$DB->get_record(customfield_data, array('fieldid' => 55, 'instanceid' => $action->id));
	switch ($values->value) {
			CASE "1":
				$value="Integrity";
				$logo="$CFG->dirroot/mod/certificate/type/GFG/Integrity.png";
				break;
			CASE "2":
				$value="Community First";
				$logo="$CFG->dirroot/mod/certificate/type/GFG/Community.png";
				break;
			CASE "3":
				$value="Excellence";
				$logo="$CFG->dirroot/mod/certificate/type/GFG/Excellence.png";
				break;
			CASE "4":
				$value="Respect";
				$logo="$CFG->dirroot/mod/certificate/type/GFG/Respect.png";
				break;
			CASE "5":
				$value="Compassion";
				$logo="$CFG->dirroot/mod/certificate/type/GFG/Compassion.png";
				break;
			CASE "6":
				$value="Empower";
				$logo="$CFG->dirroot/mod/certificate/type/GFG/Empower.png";
				break;
	}
	$pdf->Image($logo, 138, 3, 20, 20);
	certificate_print_text($pdf, $y, $x+13, 'C', 'Helvetica', '' ,9, 'This action promotes the GCH value of');
	certificate_print_text($pdf, $y, $x+21, 'C', 'Helvetica', '' ,9, 'within our work unit');
	
			
	$pdf->SetTextColor(16, 75, 118);
	certificate_print_text($pdf, $y, $x+17, 'C', 'Helvetica', 'B' ,9, $value);
	certificate_print_text($pdf, $y+10, $x+60, 'l', 'Helvetica', 'B' ,12, 'Who is responsible for this action?');
	certificate_print_text($pdf, $y+160, $x+60, 'l', 'Helvetica', 'B' ,12, 'When is this action due?');


	$pdf->SetTextColor(0, 0, 0);
	certificate_print_text($pdf, $y+83, $x+60, 'l', 'Helvetica', '' ,12, $response->value,75);
	certificate_print_text($pdf, $y+212, $x+60, 'l', 'Helvetica', '' ,12, date('j F Y',$action->duedate));

		$pos++;
		$perioddate=$DB->get_record(ciap_periods, array('id' => $update->periodid));
		switch ($update->status) {
			CASE "0":
				$ans="Not yet started";
				break;
			CASE "1":
				$ans="In progress";
				break;
			CASE "2":
				$ans="Complete";
				$complete=1;
				break;
			CASE "3":
				$ans="No longer required";
				break;
		}
		certificate_print_text($pdf, $y+10, $x+40+($pos*30), 'l', 'Helvetica', 'B' ,12, 'Update '.$update->periodid);
		certificate_print_text($pdf, $y+30, $x+40+($pos*30), 'l', 'Helvetica', '' ,12, '('.date('F Y',$perioddate->enddate).')');
		certificate_print_text($pdf, $y+160, $x+40+($pos*30), 'l', 'Helvetica', 'B' ,12, 'Status:');
		certificate_print_text($pdf, $y+180, $x+40+($pos*30), 'l', 'Helvetica', '' ,12, $ans);
		IF ($update->duedate) {
			certificate_print_text($pdf, $y+202, $x+40+($pos*30), 'l', 'Helvetica', '' ,12, '('.date('j F Y',$update->duedate).')');
		}
		
		certificate_print_text($pdf, $y+10, $x+50+($pos*30), 'l', 'Helvetica', '' ,12, $update->description);
		
		
	}
	IF ($complete) {
		$pdf->SetTextColor(187, 111, 122);
		certificate_print_text($pdf, $y+10, $x+160, 'l', 'Helvetica', 'B' ,16, 'Congratulations on completing this action - make sure you celebrate this win with your team!');
		$pdf->SetTextColor(0, 0, 0);
	}
	IF (strlen($actionbody)>400) {
		printhead2($plan);
		$pdf->Image($logo, 138, 3, 20, 20);
		certificate_print_text($pdf, $y, $x+13, 'C', 'Helvetica', '' ,9, 'This action promotes the GCH value of');
		certificate_print_text($pdf, $y, $x+21, 'C', 'Helvetica', '' ,9, 'within our work unit');
		$pdf->SetTextColor(16, 75, 118);
		certificate_print_text($pdf, $y, $x+17, 'C', 'Helvetica', 'B' ,9, $value);
		certificate_print_text($pdf, $y+10, $x+10, 'l', 'Helvetica', 'B' ,37, 'Action '.$actionno);
		$pdf->SetTextColor(187, 111, 122);
		certificate_print_text($pdf, $y+10, $x+25, 'l', 'Helvetica', 'B' ,24, 'Appendix');
		$pdf->SetTextColor(0, 0, 0);
		certificate_print_text($pdf, $y+10, $x+40, 'l', 'Helvetica', 'B' ,12, $actionhead, 240);
		certificate_print_text($pdf, $y+10, $x+50, 'l', 'Helvetica', 'i' ,12, $actionbody, 240);

	}
}		
	

function printhead1($plan) {
GLOBAL $pdf, $DB, $CFG, $x, $y, $page;

$pdf->AddPage();
$page++;
$pdf->Image("$CFG->dirroot/mod/certificate/type/GFG/CIAP P1.jpg", 0, 0, 297, 210);

$pdf->SetTextColor(255, 255, 255);
certificate_print_text($pdf, $y+95, $x, 'l', 'Helvetica', 'B' ,18, $plan->idnumber.' '.$plan->name);

$posid=$DB->get_record(ciap_owners, array('planid' => $plan->id));
$includes=$DB->get_record(customfield_data, array('fieldid' => '73', 'instanceid' => $plan->id));
if (!$posid->value) {
		$userid->userid=$posid->userid;
} ELSE {
	$userid=$DB->get_record(user_info_data, array('fieldid' => 8, 'data' => $posid->value));
}
$division=$DB->get_record(user_info_data, array('fieldid' => 20, 'userid' => $userid->userid));
certificate_print_text($pdf, $y+95, $x+10, 'l', 'Helvetica', '' ,12,$division->data);
certificate_print_text($pdf, $y+95, $x+17, 'l', 'Helvetica', '' ,12,$includes->value);

certificate_print_text($pdf, $y+95, $x+25, 'l', 'Helvetica', 'B' ,18, '2020 Continuous Improvement Action Plan - Summary');
$pdf->SetTextColor(16, 75, 118);

certificate_print_text($pdf, $y+5, $x+50, 'l', 'Helvetica', 'B' ,14, 'What is the action we have committed to?');
certificate_print_text($pdf, $y+180, $x+50, 'l', 'Helvetica', 'B' ,14, 'Value:');
certificate_print_text($pdf, $y+230, $x+50, 'l', 'Helvetica', 'B' ,14, 'Action status:');

$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $y, $x+175, 'C', 'Helvetica', 'B' ,11, '2020 Continuous Improvement Action Plan - Summary');
certificate_print_text($pdf, $y, $x+180, 'C', 'Helvetica', 'B' ,11, $plan->idnumber.' '.$plan->name.'  -  page '.$page);
certificate_print_text($pdf, $y, $x+185, 'C', 'Helvetica', 'B' ,11, 'Printed on '.date('j F Y',time()));

}


function printhead2($plan) {
GLOBAL $pdf, $DB, $CFG, $x, $y, $page;

$pdf->AddPage();
$page++;
$pdf->Image("$CFG->dirroot/mod/certificate/type/GFG/CIAP P2.jpg", 0, 0, 297, 210);

certificate_print_text($pdf, $y, $x+175, 'C', 'Helvetica', 'B' ,11, '2020 Continuous Improvement Action Plan - Summary');
certificate_print_text($pdf, $y, $x+180, 'C', 'Helvetica', 'B' ,11, $plan->idnumber.' '.$plan->name.'  -  page '.$page);
certificate_print_text($pdf, $y, $x+185, 'C', 'Helvetica', 'B' ,11, 'Printed on '.date('j F Y',time()));

}

function headbod($text) {
		$repl = array(" </p>"," /n","</p>","/n");
		$repl2 = array("..",".  .",".  .",". .",".  .");
		$text1=str_replace($repl,".",$text);
		$text2=preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $text1);
		$text3=strip_tags($text2);
		$text4 = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $text3)));
		$text5=str_replace($repl2,". ",$text4);
		$pos=strpos($text5,".");
		if ($pos<80 && $pos>5) {
			$head=substr($text5,0,$pos+1);
			$body=substr($text5, $pos+1);
		}
		ELSE {
				$body=$text5;
		}
		$final=array($head,$body);
		return $final;
}	

function outputdata() {
GLOBAL $pdf, $DB, $CFG, $x, $y, $page;
$actions=$DB->get_records(ciap_actions);
$actionno=0;
$posno=0;

	FOREACH ($actions AS $action) {
		echo "<br>".$action->planid."<br><b>Original</b><br>".$action->description . "<br>";
		$repl = array(" </p>"," /n","</p>","/n");
		$repl2 = array("..",".  .",".  .",". .",".  .");
		$action1=str_replace($repl,".",$action->description);
		$action2=preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $action1);
		$action3=strip_tags($action2);
		$actionstxt = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $action3)));
		$actiontxt=str_replace($repl2,". ",$actionstxt);
		echo "<br><b>Stripped</b><br>".$actiontxt . "<br>";
		$pos=strpos($actiontxt,".");
		if ($pos<80 && $pos>5) {
			$actionhead=substr($actiontxt,0,$pos+1);
			$actionbody=substr($actiontxt, $pos+1);
			echo "<br><b>Header</b><br>".$actionhead."<br>";
			echo "<br><b>Body</b><br>".$actionbody."<br>";
		}
	echo "<br>";
	$actionhead="";
	$actionbody="";
	}
EXIT();
}


