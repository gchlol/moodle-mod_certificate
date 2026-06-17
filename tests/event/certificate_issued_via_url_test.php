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

namespace mod_certificate\event;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/certificate/locallib.php');

/**
 * Tests for the certificate_issued_via_url event (GS-725).
 *
 * @package     mod_certificate
 * @category    test
 * @copyright   2026 Gold Coast Health
 * @author      Jonas Sajonas
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_certificate\event\certificate_issued_via_url
 */
final class certificate_issued_via_url_test extends \advanced_testcase {
    /**
     * Create a course, a manager, an enrolled student and an issued certificate.
     *
     * @return array [manager, student, certificate, cm, context_module, issue]
     */
    private function create_issue(): array {
        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $certificate = $generator->create_instance(['course' => $course->id, 'name' => 'Test Certificate']);
        $cm = get_coursemodule_from_instance('certificate', $certificate->id);
        $context = \context_module::instance($cm->id);

        $emailsink = $this->redirectEmails();
        $issue = \certificate_get_issue($course, $student, $certificate, $cm);
        $emailsink->close();

        return [$manager, $student, $certificate, $cm, $context, $issue];
    }

    /**
     * Build the event for an issued certificate.
     *
     * @param \context_module $context
     * @param \stdClass $issue
     * @param \stdClass $manager
     * @param \stdClass $student
     * @return certificate_issued_via_url
     */
    private function make_event(\context_module $context, \stdClass $issue, \stdClass $manager,
            \stdClass $student): certificate_issued_via_url {
        return certificate_issued_via_url::create([
            'objectid' => $issue->id,
            'relateduserid' => $student->id,
            'userid' => $manager->id,
            'context' => $context,
        ]);
    }

    /**
     * Event triggers cleanly and passes core event validation.
     */
    public function test_event_triggered(): void {
        $this->resetAfterTest();
        [$manager, $student, , , $context, $issue] = $this->create_issue();
        $event = $this->make_event($context, $issue, $manager, $student);

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(certificate_issued_via_url::class, reset($events));
        $this->assertDebuggingNotCalled();
    }

    /**
     * Event carries the expected data.
     */
    public function test_event_data(): void {
        $this->resetAfterTest();
        [$manager, $student, , , $context, $issue] = $this->create_issue();
        $event = $this->make_event($context, $issue, $manager, $student);

        $this->assertEquals($issue->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals($manager->id, $event->userid);
        $this->assertEquals($context->id, $event->contextid);
        $this->assertSame('c', $event->crud);
        $this->assertEquals(certificate_issued_via_url::LEVEL_TEACHING, $event->edulevel);
        $this->assertSame('certificate_issues', $event->objecttable);
    }

    /**
     * get_url() points at the certificate view page for the module.
     */
    public function test_event_get_url(): void {
        $this->resetAfterTest();
        [$manager, $student, , , $context, $issue] = $this->create_issue();
        $event = $this->make_event($context, $issue, $manager, $student);

        $expected = new \moodle_url('/mod/certificate/view.php', ['id' => $context->instanceid]);
        $this->assertEquals($expected->out(), $event->get_url()->out());
    }

    /**
     * get_name() resolves the localised event name.
     */
    public function test_event_get_name(): void {
        $this->assertEquals(
            get_string('eventcertificateissuedviaurl', 'certificate'),
            certificate_issued_via_url::get_name()
        );
    }

    /**
     * get_description() returns a string referencing the issuer, issue and target.
     */
    public function test_event_get_description(): void {
        $this->resetAfterTest();
        [$manager, $student, , , $context, $issue] = $this->create_issue();
        $event = $this->make_event($context, $issue, $manager, $student);

        $description = $event->get_description();
        $this->assertIsString($description);
        $this->assertStringContainsString((string) $manager->id, $description);
        $this->assertStringContainsString((string) $student->id, $description);
        $this->assertStringContainsString((string) $issue->id, $description);
    }

    /**
     * Full on-behalf chain (resolver -> issue -> event) writes the row and fires the event,
     * mirroring view.php without an HTTP request.
     */
    public function test_on_behalf_chain_creates_issue_and_event(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $certificate = $generator->create_instance(['course' => $course->id, 'name' => 'Test Certificate']);
        $cm = get_coursemodule_from_instance('certificate', $certificate->id);
        $context = \context_module::instance($cm->id);

        $this->setUser($manager);
        $_GET['userid'] = $student->id;

        $target = \certificate_resolve_target_user($course, $context);
        $this->assertEquals($student->id, $target->id);

        $emailsink = $this->redirectEmails();
        $eventsink = $this->redirectEvents();
        $issue = \certificate_get_issue($course, $target, $certificate, $cm);
        certificate_issued_via_url::create([
            'objectid' => $issue->id,
            'relateduserid' => $target->id,
            'userid' => $manager->id,
            'context' => $context,
        ])->trigger();
        $events = $eventsink->get_events();
        $eventsink->close();
        $emailsink->close();

        $this->assertTrue($DB->record_exists('certificate_issues', ['id' => $issue->id, 'userid' => $student->id]));
        $this->assertCount(1, $events);
        $this->assertInstanceOf(certificate_issued_via_url::class, reset($events));
    }
}
