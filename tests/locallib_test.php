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

namespace mod_certificate;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/certificate/locallib.php');

/**
 * Tests for certificate_resolve_target_user() URL-param on-behalf issuance (GS-725).
 *
 * @package     mod_certificate
 * @category    test
 * @copyright   2026 Gold Coast Health
 * @author      Jonas Sajonas
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      ::certificate_resolve_target_user
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Create a course with a certificate instance.
     *
     * @return array [course, certificate, cm, context_module]
     */
    private function create_certificate(): array {
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $certificate = $generator->create_instance(['course' => $course->id, 'name' => 'Test Certificate']);
        $cm = get_coursemodule_from_instance('certificate', $certificate->id);
        $context = \context_module::instance($cm->id);

        return [$course, $certificate, $cm, $context];
    }

    /**
     * No userid param: caller handles the normal self-view path.
     */
    public function test_returns_null_when_no_userid(): void {
        $this->resetAfterTest();
        [$course, , , $context] = $this->create_certificate();
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($manager);

        $this->assertNull(\certificate_resolve_target_user($course, $context));
    }

    /**
     * userid matches the acting user: self-view path, no on-behalf issue.
     */
    public function test_returns_null_when_userid_is_self(): void {
        $this->resetAfterTest();
        [$course, , , $context] = $this->create_certificate();
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($manager);
        $_GET['userid'] = $manager->id;

        $this->assertNull(\certificate_resolve_target_user($course, $context));
    }

    /**
     * Manager with manage cap and an enrolled target: returns that target user.
     */
    public function test_returns_target_user_when_manager_and_enrolled(): void {
        $this->resetAfterTest();
        [$course, , , $context] = $this->create_certificate();
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($manager);
        $_GET['userid'] = $student->id;

        $result = \certificate_resolve_target_user($course, $context);
        $this->assertNotNull($result);
        $this->assertEquals($student->id, $result->id);
    }

    /**
     * Acting user lacks mod/certificate:manage: access denied.
     */
    public function test_throws_required_capability_without_manage(): void {
        $this->resetAfterTest();
        [$course, , , $context] = $this->create_certificate();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $other = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $_GET['userid'] = $other->id;

        $this->expectException(\required_capability_exception::class);
        \certificate_resolve_target_user($course, $context);
    }

    /**
     * Target user exists but is not enrolled: usernotenrolled.
     */
    public function test_throws_usernotenrolled_when_target_not_enrolled(): void {
        $this->resetAfterTest();
        [$course, , , $context] = $this->create_certificate();
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $target = $this->getDataGenerator()->create_user();
        $this->setUser($manager);
        $_GET['userid'] = $target->id;

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/not enrolled/');
        \certificate_resolve_target_user($course, $context);
    }

    /**
     * Non-existent userid: MUST_EXIST throws.
     */
    public function test_throws_when_userid_nonexistent(): void {
        $this->resetAfterTest();
        [$course, , , $context] = $this->create_certificate();
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($manager);
        $_GET['userid'] = 9999999;

        $this->expectException(\dml_missing_record_exception::class);
        \certificate_resolve_target_user($course, $context);
    }
}
