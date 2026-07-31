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
 * Language strings for the certificate module
 *
 * @package    mod_certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addlinklabel'] = 'Add another linked activity option';
$string['addlinktitle'] = 'Click to add another linked activity option';
$string['areaintro'] = 'Certificate introduction';
$string['awarded'] = 'Awarded';
$string['awardedto'] = 'Awarded To';
$string['back'] = 'Back';
$string['border'] = 'Border';
$string['borderblack'] = 'Black';
$string['borderblue'] = 'Blue';
$string['borderbrown'] = 'Brown';
$string['bordercolor'] = 'Border Lines';
$string['bordercolor_help'] = 'Since images can substantially increase the size of the pdf file, you may choose to print a border of lines instead of using a border image (be sure the Border Image option is set to No).  The Border Lines option will print a nice border of three lines of varying widths in the chosen color.';
$string['bordergreen'] = 'Green';
$string['borderlines'] = 'Lines';
$string['borderstyle'] = 'Border Image';
$string['borderstyle_help'] = 'The Border Image option allows you to choose a border image from the certificate/pix/borders folder.  Select the border image that you want around the certificate edges or select no border.';
$string['certificate'] = 'Verification for certificate code:';
$string['certificate:addinstance'] = 'Add a certificate instance';
$string['certificate:manage'] = 'Manage a certificate instance';
$string['certificate:printteacher'] = 'Be listed as a teacher on the certificate if the print teacher setting is on';
$string['certificate:student'] = 'Retrieve a certificate';
$string['certificate:view'] = 'View a certificate';
$string['certificatename'] = 'Certificate Name';
$string['certificatereport'] = 'Certificates Report';
$string['certificatesfor'] = 'Certificates for';
$string['certificatetype'] = 'Certificate Type';
$string['certificatetype_help'] = 'This is where you determine the layout of the certificate. The certificate type folder includes four default certificates:
A4 Embedded prints on A4 size paper with embedded font.
A4 Non-Embedded prints on A4 size paper without embedded fonts.
Letter Embedded prints on letter size paper with embedded font.
Letter Non-Embedded prints on letter size paper without embedded fonts.

The non-embedded types use the Helvetica and Times fonts.  If you feel your users will not have these fonts on their computer, or if your language uses characters or symbols that are not accommodated by the Helvetica and Times fonts, then choose an embedded type.  The embedded types use the Dejavusans and Dejavuserif fonts.  This will make the pdf files rather large; thus it is not recommended to use an embedded type unless you must.

New type folders can be added to the certificate/type folder. The name of the folder and any new language strings for the new type must be added to the certificate language file.';
$string['certify'] = 'This is to certify that';
$string['code'] = 'Code';
$string['completiondate'] = 'Course Completion';
$string['course'] = 'For';
$string['coursegrade'] = 'Course Grade';
$string['coursename'] = 'Course';
$string['coursetimereq'] = 'Required minutes in course';
$string['coursetimereq_help'] = 'Enter here the minimum amount of time, in minutes, that a student must be logged into the course before they will be able to receive the certificate.';
$string['credithours'] = 'Credit Hours';
$string['customtext'] = 'Custom Text';
$string['customtext_help'] = 'If you want the certificate to print different names for the teacher than those who are assigned
the role of teacher, do not select Print Teacher or any signature image except for the line image.  Enter the teacher names in this text box as you would like them to appear.  By default, this text is placed in the lower left of the certificate. The following html tags are available: &lt;br&gt;, &lt;p&gt;, &lt;b&gt;, &lt;i&gt;, &lt;u&gt;, &lt;img&gt; (src and width (or height) are mandatory), &lt;a&gt; (href is mandatory), &lt;font&gt; (possible attributes are: color, (hex color code), face, (arial, times, courier, helvetica, symbol)).';
$string['date'] = 'On';
$string['datefmt'] = 'Date Format';
$string['datefmt_help'] = 'Choose a date format to print the date on the certificate. Or, choose the last option to have the date printed in the format of the user\'s chosen language.';
$string['datehelp'] = 'Date';
$string['deletissuedcertificates'] = 'Delete issued certificates';
$string['delivery'] = 'Delivery';
$string['delivery_help'] = 'Choose here how you would like your students to get their certificate.
Open in Browser: Opens the certificate in a new browser window.
Force Download: Opens the browser file download window.
Email Certificate: Choosing this option sends the certificate to the student as an email attachment.
After a user receives their certificate, if they click on the certificate link from the course homepage, they will see the date they received their certificate and will be able to review their received certificate.';
$string['designoptions'] = 'Design Options';
$string['download'] = 'Force download';
$string['emailcertificate'] = 'Email';
$string['emailothers'] = 'Email Others';
$string['emailothers_help'] = 'Enter the email addresses here, separated by a comma, of those who should be alerted with an email whenever students receive a certificate.';
$string['emailstudenttext'] = 'Attached is your certificate for {$a->course}.';
$string['emailteachers'] = 'Email Teachers';
$string['emailteachers_help'] = 'If enabled, then teachers are alerted with an email whenever students receive a certificate.';
$string['emailteachermail'] = '
{$a->student} has received their certificate: \'{$a->certificate}\'
for {$a->course}.

