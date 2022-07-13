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

 /** Get the user currently sitting in the position.
 * 
 * @param $positionID
 * @return $userID
 * 
 */
function mod_certificate_get_userid($positionID){

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
  * Gets the Position ID of a user.
  * @param  $userID
  * @return $positionID
  */
function mod_certificate_get_position_id($userID){

    //Global Variables.
    global $DB;

    //Get the Position ID Field.
    $record = $DB->get_record('user_info_data', array('userid' => $userID, 'fieldid' => 8));
    
    //If we got the Position ID Field.
    if($record){
        //Return the users position id with the specified user id.
        return $record->data;
    }

    return null;
}

/**
  * Gets the Division of a user.
  * @param  $userID
  * @return $division
  */
function mod_certificate_get_division($userID){

    //Global Variables.
    global $DB;

    //Get the Division Field.
    $record = $DB->get_record('user_info_data', array('userid' => $userID, 'fieldid' => 20));
    
    //If we got the Position ID Field.
    if($record){
        //Return the users position id with the specified user id.
        return $record->data;
    }

    return null;
}

/**
  * Gets the Division of a user.
  * @param  $userID
  * @return $division
  */
  function mod_certificate_get_service_line($userID){

    //Global Variables.
    global $DB;

    //Get the Division Field.
    $record = $DB->get_record('user_info_data', array('userid' => $userID, 'fieldid' => 18));
    
    //If we got the Position ID Field.
    if($record){
        //Return the users position id with the specified user id.
        return $record->data;
    }

    return null;
}

/**
 * Get the reports Culture Champion.
 * @param  $Report Number
 * @return $Culture Champion Name
 */
function mod_certificate_get_culture_champion($reportName){

    //Global Variables.
    global $DB;

    // Get the report Number from the start of the report name.
    $reportNumber = substr($reportName, 0, 5);

    //echo "<pre>Report Number: " . $reportNumber . "</pre>";die;

    if(strlen($reportNumber) == 5){

        // Get the Culture Champion responses with the report name.
        $sql = "SELECT qr.*
                FROM {questionnaire_quest_choice} qqc
                JOIN {questionnaire_question} qq ON qqc.question_id = qq.id
                AND qq.surveyid = 1568
                JOIN {questionnaire_resp_single} qrs ON qq.id = qrs.question_id
                AND qqc.id = qrs.choice_id
                JOIN {questionnaire_response} qr ON qrs.response_id = qr.id
                AND qr.complete = 'y'
                WHERE qqc.content LIKE '$reportNumber%'
                ORDER BY qr.submitted DESC
        ";

        // Get all actions related to the report.
        $responses = $DB->get_records_sql($sql);

        // If there is a Culture Champion for this report.
        if(!empty($responses)){

            // Do this for every Culture Champion.
            foreach($responses as $response){

                // Return the Culture Champions Name.
                $question = $DB->get_record('questionnaire_question', array('surveyid' => 1568, 'name' => 'Culture Champion Name', 'deleted' => 'n'));
                $cc = $DB->get_record('questionnaire_response_text', array('response_id' => $response->id, 'question_id' => $question->id));
                    
                return $cc->response;
            }
        }
        else{

            // Return that there is no Culture Champion.
            return "No Culture Champion";
        }
    }
    else {
        // Return that there is no Culture Champion.
        return "No Culture Champion";
    }
}


/**
 * Get the Report Name.
 * 
 * @param 
 * @return
 */
function mod_certificate_get_report_name($reportNum){

    //Global Variables.
    global $DB;

    $sql = "SELECT reportNum.content AS 'number',
                   reportName.content AS 'name'

              FROM {data_content} reportNum

             INNER JOIN {data_content} reportName
                ON reportName.fieldid = 158
               AND reportName.recordid = reportNum.recordid

            WHERE reportNum.fieldid = 157
              AND reportNum.content = $reportNum
    ";

    // Get the Report Name.
    $report = $DB->get_record_sql($sql);

    if($report){
       return $report->number . " " . $report->name;
    }
}

/**
 * Get the Report Owner.
 * 
 * @param 
 * @return userid // Returns the user id of the report owner.
 */
function mod_certificate_get_report_owner($reportNum){

    //Global Variables.
    global $DB;

    $sql = "SELECT u.id AS 'userid'

              FROM {data_content} reportOwner

             INNER JOIN {data_content} reportNum
                ON reportNum.fieldid = 157
               AND reportNum.content = $reportNum

             INNER JOIN {user_info_data} posid
                ON posid.data = reportOwner.content
               AND posid.fieldid = 8

             INNER JOIN {user} u
                ON u.id = posid.userid
               AND u.deleted = 0
               AND u.suspended = 0

             WHERE reportOwner.fieldid = 156
               AND reportOwner.recordid = reportNum.recordid
    ";

    $reportOwners = $DB->get_records_sql($sql);

    if(!empty($reportOwners)){
        foreach($reportOwners as $reportOwner){
            return $reportOwner->userid;
        }
    }
}

/**
 * Get a list of position IDs that report to this positionID.
 * 
 * @param  $positionID
 * @return
 */
function mod_certificate_get_reports_to($positionID){

    // Global Variables.
    global $DB;

    // Users Position ID's.
    $reportsTo;

    $sql = "SELECT posid.data AS 'posid'

              FROM {user} u

             INNER JOIN {user_info_data} reportsto
                ON reportsto.userid = u.id
               AND reportsto.fieldid = 28
               AND reportsto.data = ?

             INNER JOIN {user_info_data} posid
                ON posid.userid = u.id
               AND posid.fieldid = 8

             WHERE u.deleted = 0
               AND u.suspended = 0
    ";

    // Get the users who report to this position.
    $users = $DB->get_records_sql($sql, array($positionID));

    // If this position has atleast 1 user reporting to them.
    if(!empty($users)){

        // Do this for each user reporting to this position.
        foreach($users as $user){
            
            // Add this users position id to the list of staff who report to the posiotion id.
            $reportsTo[] = $user->posid;
        }
    }

    // Return the list of staff members position ids who report to the specified position id.
    return $reportsTo;
}

/**
  * Gets the report name from the Database Module.
  * @param  $positionID
  * @return $reportName
  */
function mod_certificate_get_report_numbers($positionID){

    //Global Variables.
    global $DB;

    //The Report Names.
    $reportNumbers;

    // SQL.
    $sql = "SELECT  reportNum.content AS 'number'

              FROM {data_content} reportNum
            
             INNER JOIN {data_content} posid
                ON posid.fieldid = 154
               AND posid.content = ?

             WHERE reportNum.recordid = posid.recordid
               AND reportNum.fieldid = 155
    ";

    //Get the recordid.
    $reports = $DB->get_records_sql($sql, array($positionID));

    //If we got a record back.
    if(!empty($reports)){

        //Do this for each record.
        foreach($reports as $report){

            //Add this report to the reports.
            $reportNumbers[] = $report->number;
        }
    }

    // Get Every user who reports to this position.
    $staff = mod_certificate_get_reports_to($positionID);

    // If the staff array isnt empty.
    if(!empty($staff)){

        //Do this for each staff.
        foreach($staff as $user){

            // SQL.
            $sql = "SELECT  reportNum.content AS 'number'

                      FROM {data_content} reportNum
  
                     INNER JOIN {data_content} posid
                        ON posid.fieldid = 154
                       AND posid.content = ?

                     WHERE reportNum.recordid = posid.recordid
                       AND reportNum.fieldid = 155
            ";

            //Get the recordid.
            $reports = $DB->get_records_sql($sql, array($user));

            //If we got a record back.
            if(!empty($reports)){

                //Do this for each record.
                foreach($reports as $report){

                    //Add this report to the reports.
                    $reportNumbers[] = $report->number;
                }
            }
        }
    }

    //Return the reports.
    return $reportNumbers;
}

/**
  * Gets the report name from the Database Module.
  * @param  $division
  * @return $reportName
  */
function mod_certificate_get_reports($division){

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
            $userid = mod_certificate_get_userid($record->reportowner);

            // If we got a user for this report.
            if($userid){

                // Get the report owners Division
                $ownerDivision = mod_certificate_get_division($userid);
            
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