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

/**
 * Library file for the CIAP certificate for moodle.
 *
 * @package   certificate_type_CIAP Quarterly Update 2
 * @copyright 2019 Nathan Robertson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
  * Gets the Division of a user.
  * @param  $userID
  * @return $division
  */
function get_division($userID){

    // Global Variables.
    global $DB;

    // Get the division Field.
    $record = $DB->get_record('user_info_data', array('userid' => $userID, 'fieldid' => 20));
    
    // If we got the division Field.
    if($record){

        //Return the users division with the specified user id.
        return $record->data;
    }

    return null;
}

/** Get the user currently sitting in the position.
 * 
 * @param $positionID
 * @return $userID
 * 
 */
function get_userid($positionID){

    // Global Variables.
    global $DB;

    // SQL
    $sql = "SELECT *
              FROM {user} u
             INNER JOIN {user_info_data} uid
                ON uid.userid = u.id
               AND uid.fieldid = 28
               AND uid.data = ?
             WHERE u.deleted = 0
               AND u.suspended = 0
             LIMIT 1
    ";
    
    // Get the user who currently sits in the position.
    $user = $DB->get_record_sql($sql, array($positionID));

    // If there is a user in the position.
    if($user){
        
        // Return the user id.
        return $user->userid;
    }
}

/**
  * Gets the Service Line of a user.
  * @param  $userID
  * @return $division
  */
function get_service_line($userID){

    // Global Variables.
    global $DB;

    // Get the Division Field.
    $record = $DB->get_record('user_info_data', array('userid' => $userID, 'fieldid' => 18));
    
    // If we got the Position ID Field.
    if($record){

        // Return the users position id with the specified user id.
        return $record->data;
    }

    // Return null.
    return null;
}

/**
 * Get the reports Culture Champion.
 * @param  $Report Number
 * @return $Culture Champion Name
 */
function get_culture_champion($reportName){

    // Global Variables.
    global $DB;

    // Get the Culture Champion responses with the report name.
    $sql = "SELECT qr.*
              FROM {questionnaire_quest_choice} qqc
              JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
               AND qq.surveyid = 1568
              JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
               AND qqc.id = qrs.choice_id
              JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
               AND qr.complete = 'y'
             WHERE qqc.content LIKE '$reportName%'
             ORDER BY qr.submitted DESC
    ";

    // Get all actions related to the report.
    $responses = $DB->get_records_sql($sql);
    
    // Do this for every Culture Champion.
    foreach($responses as $response){

        // Get the culuture champion question.
        $question = $DB->get_record('questionnaire_question', array('surveyid' => 1568, 'name' => 'Culture Champion Name', 'deleted' => 'n'));
        
        // Get the culture champion response.
        $cc = $DB->get_record('questionnaire_response_text', array('response_id' => $response->id, 'question_id' => $question->id));
        
        // Return the Culture Champions Name
        return $cc->response;
    }
}


/**
 * Get the Report Name.
 * 
 * @param $reportNum - the report number.
 * @return $reportName - the report name asociated with the report number.
 */
function get_report_name($reportNum){

    // Global Variables.
    global $DB;

    // Get the record with the Report Number.
    $record = $DB->get_record('data_content', array('fieldid' => 157, 'content' => $reportNum));

    // If we got a record back.
    if($record){

        // Get the Report Name.
        $name = $DB->get_record('data_content', array('fieldid' => 158, 'recordid' => $record->recordid));
        
        // return the Report Name.
        return $record->content . " " . $name->content;
    }
}

/**
 * Get the Report Owner.
 * 
 * @param $reportNum
 * @return $userid of report owner.
 */
function get_report_owner($reportNum){

    //Global Variables.
    global $DB;

    $record = $DB->get_record('data_content', array('fieldid' => 157, 'content' => $reportNum));

    // If we got a record back.
    if($record){

        // Get the report owners position ID
        $reportOwner = $DB->get_record('data_content', array('fieldid' => 156, 'recordid' => $record->recordid));
    
        // Get the Position ID Field.
        $user = $DB->get_record('user_info_data', array('fieldid' => 8, 'data' => $reportOwner->content));
    
        // Return the User ID.
        return $user->userid;
    }
}

/**
 * Get the staff who report to this positionID.
 * 
 * @param  $positionID
 * @return
 */
function get_reports_to($positionID){

    // Global Variables.
    global $DB;

    // Users Position ID's.
    $staff;

    // Get all the current Users.
    $users = $DB->get_records('user', array('deleted' => 0, 'suspended' => 0));

    // Do this for each User.
    foreach($users as $user){

        // Get who the current user reports too.
        $reportsTo = $DB->get_record('user_info_data', array('userid' => $user->id, 'fieldid' => 28));

        // If this user reports to this positionID.
        if($positionID == $reportsTo->data){

            // Get the current users postionID.
            $posID = $DB->get_record('user_info_data', array('userid' => $user->id, 'fieldid' => 8));

            // Add this users position ID to the list of staff.
            $staff[] = $posID->data;
        }
    }

    // Return an array of staff who report to this positionID.
    return $staff;
}

/**
  * Gets the report name from the Database Module.
  * @param  $division
  * @return $reportName
  */
function get_reports($division){

    // Global Variables.
    global $DB;

    // The Report Names.
    $reports;

    // SQL.
    $sql = "SELECT  reportNum.content AS 'ReportNumber',
                    reportName.content AS 'ReportName',
                    reportOwner.content AS 'ReportOwner'
              FROM {data_content} reportNum

             INNER JOIN {data_content} reportName
                ON reportName.recordid = reportNum.recordid
               AND reportName.fieldid = 158

             INNER JOIN {data_content} reportOwner
                ON reportOwner.recordid = reportNum.recordid
               AND reportOwner.fieldid = 156

             WHERE reportNum.fieldid = 157
    ";

    // Get the recordid.
    $records = $DB->get_records_sql($sql);

    // If we got a record back.
    if(!empty($records)){

        // Do this for each record.
        foreach($records as $record){

            // Get the user in the report owner position.
            $userid = get_userid($record->reportowner);

            // If we got a user for this report.
            if($userid){

                // Get the report owners Division
                $ownerDivision = get_division($userid);
            
                // If the report owners division = the ED division.
                if($division == $ownerDivision){

                    // Add this report to the reports.
                    $reports[] = $record->reportnumber;
                }
            }
        }
    }

    // Return the reports.
    return $reports;
}