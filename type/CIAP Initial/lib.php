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
  * Gets the Position ID of a user.
  * @param  $userID
  * @return $positionID
  */
function get_position_id($userID){

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
  function get_division($userID){

    //Global Variables.
    global $DB;

    //Get the Division Field.
    $record = $DB->get_record('user_info_data', array('userid' => $userID, 'fieldid' => 19));
    
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
  function get_service_line($userID){

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

function get_report_name($reportNum){

    //Global Variables.
    global $DB;

    $record = $DB->get_record('data_content', array('fieldid' => 157, 'content' => $reportNum));

    //If we got a record back.
    if($record){

        //return the Report Name.
        //$num = $DB->get_record('data_content', array('fieldid' => 157, 'recordid' => $record->recordid));
        $name = $DB->get_record('data_content', array('fieldid' => 158, 'recordid' => $record->recordid));
        
        return $record->content . " " . $name->content;
    }
}

function get_report_owner($reportNum){

    //Global Variables.
    global $DB;

    $record = $DB->get_record('data_content', array('fieldid' => 157, 'content' => $reportNum));

    //If we got a record back.
    if($record){

        //Get the report owners position ID
        $reportOwner = $DB->get_record('data_content', array('fieldid' => 156, 'recordid' => $record->recordid));
    
        //Get the Position ID Field.
        $user = $DB->get_record('user_info_data', array('fieldid' => 8, 'data' => $reportOwner->content));
    
        return $user->userid;
    }
}

/**
  * Gets the report name from the Database Module.
  * @param  $positionID
  * @return $reportName
  */
  function get_reports($positionID){

    //Global Variables.
    global $DB;

    //The Report Names.
    $reports;

    //Get the recordid.
    $records = $DB->get_records('data_content', array('fieldid' => 154, 'content' => $positionID));

    //If we got a record back.
    if(!empty($records)){

        //Do this for each record.
        foreach($records as $record){
            
            //Get the report name.
            $report = $DB->get_record('data_content', array('fieldid' => 155, 'recordid' => $record->recordid));
           
            //If we got a report name.
            if($report){

                //Add this report to the reports.
                $reports[] = $report->content;
            }
        }
    }

    //Return the reports.
    return $reports;
}