You can review it here:

    {$a->url}';
$string['emailteachermailhtml'] = '
{$a->student} has received their certificate: \'<i>{$a->certificate}</i>\'
for {$a->course}.

You can review it here:

    <a href="{$a->url}">Certificate Report</a>.';
$string['entercode'] = 'Enter certificate code to verify:';
$string['fontsans'] = 'Sans-serif font family';
$string['fontsans_desc'] = 'Sans-serif font family for certificates with embedded fonts';
$string['fontserif'] = 'Serif font family';
$string['fontserif_desc'] = 'Serif font family for certificates with embedded fonts';
$string['getcertificate'] = 'Get your certificate';
$string['grade'] = 'Grade';
$string['gradedate'] = 'Grade Date';
$string['gradefmt'] = 'Grade Format';
$string['gradefmt_help'] = 'There are three available formats if you choose to print a grade on the certificate:

Percentage Grade: Prints the grade as a percentage.
Points Grade: Prints the point value of the grade.
Letter Grade: Prints the percentage grade as a letter.';
$string['gradeletter'] = 'Letter Grade';
$string['gradepercent'] = 'Percentage Grade';
$string['gradepoints'] = 'Points Grade';
$string['imagetype'] = 'Image Type';
$string['incompletemessage'] = 'In order to download your certificate, you must first complete all required activities. Please return to the course to complete your coursework.';
$string['intro'] = 'Introduction';
$string['issueoptions'] = 'Issue Options';
$string['issued'] = 'Issued';
$string['issueddate'] = 'Date Issued';
$string['landscape'] = 'Landscape';
$string['landscape_unsupported'] = 'Landscape orientation is not supported on this certificate';
$string['lastviewed'] = 'You last received this certificate on:';
$string['letter'] = 'Letter';
$string['lockingoptions'] = 'Locking Options';
$string['modulename'] = 'Certificate';
$string['modulename_help'] = 'This module allows for the dynamic generation of certificates based on predefined conditions set by the teacher.';
$string['modulename_link'] = 'Certificate_module';
$string['modulenameplural'] = 'Certificates';
$string['mycertificates'] = 'My Certificates';
$string['nocertificates'] = 'There are no certificates';
$string['nocertificatesissued'] = 'There are no certificates that have been issued';
$string['nocertificatesreceived'] = 'has not received any course certificates.';
$string['nofileselected'] = 'Must choose a file to upload!';
$string['nogrades'] = 'No grades available';
$string['notapplicable'] = 'N/A';
$string['notfound'] = 'The certificate number could not be validated.';
$string['notissued'] = 'Not Issued';
$string['notissuedyet'] = 'Not issued yet';
$string['notreceived'] = 'You have not received this certificate';
$string['openbrowser'] = 'Open in new window';
$string['opendownload'] = 'Click the button below to save your certificate to your computer.';
$string['openemail'] = 'Click the button below and your certificate will be sent to you as an email attachment.';
$string['openwindow'] = 'Click the button below to open your certificate in a new browser window.';
$string['or'] = 'Or';
$string['orientation'] = 'Orientation';
$string['orientation_help'] = 'Choose whether you want your certificate orientation to be portrait or landscape.';
$string['pluginadministration'] = 'Certificate administration';
$string['pluginname'] = 'Certificate';
$string['portrait'] = 'Portrait';
$string['portrait_unsupported'] = 'Portrait orientation is not supported on this certificate';
$string['printdate'] = 'Print Date';
$string['printdate_help'] = 'This is the date that will be printed, if a print date is selected. If the course completion date is selected but the student has not completed the course, the date received will be printed. You can also choose to print the date based on when an activity was graded. If a certificate is issued before that activity is graded, the date received will be printed.';
$string['printerfriendly'] = 'Printer-friendly page';
$string['printhours'] = 'Print Credit Hours';
$string['printhours_help'] = 'Enter here the number of credit hours to be printed on the certificate.';
$string['printgrade'] = 'Print Grade';
$string['printgrade_help'] = 'You can choose any available course grade items from the gradebook to print the user\'s grade received for that item on the certificate.  The grade items are listed in the order in which they appear in the gradebook. Choose the format of the grade below.';
$string['printnumber'] = 'Print Code';
$string['printnumber_help'] = 'A unique 10-digit code of random letters and numbers can be printed on the certificate. This number can then be verified by comparing it to the code number displayed in the certificates report.';
$string['printoutcome'] = 'Print Outcome';
$string['printoutcome_help'] = 'You can choose any course outcome to print the name of the outcome and the user\'s received outcome on the certificate.  An example might be: Assignment Outcome: Proficient.';
$string['printseal'] = 'Seal or Logo Image';
$string['printseal_help'] = 'This option allows you to select a seal or logo to print on the certificate from the certificate/pix/seals folder. By default, this image is placed in the lower right corner of the certificate.';
$string['printsignature'] = 'Signature Image';
$string['printsignature_help'] = 'This option allows you to print a signature image from the certificate/pix/signatures folder.  You can print a graphic representation of a signature, or print a line for a written signature. By default, this image is placed in the lower left of the certificate.';
$string['printteacher'] = 'Print Teacher Name(s)';
$string['printteacher_help'] = 'For printing the teacher name on the certificate, set the role of teacher at the module level.  Do this if, for example, you have more than one teacher for the course or you have more than one certificate in the course and you want to print different teacher names on each certificate.  Click to edit the certificate, then click on the Locally assigned roles tab.  Then assign the role of Teacher (editing teacher) to the certificate (they do not HAVE to be a teacher in the course--you can assign that role to anyone).  Those names will be printed on the certificate for teacher.';
$string['printwmark'] = 'Watermark Image';
$string['printwmark_help'] = 'A watermark file can be placed in the background of the certificate. A watermark is a faded graphic. A watermark could be a logo, seal, crest, wording, or whatever you want to use as a graphic background.';
$string['receivedcerts'] = 'Received certificates';
$string['receiveddate'] = 'Date Received';
$string['removecert'] = 'Issued certificates removed';
$string['report'] = 'Report';
$string['reportcert'] = 'Report Certificates';
$string['reportcert_help'] = 'If you choose yes here, then this certificate\'s date received, code number, and the course name will be shown on the user certificate reports.  If you choose to print a grade on this certificate, then that grade will also be shown on the certificate report.';
$string['requiredtimenotmet'] = 'You must spend at least a minimum of {$a->requiredtime} minutes in the course before you can access this certificate';
$string['requiredtimenotvalid'] = 'The required time must be a valid number greater than 0';
$string['reviewcertificate'] = 'Review your certificate';
$string['savecert'] = 'Save Certificates';
$string['savecert_help'] = 'If you choose this option, then a copy of each user\'s certificate pdf file is saved in the course files moddata folder for that certificate. A link to each user\'s saved certificate will be displayed in the certificate report.';
$string['seal'] = 'Seal';
$string['sigline'] = 'line';
$string['signature'] = 'Signature';
$string['statement'] = 'has completed the course';
$string['summaryofattempts'] = 'Summary of previously received certificates';
$string['textoptions'] = 'Text Options';
$string['title'] = 'CERTIFICATE of ACHIEVEMENT';
$string['to'] = 'Awarded to';
$string['typeA4_embedded'] = 'A4 Embedded';
$string['typeA4_non_embedded'] = 'A4 Non-Embedded';
$string['typeletter_embedded'] = 'Letter Embedded';
$string['typeletter_non_embedded'] = 'Letter Non-Embedded';
$string['unsupportedfiletype'] = 'File must be a jpeg or png file';
$string['uploadimage'] = 'Upload image';
$string['uploadimagedesc'] = 'This button will take you to a new screen where you will be able to upload images.';
$string['userdateformat'] = 'User\'s Language Date Format';
$string['validate'] = 'Verify';
$string['verifycertificate'] = 'Verify Certificate';
$string['viewcertificateviews'] = 'View {$a} issued certificates';
$string['viewed'] = 'You received this certificate on:';
$string['viewtranscript'] = 'View Certificates';
$string['watermark'] = 'Watermark';

