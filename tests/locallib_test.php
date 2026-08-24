<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

use mod_certificate\local\temporary_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for Certificate local library functions.
 *
 * @package    mod_certificate
 * @copyright  2026 Gold Coast Health
 * @author     Yucheng Zhu
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
     * @param int $requiredtime required course time in minutes
     * @return array course, certificate, and course module
     */
    private function create_certificate(string $certificatetype, int $requiredtime = 0) {
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $certificate = $generator->create_instance([
            'course' => $course->id,
            'certificatetype' => $certificatetype,
            'requiredtime' => $requiredtime,
        ]);
        $cm = get_coursemodule_from_id('certificate', $certificate->cmid, 0, false, MUST_EXIST);

        return [$course, $certificate, $cm];
    }

    /**
     * Portfolio certificate types use the documented, case-sensitive portfolio_ prefix.
     *
     * @return void
     */
    public function test_portfolio_certificate_type_detection() {
        $this->assertTrue(certificate_is_portfolio_type('portfolio_gch'));
        $this->assertTrue(certificate_is_portfolio_type('portfolio_example'));
        $this->assertFalse(certificate_is_portfolio_type('Portfolio'));
        $this->assertFalse(certificate_is_portfolio_type('portfolio'));
        $this->assertFalse(certificate_is_portfolio_type('my_portfolio_gch'));
    }

    /**
     * Rendering as another user must not replace the user stored in the session.
     *
     * @return void
     */
    public function test_temporary_global_user_does_not_change_session_user() {
        global $USER;

        $this->resetAfterTest(true);
        $requester = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        $target->password = 'target-password-hash';
        $target->secret = 'target-secret';
        $this->setUser($requester);

        $selfcontext = new temporary_user($requester);
        $this->assertSame($USER, $_SESSION['USER']);
        $selfcontext->apply();
        $this->assertSame($USER, $_SESSION['USER']);
        $selfcontext->restore();

        $usercontext = new temporary_user($target);
        $this->assertSame((int)$requester->id, (int)$USER->id);
        $this->assertSame((int)$requester->id, (int)$_SESSION['USER']->id);
        try {
            $usercontext->apply();
            $this->assertSame((int)$target->id, (int)$USER->id);
            $this->assertSame((int)$requester->id, (int)$_SESSION['USER']->id);
            $this->assertFalse(property_exists($USER, 'password'));
            $this->assertFalse(property_exists($USER, 'secret'));

        } finally {
            $usercontext->restore();
        }

        $this->assertSame((int)$requester->id, (int)$USER->id);
        $this->assertSame((int)$requester->id, (int)$_SESSION['USER']->id);
        $USER->firstname = 'Restored requester';
        $this->assertSame($USER->firstname, $_SESSION['USER']->firstname);
    }

    /**
     * Teacher notifications exclude the certificate owner, not the logged-in delegate.
     *
     * @return void
     */
    public function test_get_teachers_excludes_certificate_owner() {
        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('portfolio_gch');
        $owner = $this->getDataGenerator()->create_user();
        $delegate = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($owner->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($delegate->id, $course->id, 'editingteacher');
        $this->setUser($delegate);

        $teachers = certificate_get_teachers($certificate, $owner, $course, $cm);

        $this->assertArrayNotHasKey($owner->id, $teachers);
        $this->assertArrayHasKey($delegate->id, $teachers);
    }

    /**
     * An authorised delegate may create a target user's portfolio issue on demand.
     *
     * @return void
     */
    public function test_delegate_can_create_portfolio_issue_for_target() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('portfolio_gch');
        $target = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        $requestinguserid = (int)$USER->id;

        $issue = certificate_get_issue_for_view($course, $target, $certificate, $cm);

        $this->assertSame((int)$target->id, (int)$issue->userid);
        $this->assertSame($requestinguserid, (int)$USER->id);
        $this->assertSame(1, $DB->count_records('certificate_issues', [
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        ]));

        $secondissue = certificate_get_issue_for_view($course, $target, $certificate, $cm);

        $this->assertSame((int)$issue->id, (int)$secondissue->id);
        $this->assertSame(1, $DB->count_records('certificate_issues', [
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        ]));
    }

    /**
     * A delegate cannot create a portfolio issue before its owner meets the required course time.
     *
     * @return void
     */
    public function test_delegate_cannot_create_portfolio_issue_before_required_time() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('portfolio_gch', 1);
        $target = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($target->id, $course->id, 'student');
        $this->setAdminUser();
        $requestinguserid = (int)$USER->id;

        try {
            certificate_get_issue_for_view($course, $target, $certificate, $cm);
            $this->fail('Expected the target user\'s required course time to be enforced.');

        } catch (moodle_exception $exception) {
            $this->assertSame('requiredtimenotmet', $exception->errorcode);
        }

        $this->assertSame($requestinguserid, (int)$USER->id);
        $this->assertFalse($DB->record_exists('certificate_issues', [
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        ]));
    }

    /**
     * A delegate may still view a portfolio issue created before its required course time changes.
     *
     * @return void
     */
    public function test_delegate_can_view_existing_portfolio_issue_before_required_time() {
        global $DB;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('portfolio_gch', 1);
        $target = $this->getDataGenerator()->create_user();
        $issueid = $DB->insert_record('certificate_issues', (object)[
            'certificateid' => $certificate->id,
            'userid' => $target->id,
            'code' => 'existing-portfolio-issue',
            'timecreated' => time(),
        ]);
        $this->setAdminUser();

        $issue = certificate_get_issue_for_view($course, $target, $certificate, $cm);

        $this->assertSame((int)$issueid, (int)$issue->id);
        $this->assertSame(1, $DB->count_records('certificate_issues', [
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        ]));
    }

    /**
     * A delegated conventional certificate still requires an existing issue.
     *
     * @return void
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

        $this->assertFalse($DB->record_exists('certificate_issues', [
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        ]));
    }

    /**
     * A delegate may view an existing conventional certificate issue.
     *
     * @return void
     */
    public function test_delegate_can_view_existing_conventional_issue() {
        global $DB;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('A4_non_embedded');
        $target = $this->getDataGenerator()->create_user();
        $issueid = $DB->insert_record('certificate_issues', (object)[
            'certificateid' => $certificate->id,
            'userid' => $target->id,
            'code' => 'existing-conventional-issue',
            'timecreated' => time(),
        ]);
        $this->setAdminUser();

        $issue = certificate_get_issue_for_view($course, $target, $certificate, $cm);

        $this->assertSame((int)$issueid, (int)$issue->id);
        $this->assertSame(1, $DB->count_records('certificate_issues', [
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        ]));
    }

    /**
     * A user may still create their own conventional certificate issue.
     *
     * @return void
     */
    public function test_user_can_create_own_conventional_issue() {
        global $DB;

        $this->resetAfterTest(true);
        list($course, $certificate, $cm) = $this->create_certificate('A4_non_embedded');
        $target = $this->getDataGenerator()->create_user();
        $this->setUser($target);

        $issue = certificate_get_issue_for_view($course, $target, $certificate, $cm);

        $this->assertSame((int)$target->id, (int)$issue->userid);
        $this->assertSame(1, $DB->count_records('certificate_issues', [
            'certificateid' => $certificate->id,
            'userid' => $target->id,
        ]));
    }

    /**
     * Certificate email delivery uses the issue owner, not the logged-in delegate.
     *
     * @return void
     */
    public function test_email_student_uses_issue_owner() {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $certificate = $generator->create_instance(['course' => $course->id]);
        $context = context_module::instance($certificate->cmid);
        $student = $this->getDataGenerator()->create_user();
        $delegate = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $issueid = $DB->insert_record('certificate_issues', (object)[
            'certificateid' => $certificate->id,
            'userid' => $student->id,
            'code' => 'email-owner-test',
            'timecreated' => time(),
        ]);
        $issue = $DB->get_record('certificate_issues', ['id' => $issueid], '*', MUST_EXIST);

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

    /**
     * Award notifications identify the certificate owner without changing the logged-in user.
     *
     * @return void
     */
    public function test_award_notifications_use_issue_owner() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $otheremail = 'certificate-observer@example.test';
        $certificate = $generator->create_instance([
            'course' => $course->id,
            'emailteachers' => 1,
            'emailothers' => $otheremail,
        ]);
        $cm = get_coursemodule_from_id('certificate', $certificate->cmid, 0, false, MUST_EXIST);
        $owner = $this->getDataGenerator()->create_user([
            'firstname' => 'Certificate',
            'lastname' => 'Owner',
            'email' => 'certificate-owner@example.com',
        ]);
        $delegate = $this->getDataGenerator()->create_user([
            'firstname' => 'Certificate',
            'lastname' => 'Delegate',
            'email' => 'certificate-delegate@example.com',
        ]);
        $this->getDataGenerator()->enrol_user($delegate->id, $course->id, 'editingteacher');
        $issue = (object)[
            'certificateid' => $certificate->id,
            'userid' => $owner->id,
        ];

        $this->setUser($delegate);
        $sink = $this->redirectEmails();
        certificate_email_teachers($course, $certificate, $issue, $cm);
        certificate_email_others($course, $certificate, $issue, $cm);

        $messages = [];
        foreach ($sink->get_messages() as $message) {
            $messages[$message->to] = $message;
        }

        $sink->close();

        $this->assertArrayHasKey($delegate->email, $messages);
        $this->assertArrayHasKey($otheremail, $messages);
        foreach ($messages as $message) {
            $this->assertStringContainsString(fullname($owner), $message->subject);
            $this->assertStringNotContainsString(fullname($delegate), $message->subject);
            $this->assertStringContainsString(fullname($owner), $message->header);
            $this->assertStringNotContainsString(fullname($delegate), $message->header);
        }

        $this->assertSame((int)$delegate->id, (int)$USER->id);
        $this->assertSame((int)$delegate->id, (int)$_SESSION['USER']->id);
    }
}
