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
 * Tests for certificate user access.
 *
 * @package    mod_certificate
 * @copyright  2026 Gold Coast Health
 * @author     Yucheng Zhu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_certificate_access_testcase extends advanced_testcase {

    /**
     * Load the certificate access function.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/certificate/lib.php');
    }

    /**
     * Create a certificate activity context.
     *
     * @return array course and context
     */
    private function create_certificate_context() {
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $certificate = $generator->create_instance(array('course' => $course->id));

        return array($course, context_module::instance($certificate->cmid));
    }

    /**
     * A user with view permission may access their own certificate.
     */
    public function test_user_can_access_own_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $this->assertNull(certificate_require_user_certificate_access($user->id, $context));
    }

    /**
     * A user with view permission may not access another user's certificate.
     */
    public function test_user_cannot_access_another_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $this->expectException(moodle_exception::class);
        certificate_require_user_certificate_access($otheruser->id, $context);
    }

    /**
     * Manage permission does not override certificate ownership.
     */
    public function test_manager_cannot_access_another_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $manager = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($manager->id, $course->id, 'editingteacher');
        $this->setUser($manager);

        $this->expectException(moodle_exception::class);
        certificate_require_user_certificate_access($otheruser->id, $context);
    }

    /**
     * Site administration does not override certificate ownership.
     */
    public function test_admin_cannot_access_another_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $otheruser = $this->getDataGenerator()->create_user();
        $this->setAdminUser();

        $this->expectException(moodle_exception::class);
        certificate_require_user_certificate_access($otheruser->id, $context);
    }
}