// Settings
$string['setting:reponame'] = 'Certificate Type Repository Name';
$string['setting:reponame_desc'] = 'Name of the repository to load certificate types from instead of the internal types directory.';

$string['privacy:metadata:mod_certificate'] = 'Info about issued certificates';
$string['privacy:metadata:mod_certificate:userid'] = 'User info';
$string['privacy:metadata:mod_certificate:certificateid'] = 'Certificate info';
$string['privacy:metadata:mod_certificate:code'] = 'Code info';
$string['privacy:metadata:mod_certificate:timecreated'] = 'Time created info';
$string['privacy:metadata:core_files'] = 'Files linked to issued certificates are stored using the core_files system';

// type/Portfolio
$string['portfolio_title'] = 'Portfolio';
$string['portfolio_title_contfor'] = 'Portfolio (cont\'d) for {$a}';
$string['portfolio_continued'] = '(cont\'d)';

$string['portfolio_colour_primary'] = '#003c69';
$string['portfolio_colour_secondary'] = '#808080';
$string['portfolio_colour_base'] = '#000000';
$string['portfolio_colour_minor'] = '#808080';

$string['portfolio_site'] = 'Gold Coast Health';
$string['portfolio_service'] = 'Learning On-Line';
$string['portfolio_siteservice'] = 'Gold Coast Health Learning On-Line';

