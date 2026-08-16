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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for Certificate local library functions.
 *
 * @package    mod_certificate
 * @copyright  2026 Gold Coast Health
 * @author     Yucheng Zhu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_certificate_locallib_testcase extends advanced_testcase {

    /**
     * Load the Certificate local library.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/certificate/locallib.php');
    }

    /**
     * Certificate email delivery uses the issue owner, not the logged-in delegate.
     */
    public function test_email_student_uses_issue_owner() {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $certificate = $generator->create_instance(array('course' => $course->id));
        $context = context_module::instance($certificate->cmid);
        $student = $this->getDataGenerator()->create_user();
        $delegate = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $issueid = $DB->insert_record('certificate_issues', (object) array(
            'certificateid' => $certificate->id,
            'userid' => $student->id,
            'code' => 'email-owner-test',
            'timecreated' => time(),
        ));
        $issue = $DB->get_record('certificate_issues', array('id' => $issueid), '*', MUST_EXIST);

        $this->setUser($delegate);
        $sink = $this->redirectEmails();

        $this->assertTrue(certificate_email_student(
            $course,
            $certificate,
            $issue,
            $context,
            'certificate contents',
            'certificate.pdf'
        ));

        $emails = $sink->get_messages();
        $this->assertCount(1, $emails);
        $this->assertSame($student->email, $emails[0]->to);
        $this->assertNotSame($delegate->email, $emails[0]->to);
        $sink->close();
    }
}
