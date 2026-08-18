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
     * Create a certificate activity for issue tests.
     *
     * @param string $certificatetype certificate type identifier
     * @return array course, certificate, and course module
     */
    private function create_certificate($certificatetype) {
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $certificate = $generator->create_instance(array(
            'course' => $course->id,
            'certificatetype' => $certificatetype,
        ));
        $cm = get_coursemodule_from_id('certificate', $certificate->cmid, 0, false, MUST_EXIST);

        return array($course, $certificate, $cm);
    }

    /**
     * Portfolio certificate types use the documented, case-sensitive portfolio_ prefix.
     */
    public function test_portfolio_certificate_type_detection() {
        $this->assertTrue(certificate_is_portfolio_type('portfolio_gch'));
        $this->assertTrue(certificate_is_portfolio_type('portfolio_example'));
        $this->assertFalse(certificate_is_portfolio_type('Portfolio'));
        $this->assertFalse(certificate_is_portfolio_type('portfolio'));
        $this->assertFalse(certificate_is_portfolio_type('my_portfolio_gch'));
    }

    /**
     * An authorised delegate may create a target user's portfolio issue on demand.
     */
    public function test_delegate_can_create_portfolio_issue_for_target() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('portfolio_gch');
        $target = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        $requestinguserid = (int) $USER->id;

        $issue = certificate_get_issue_for_view($course, $target, $certificate, $cm);

        $this->assertSame((int) $target->id, (int) $issue->userid);
        $this->assertSame($requestinguserid, (int) $USER->id);
        $this->assertSame(1, $DB->count_records('certificate_issues', array(
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        )));

        $secondissue = certificate_get_issue_for_view($course, $target, $certificate, $cm);

        $this->assertSame((int) $issue->id, (int) $secondissue->id);
        $this->assertSame(1, $DB->count_records('certificate_issues', array(
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        )));
    }

    /**
     * A delegated conventional certificate still requires an existing issue.
     */
    public function test_delegate_cannot_create_conventional_issue_for_target() {
        global $DB;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('A4_non_embedded');
        $target = $this->getDataGenerator()->create_user();
        $this->setAdminUser();

        try {
            certificate_get_issue_for_view($course, $target, $certificate, $cm);
            $this->fail('Expected a missing delegated certificate issue to be rejected.');
        } catch (moodle_exception $exception) {
            $this->assertSame('nocertificatesissued', $exception->errorcode);
        }

        $this->assertFalse($DB->record_exists('certificate_issues', array(
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        )));
    }

    /**
     * A delegate may view an existing conventional certificate issue.
     */
    public function test_delegate_can_view_existing_conventional_issue() {
        global $DB;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('A4_non_embedded');
        $target = $this->getDataGenerator()->create_user();
        $issueid = $DB->insert_record('certificate_issues', (object) array(
            'certificateid' => $certificate->id,
            'userid' => $target->id,
            'code' => 'existing-conventional-issue',
            'timecreated' => time(),
        ));
        $this->setAdminUser();

        $issue = certificate_get_issue_for_view($course, $target, $certificate, $cm);

        $this->assertSame((int) $issueid, (int) $issue->id);
        $this->assertSame(1, $DB->count_records('certificate_issues', array(
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        )));
    }

    /**
     * A user may still create their own conventional certificate issue.
     */
    public function test_user_can_create_own_conventional_issue() {
        global $DB;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('A4_non_embedded');
        $target = $this->getDataGenerator()->create_user();
        $this->setUser($target);

        $issue = certificate_get_issue_for_view($course, $target, $certificate, $cm);

        $this->assertSame((int) $target->id, (int) $issue->userid);
        $this->assertSame(1, $DB->count_records('certificate_issues', array(
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        )));
    }

    /**
     * A self-service GFG request continues to create the requesting user's issue record.
     */
    public function test_self_service_gfg_issue_is_owned_by_requester() {
        global $DB;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('GFG');
        $requester = $this->getDataGenerator()->create_user();
        $this->setUser($requester);

        $issue = certificate_get_issue_for_view($course, $requester, $certificate, $cm);

        $this->assertSame((int) $requester->id, (int) $issue->userid);
        $this->assertSame(1, $DB->count_records('certificate_issues', array(
            'certificateid' => $certificate->id,
            'userid' => $requester->id,
        )));
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