$string['portfolio_postuser'] = 'has completed the following';
$string['portfolio_preuser'] = 'This is to certify that';
$string['portfolio_printedon'] = 'Printed on {$a}';
$string['portfolio_siteservicelabel'] = 'Presented by';
$string['portfolio_coursemandatory'] = 'Mandatory courses';
$string['portfolio_coursemandatory_cont'] = 'Mandatory courses (cont\'d)';
$string['portfolio_courseother'] = 'Other courses';
$string['portfolio_courseother_cont'] = 'Other courses (cont\'d)';
$string['portfolio_courseadditional'] = 'Additional learning modules';
$string['portfolio_courseadditional_cont'] = 'Additional learning modules (cont\'d)';

// Additional shared strings.
$string['actionstatuscolon'] = 'Action status:';
$string['agreementscontinuedfor'] = 'Agreements (continued) for {$a}';
$string['appendix'] = 'Appendix';
$string['approvedleavefor'] = '{$a} has been approved leave for the following days:';
$string['atsiprogramname'] = 'Aboriginal & Torres Strait Islander';
$string['atsiprogramsubtitle'] = 'Cultural Practice Program';
$string['attendedfollowing'] = 'has attended the following';
$string['attendedsession'] = 'has attended the session';
$string['certificateofattendance'] = 'Certificate of Attendance';
$string['certificateofcompletion'] = 'Certificate of completion';
$string['completedcourse'] = 'has completed the following course';
$string['completedrequirements'] = 'has successfully completed the requirements of';
$string['completedtheoreticalrequirements'] = 'has successfully completed the theoretical requirements of';
$string['coursefacilitator'] = 'Course facilitator';
$string['coursefacilitators'] = 'Course facilitators';
$string['coursegradevalue'] = 'Course grade: {$a}';
$string['coursemisconfigured'] = 'The course is misconfigured.';
$string['coursetheory'] = 'course theory';
$string['credithoursvalue'] = 'Credit hours: {$a}';
$string['dateformatdaymonthyear'] = '1 January 2000';
$string['dateformatmonthdayordinalyear'] = 'January 1st, 2000';
$string['dateformatmonthdayyear'] = 'January 1, 2000';
$string['dateformatmonthyear'] = 'January 2000';
$string['durationhours'] = '({$a} hours)';
$string['employeelevelvalue'] = 'Employee level: {$a}';
$string['gchlearningonline'] = 'Gold Coast Health Learning On-Line';
$string['includedmodules'] = 'which included the following modules';
$string['invalidcertificate'] = 'The certificate is invalid.';
$string['invalidcourse'] = 'The course is invalid.';
$string['invalidcoursemodule'] = 'The course module is invalid.';
$string['issuedcolon'] = 'Issued:';
$string['modulegradevalue'] = '{$a->name} grade: {$a->grade}';
$string['namevalue'] = 'Name: {$a}';
$string['nooutcomes'] = 'No outcomes available';
$string['on'] = 'on';
$string['ordinalnd'] = 'nd';
$string['ordinalrd'] = 'rd';
$string['ordinalst'] = 'st';
$string['ordinalth'] = 'th';
$string['orgunitnamevalue'] = 'Org unit name: {$a}';
$string['orgunitnumbervalue'] = 'Org unit number: {$a}';
$string['outcomevalue'] = '{$a->name}: {$a->grade}';
$string['pagexofy'] = 'Page {$a->page} of {$a->pages}';
$string['payrollnumbervalue'] = 'Payroll number: {$a}';
$string['portfoliounavailable'] = 'This Portfolio certificate is no longer available.';
$string['positiondescriptionvalue'] = 'Position description: {$a}';
$string['positionvalue'] = 'Position: {$a}';
$string['presentedby'] = 'Presented by';
$string['reporttitle'] = '{$a}: Report';
$string['statementofattainment'] = 'Statement of Attainment';
$string['statementofattendance'] = 'Statement of Attendance';
$string['successfullycompletedthe'] = 'has successfully completed the';
$string['supervisor'] = 'Supervisor';
$string['valuecolon'] = 'Value:';

// Certificate type names.
$string['typea4_embedded'] = 'A4 embedded';
$string['typea4_non_embedded'] = 'A4 non-embedded';
$string['typeadvlifesupp'] = 'Advanced life support';
$string['typeatsi'] = 'ATSI';
$string['typeballot'] = 'Ballot';
$string['typebls'] = 'BLS';
$string['typecertificate_of_completion'] = 'Certificate of completion';
$string['typecertificate_of_completion_theory'] = 'Certificate of completion theory';
$string['typeciap'] = 'CIAP';
$string['typeciap_close_out'] = 'CIAP close out';
$string['typeciap_ed'] = 'CIAP ED';
$string['typeciap_initial'] = 'CIAP initial';
$string['typeciap_quarterly_update_2'] = 'CIAP quarterly update 2';
$string['typeciap_quarterly_update_3'] = 'CIAP quarterly update 3';
$string['typeciap_quarterly_update_4'] = 'CIAP quarterly update 4';
$string['typecompletion_with_notes'] = 'Completion with notes';
$string['typecompletion_with_transcript'] = 'Completion with transcript';
$string['typecrucialconv'] = 'Crucial conversations';
$string['typegfg'] = 'Going for Gold';
$string['typehha'] = 'HHA';
$string['typemental_health_cert'] = 'Mental health certificate';
$string['typemeuterm'] = 'MEU term assessment';
$string['typenum_dc'] = 'NUM direct care';
$string['typenum_er'] = 'NUM education and research';
$string['typenum_ld'] = 'NUM leadership and development';
$string['typenum_ss'] = 'NUM system support';
$string['typepassport'] = 'Passport';
$string['typepeer'] = 'Peer review';
$string['typeportfolio'] = 'Portfolio';
$string['typestatement_of_attainment'] = 'Statement of attainment';
$string['typestatement_of_attendance'] = 'Statement of attendance';
$string['typestatement_of_attendance_multi'] = 'Statement of attendance (multiple sessions)';
$string['typetna'] = 'Training needs analysis';
$string['typewui_attendance'] = 'WUI attendance';

// Advanced life support certificate.
$string['advancedlifesupportprogram'] = 'Advanced life support / RRCD program';
$string['advguidelines'] = '- Australian Resuscitation Council (ARC) Guidelines 2010';
$string['advmanagingarrest'] = 'Managing the Adult Cardio-Respiratory Arrest';
$string['advrapidresponse'] = '- Rapid response to the deteriorating patient';
$string['advresuscitationdetails'] = '- Basic life support, defibrillation (AED and manual), synchronized cardioversion, external pacing, basic and advanced airway management skills and medications';
$string['advsimulatedscenarios'] = '- Simulated scenarios';
$string['advuseearlywarning'] = '- Use of an early warning and response systems';
$string['initialpracticalcompleted'] = 'Initial practical session completed';
$string['initialtheorycompleted'] = 'Initial theory completed';
$string['latestpracticalcompleted'] = 'Latest recertification practical completed';
$string['latesttheorycompleted'] = 'Latest recertification theory completed';
$string['recognisingclinicaldeterioration'] = 'Recognising and Responding to Clinical Deterioration';
$string['trainingassessmentincluded'] = 'Training & Assessment Included:';

// Continuous improvement action plan certificates.
$string['ciapaction'] = 'Action {$a}';
$string['ciapactionresponse'] = 'What is the action in response to?';
$string['ciapactionstatus'] = 'Action {$a->action} ({$a->status})';
$string['ciapagreedaction'] = 'What is the action your team has agreed to?';
$string['ciapcelebratesuccess'] = 'Don\'t forget to celebrate this success with your team.';
$string['ciapcloseout'] = 'Close out of your team\'s';
$string['ciapcloseoutstatus'] = 'Close out status: {$a}';
$string['ciapcongratulations'] = 'Congratulations!';
$string['ciapculturechampion'] = '2018 GFG Survey Culture Champion: {$a}';
$string['ciapimplementedby'] = 'Implemented by: {$a}';
$string['ciapnewactionadded'] = 'New action added';
$string['ciapnoaccess'] = 'You don\'t have access to any CIAPs.';
$string['ciapontrack2020'] = 'Are you on track to achieve this task by the commencement of the 2020 Going for Gold Survey? {$a}';
$string['ciapontrackduedate'] = 'Are you on track to achieve this task by the due date? {$a}';
$string['ciapq2wherewerewe'] = 'Q2 where were we at: {$a}';
$string['ciapq4wherearewe'] = 'Q4 where are we at? {$a}';
$string['ciapq4wherewerewe'] = 'Q4 where were we at: {$a}';
$string['ciapquarterlyupdate2'] = 'Quarterly Update 2 (1 October - 31 December)';
$string['ciapquarterlyupdate4'] = 'Quarterly Update 4 (1 April - 30 June)';
$string['ciapresearchprogram'] = 'What research program from the Staff Survey does this action link to?';
$string['ciapresponsible'] = 'Who will be responsible for implementing the action? {$a}';
$string['ciapstatuscomplete'] = 'Complete';
$string['ciapstatusinprogress'] = 'In progress';
$string['ciapstatusnolongerrequired'] = 'No longer required';
$string['ciapstatusnotyetstarted'] = 'Not yet started';
$string['ciapsurveyquestion'] = 'Survey question: {$a}';
$string['ciapsurveytitle'] = '2018 Going for Gold Staff Survey';
$string['ciaptitle'] = 'Continuous Improvement Action Plan';
$string['ciapupdatenotprovided'] = 'Update not provided';
$string['ciapwherearewe'] = 'Where are we at? {$a}';
$string['ciapwhynolongerrequired'] = 'Why is the action no longer required? {$a}';
$string['ciapwhynotontrack'] = 'Why aren\'t you on track? {$a}';
$string['ciapyear'] = '2018 / 2019';
$string['goingforgold'] = 'Going for Gold';
$string['noculturechampion'] = 'No culture champion';

// Going for Gold certificate.
$string['gfgactioncommitted'] = 'What is the action we have committed to?';
$string['gfgactioncongratulations'] = 'Congratulations on completing this action - make sure you celebrate this win with your team!';
$string['gfgactiondue'] = 'When is this action due?';
$string['gfgactionnumber'] = '{$a}.';
$string['gfgactionresponsible'] = 'Who is responsible for this action?';
$string['gfgactionupdatenotprovided'] = 'An update has not been provided for this action';
$string['gfgdue'] = '(due {$a})';
$string['gfgfurtherdetails'] = 'Further details available over the page';
$string['gfgplanpage'] = '{$a->id} {$a->name} - Page {$a->page}';
$string['gfgpromotesvalue'] = 'This action promotes the GCH value of {$a} within our work unit';
$string['gfgstatuscomplete'] = 'Complete';
$string['gfgstatusdate'] = '{$a->status} ({$a->date})';
$string['gfgstatusinprogress'] = 'In progress';
$string['gfgstatusnotrequired'] = 'No longer required';
$string['gfgstatusnotstarted'] = 'Not yet started';
$string['gfgstatusnoupdate'] = 'No update provided';
$string['gfgstatusvalue'] = 'Status: {$a}';
$string['gfgsummarytitle'] = '{$a} - Summary';
$string['gfgupdatenumber'] = 'Update {$a}';
$string['gfgupdatenumberdate'] = '{$a->update} ({$a->date})';

// Hand Hygiene Australia certificate.
$string['hha'] = 'HHA';
$string['hhaprojectmanager'] = 'HHA Project Manager';
$string['hhaworkshopcompletion'] = 'Has successfully completed a 5-hour HHA workshop and is now a recognised HHA compliance auditor and assessor.';

// Medical education unit term assessment.
$string['meutermappendiximprovement'] = 'Appendix {$a} - Areas for improvement';
$string['meutermappendixstrengths'] = 'Appendix {$a} - Strengths';
$string['meutermassessmentdate'] = 'Assessment date:';
$string['meutermassessmentfor'] = 'This form is being completed for:';
$string['meutermdirectorclinicaltraining'] = 'Director of Clinical Training';
$string['meutermdirectorcomments'] = 'Director of Clinical Training comments:';
$string['meutermdomain1'] = 'Domain 1: Science and scholarship - The {$a} as scientist and scholar';
$string['meutermdomain2'] = 'Domain 2: Clinical practice - The {$a} as practitioner';
$string['meutermdomain3'] = 'Domain 3: Health and society - The {$a} as a health advocate';
$string['meutermdomain4'] = 'Domain 4: Professionalism and leadership - The {$a} as a professional and leader';
$string['meutermelectronicagreement'] = 'This document was electronically agreed to by the above-named {$a->supervisor} on {$a->date}.';
$string['meutermipaprequired'] = 'IPAP is required for {$a}';
$string['meutermleveldetails'] = '{$a} details';
$string['meutermlevelname'] = '{$a} name';
$string['meutermnoipaprequired'] = 'No IPAP required';
$string['meutermregistrationnumber'] = 'AHPRA reg no:';
$string['meutermseeappendix'] = 'See appendix attached';
$string['meutermsharingauthorised'] = 'Sharing of this term assessment has been authorised by the {$a}.';
$string['meutermsharingnotauthorised'] = 'Sharing of this term assessment has not been authorised by the {$a}.';
$string['meutermsharingnotcompleted'] = 'Sharing of this term assessment has not been completed by the {$a}.';
$string['meutermtermdetails'] = 'Term details';
$string['meutermtermname'] = 'Term name:';
$string['meutermtermyear'] = '{$a->term} - {$a->year}';
$string['meutermtitle'] = 'Intern assessment - {$a}';
$string['meutermtrainingassessmentform'] = '{$a} training - term assessment form';
$string['meutermunitofterm'] = 'Unit of term:';
$string['rating'] = 'Rating';

// Mental health certificate.
$string['mentalhealth'] = 'Mental Health';
$string['mentalhealthtitle'] = 'Gold Coast Health Mental Health';

// Nurse unit manager learning plans.
$string['numdirectcare'] = 'Direct Care';
$string['numeducationresearch'] = 'Education and Research';
$string['numleadership'] = 'Leadership';
$string['numlearningplanintro'] = 'This learning plan is a point in time reflection of your own identified learning needs. As you progress and gain more experience your learning needs will change. You may go back and redo the orientation/onboarding gap analysis tool for a revised learning plan.';
$string['numnoactions'] = 'You have not selected any actions for this timeframe.';
$string['numsystemsupport'] = 'System Support';
$string['numtimeframe12plus'] = '12 months or more';
$string['numtimeframe1to3'] = '1-3 months';
$string['numtimeframe4to11'] = '4-11 months';

// Peer review certificate.
$string['peer'] = 'Peer';
$string['peeremail'] = 'Peer email';
$string['peername'] = 'Peer name';
$string['peernumber'] = 'Peer no';
$string['peerreviewsummaryfor'] = 'Peer review summary for';
$string['ratingexceedsexpectations'] = '3 - Exceeds Expectations';
$string['ratingmeetsexpectations'] = '2 - Meets Expectations';
$string['ratingopportunityimprovement'] = '1 - Opportunity for Improvement';

// Legacy portfolio certificate.
$string['portfolio_coursemanager'] = 'Manager support modules';
$string['portfolio_coursemanager_cont'] = 'Manager support modules (cont\'d)';

// Training needs analysis certificate.
$string['tnaactivities10'] = 'My 10% activities:';
$string['tnaactivities20'] = 'My 20% activities:';
$string['tnaactivities70'] = 'My 70% activities:';
$string['tnabuildrelationships'] = 'Build Relationships';
$string['tnabuildrelationshipsdescription'] = 'Shape and maximise relationships with colleagues, patients and the community.';
$string['tnabusinessenablers'] = 'Business Enablers';
$string['tnabusinessenablersdescription'] = 'Boost effective service delivery and champion change management.';
$string['tnacorecapabilityprofile'] = 'Core Capability Profile: {$a}';
$string['tnacriticalmarker'] = '> indicates the most critical capability for your role as identified by yourself';
$string['tnadevelopmentopportunities'] = 'My development opportunities: {$a}';
$string['tnadevelopmentprompt'] = 'Capture any development opportunities below, using the 70-20-10 model.';
$string['tnaleadershippeoplemanagement'] = 'Leadership and People Management';
$string['tnaleadershippeoplemanagementdescription'] = 'Inspire, engage and develop our people.';
$string['tnamodel10'] = '10% - Formal educational programs or courses';
$string['tnamodel20'] = '20% - Collaborative learning and learning from others';
$string['tnamodel70'] = '70% - Job related experiences';
$string['tnapersonalattributes'] = 'Personal Attributes';
$string['tnapersonalattributesdescription'] = 'Individual behaviours influenced by our values and ethical compass.';
$string['tnaratingalmostthere'] = 'I am almost there, I need a little more practice or support';
$string['tnaratingcanapply'] = 'I understand and can apply this concept';
$string['tnaratingcancoach'] = 'I understand and am able to coach others in this concept';
$string['tnaratingneedsdevelopment'] = 'I could do with development and support in this concept';
$string['tnaresultsfocused'] = 'Results Focused';
$string['tnaresultsfocuseddescription'] = 'Drive and influence successful organisational outcomes.';
$string['tnaresultsintro'] = 'Below are your Training Needs Analysis results, capturing which capabilities from the Core Capability Framework are important in your role and any opportunities for development.';
$string['tnaresultstitle'] = 'TNA Results - {$a}';
