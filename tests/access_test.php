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

use mod_certificate\permission;
use tool_organisation\persistent\assignment;
use tool_organisation\persistent\hierarchy;
use tool_organisation\persistent\level;
use tool_organisation\persistent\level_data;
use tool_organisation\persistent\position;
use tool_organisation\persistent\role;

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
     * Reset request-level permission caches between tests.
     */
    protected function setUp(): void {
        parent::setUp();
        permission::reset_caches();
    }

    /**
     * Load the certificate access function.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/certificate/locallib.php');
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/mod/certificate/db/upgrade.php');
    }

    /**
     * Create a certificate activity context.
     *
     * @return array course, context, certificate, and course module
     */
    private function create_certificate_context() {
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_certificate');
        $certificate = $generator->create_instance(array('course' => $course->id));
        $cm = get_coursemodule_from_id('certificate', $certificate->cmid, 0, false, MUST_EXIST);

        return array($course, context_module::instance($certificate->cmid), $certificate, $cm);
    }

    /**
     * Create an issued certificate record for a user.
     *
     * @param int $certificateid certificate instance ID
     * @param int $userid target user ID
     * @param int $timecreated issue time used for deterministic sorting
     * @return void
     */
    private function create_certificate_issue($certificateid, $userid, $timecreated) {
        global $DB;

        $DB->insert_record('certificate_issues', (object) array(
            'certificateid' => $certificateid,
            'userid' => $userid,
            'code' => "certificate-code-$userid",
            'timecreated' => $timecreated,
        ));
    }

    /**
     * Assert that both the permission policy and its enforcement wrapper allow a target user.
     *
     * @param context_module $context certificate activity context
     * @param int $userid target user ID
     * @return void
     */
    private function assert_can_view_certificate($context, $userid) {
        $this->assertTrue(has_capability('mod/certificate:view', $context));
        $this->assertTrue(permission::can_view_user_certificate($context, $userid));
        certificate_require_user_certificate_access($userid, $context);
    }

    /**
     * Assert that both the permission policy and its enforcement wrapper deny a target user.
     *
     * @param context_module $context certificate activity context
     * @param int $userid target user ID
     * @return void
     */
    private function assert_cannot_view_certificate($context, $userid) {
        $this->assertTrue(has_capability('mod/certificate:view', $context));
        $this->assertFalse(permission::can_view_user_certificate($context, $userid));

        try {
            certificate_require_user_certificate_access($userid, $context);
            $this->fail('Expected certificate access to be denied.');
        } catch (moodle_exception $exception) {
            $this->assertSame('nopermissions', $exception->errorcode);
        }
    }

    /**
     * Assign a user to an organisation level.
     *
     * @param stdClass $user user to assign
     * @param int $levelid organisation level ID
     * @param string $suffix unique record suffix
     * @return void
     */
    private function assign_user_to_level($user, $levelid, $suffix) {
        $position = new position(0, (object) array(
            'name' => "Certificate position $suffix",
            'idnumber' => "certificate-position-$suffix",
        ));
        $position->create();

        $assignment = new assignment(0, (object) array(
            'userid' => $user->id,
            'positionid' => $position->get('id'),
            'assignnu' => "certificate-assignment-$suffix",
        ));
        $assignment->create();

        $leveldata = new level_data(0, (object) array(
            'levelid' => $levelid,
            'assignid' => $assignment->get('id'),
        ));
        $leveldata->create();
    }

    /**
     * Create direct, indirect, and unrelated organisation assignments.
     *
     * @param stdClass $manager manager user
     * @param stdClass $directreport direct report user
     * @param stdClass $indirectreport indirect report user
     * @param stdClass $unrelateduser unrelated user
     * @return void
     */
    private function create_manager_hierarchy($manager, $directreport, $indirectreport, $unrelateduser) {
        $suffix = uniqid('', true);
        $hierarchy = new hierarchy(0, (object) array(
            'idnumber' => "certificate-hierarchy-$suffix",
            'name' => 'Certificate test hierarchy',
            'type' => hierarchy::TYPE_ASSIGNMENT,
        ));
        $hierarchy->create();

        $managerlevel = new level(0, (object) array(
            'hierarchyid' => $hierarchy->get('id'),
            'name' => 'Manager level',
            'idnumber' => "certificate-manager-$suffix",
        ));
        $managerlevel->create();

        $directlevel = new level(0, (object) array(
            'hierarchyid' => $hierarchy->get('id'),
            'parent' => $managerlevel->get('id'),
            'name' => 'Direct report level',
            'idnumber' => "certificate-direct-$suffix",
        ));
        $directlevel->create();

        $indirectlevel = new level(0, (object) array(
            'hierarchyid' => $hierarchy->get('id'),
            'parent' => $directlevel->get('id'),
            'name' => 'Indirect report level',
            'idnumber' => "certificate-indirect-$suffix",
        ));
        $indirectlevel->create();

        $unrelatedlevel = new level(0, (object) array(
            'hierarchyid' => $hierarchy->get('id'),
            'name' => 'Unrelated level',
            'idnumber' => "certificate-unrelated-$suffix",
        ));
        $unrelatedlevel->create();

        $managerrole = new role(0, (object) array(
            'levelid' => $managerlevel->get('id'),
            'userid' => $manager->id,
            'type' => role::TYPE_USER,
            'manager' => 1,
        ));
        $managerrole->create();

        $this->assign_user_to_level($directreport, $directlevel->get('id'), "direct-$suffix");
        $this->assign_user_to_level($indirectreport, $indirectlevel->get('id'), "indirect-$suffix");
        $this->assign_user_to_level($unrelateduser, $unrelatedlevel->get('id'), "unrelated-$suffix");
    }

    /**
     * Mark a user as a site administrator for a test.
     *
     * @param stdClass $user user to make an administrator
     * @return void
     */
    private function make_site_admin($user) {
        global $CFG;

        $adminids = array_filter(explode(',', $CFG->siteadmins));
        $adminids[] = $user->id;
        $CFG->siteadmins = implode(',', array_unique($adminids));
        set_config('siteadmins', $CFG->siteadmins);
    }

    /**
     * Create a role with Certificate facilitator permission.
     *
     * @return int role ID
     */
    private function create_facilitator_role() {
        return $this->getDataGenerator()->create_role(array(
            'mod/certificate:view' => 'allow',
            'mod/certificate:viewallnonadmincertificates' => 'allow',
        ));
    }

    /**
     * Create a teacher-archetype role without facilitator permission.
     *
     * @param string $archetype role archetype
     * @return int role ID
     */
    private function create_nonfacilitator_teacher_role($archetype = 'teacher') {
        return $this->getDataGenerator()->create_role(array(
            'archetype' => $archetype,
            'mod/certificate:view' => 'allow',
        ));
    }

    /**
     * A staff member may access their own certificate.
     */
    public function test_staff_can_view_own_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $staff = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($staff->id, $course->id, 'student');
        $this->setUser($staff);

        $this->assert_can_view_certificate($context, $staff->id);
        $this->assertFalse(permission::can_view_other_users($context));
    }

    /**
     * A staff member may not access another user's certificate.
     */
    public function test_staff_cannot_view_another_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $staff = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($staff->id, $course->id, 'student');
        $this->setUser($staff);

        $this->assert_cannot_view_certificate($context, $otheruser->id);
    }

    /**
     * Target-user permission does not replace the view capability.
     */
    public function test_view_capability_is_required() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertFalse(permission::can_view_user_certificate($context, $user->id));
        $this->expectException(required_capability_exception::class);
        certificate_require_user_certificate_access($user->id, $context);
    }

    /**
     * Facilitator permission does not replace the base view capability.
     */
    public function test_facilitator_capability_does_not_replace_view_capability() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $facilitator = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role(array(
            'mod/certificate:viewallnonadmincertificates' => 'allow',
        ));
        $this->getDataGenerator()->enrol_user($facilitator->id, $course->id, $roleid);
        $this->setUser($facilitator);

        $this->assertTrue(has_capability('mod/certificate:viewallnonadmincertificates', $context));
        $this->assertFalse(permission::can_view_user_certificate($context, $facilitator->id));
        $this->expectException(required_capability_exception::class);
        certificate_require_user_certificate_access($facilitator->id, $context);
    }

    /**
     * A manager may access their own, direct reports', and indirect reports' certificates.
     */
    public function test_manager_can_view_recursive_reports() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $manager = $this->getDataGenerator()->create_user();
        $directreport = $this->getDataGenerator()->create_user();
        $indirectreport = $this->getDataGenerator()->create_user();
        $unrelateduser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($manager->id, $course->id, 'student');
        $this->create_manager_hierarchy($manager, $directreport, $indirectreport, $unrelateduser);
        $this->setUser($manager);

        $this->assert_can_view_certificate($context, $manager->id);
        $this->assert_can_view_certificate($context, $directreport->id);
        $this->assert_can_view_certificate($context, $indirectreport->id);
        $this->assert_cannot_view_certificate($context, $unrelateduser->id);
        $this->assertTrue(permission::can_view_other_users($context));
    }

    /**
     * A manager may not access a site administrator's certificate.
     */
    public function test_manager_cannot_view_admin_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $manager = $this->getDataGenerator()->create_user();
        $directreport = $this->getDataGenerator()->create_user();
        $indirectreport = $this->getDataGenerator()->create_user();
        $unrelateduser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($manager->id, $course->id, 'student');
        $this->create_manager_hierarchy($manager, $directreport, $indirectreport, $unrelateduser);
        $this->make_site_admin($directreport);
        $this->setUser($manager);

        $this->assert_cannot_view_certificate($context, $directreport->id);
    }

    /**
     * Managed site administrators do not give a manager access to other-user reports.
     */
    public function test_manager_with_only_admin_reports_cannot_view_other_users() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $manager = $this->getDataGenerator()->create_user();
        $directreport = $this->getDataGenerator()->create_user();
        $indirectreport = $this->getDataGenerator()->create_user();
        $unrelateduser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($manager->id, $course->id, 'student');
        $this->create_manager_hierarchy($manager, $directreport, $indirectreport, $unrelateduser);
        $this->make_site_admin($directreport);
        $this->make_site_admin($indirectreport);
        $this->setUser($manager);

        $this->assertFalse(permission::can_view_other_users($context));
    }

    /**
     * A user with Certificate facilitator permission may access every non-admin certificate.
     */
    public function test_facilitator_can_view_non_admin_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $facilitator = $this->getDataGenerator()->create_user();
        $staff = $this->getDataGenerator()->create_user();
        $facilitatorroleid = $this->create_facilitator_role();
        $this->getDataGenerator()->enrol_user($facilitator->id, $course->id, $facilitatorroleid);
        $this->setUser($facilitator);

        $this->assertTrue(has_capability('mod/certificate:viewallnonadmincertificates', $context));
        $this->assert_can_view_certificate($context, $staff->id);
        $this->assertTrue(permission::can_view_other_users($context));
    }

    /**
     * A facilitator may not access a site administrator's certificate.
     */
    public function test_facilitator_cannot_view_admin_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $facilitator = $this->getDataGenerator()->create_user();
        $admin = $this->getDataGenerator()->create_user();
        $facilitatorroleid = $this->create_facilitator_role();
        $this->getDataGenerator()->enrol_user($facilitator->id, $course->id, $facilitatorroleid);
        $this->make_site_admin($admin);
        $this->setUser($facilitator);

        $this->assert_cannot_view_certificate($context, $admin->id);
    }

    /**
     * Certificate management permission does not make an editing teacher a facilitator.
     */
    public function test_editing_teacher_without_facilitator_capability_cannot_view_another_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $editingteacher = $this->getDataGenerator()->create_user();
        $staff = $this->getDataGenerator()->create_user();
        $roleid = $this->create_nonfacilitator_teacher_role('editingteacher');
        $this->getDataGenerator()->enrol_user($editingteacher->id, $course->id, $roleid);
        $this->setUser($editingteacher);

        $this->assertTrue(has_capability('mod/certificate:manage', $context));
        $this->assertFalse(has_capability('mod/certificate:viewallnonadmincertificates', $context));
        $this->assert_cannot_view_certificate($context, $staff->id);
    }

    /**
     * A non-editing teacher archetype alone does not grant facilitator access.
     */
    public function test_nonediting_teacher_without_facilitator_capability_only_sees_self() {
        $this->resetAfterTest(true);
        list($course, $context, $certificate, $cm) = $this->create_certificate_context();
        $teacher = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $roleid = $this->create_nonfacilitator_teacher_role();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $roleid);
        $this->create_certificate_issue($certificate->id, $teacher->id, 1);
        $this->create_certificate_issue($certificate->id, $otheruser->id, 2);
        $this->setUser($teacher);

        $this->assertFalse(has_capability('mod/certificate:viewallnonadmincertificates', $context));
        $this->assertFalse(permission::can_view_user_certificate($context, $otheruser->id));
        $this->assertFalse(permission::can_view_other_users($context));
        $users = certificate_get_issues($certificate->id, 'ci.timecreated ASC', 0, $cm);
        $this->assertSame(array((int) $teacher->id), array_keys($users));
    }

    /**
     * A site administrator may access any user's certificate, including another administrator.
     */
    public function test_admin_can_view_any_certificate() {
        $this->resetAfterTest(true);
        list($course, $context) = $this->create_certificate_context();
        $staff = $this->getDataGenerator()->create_user();
        $otheradmin = $this->getDataGenerator()->create_user();
        $this->make_site_admin($otheradmin);
        $this->setAdminUser();

        $this->assert_can_view_certificate($context, $staff->id);
        $this->assert_can_view_certificate($context, $otheradmin->id);
        $this->assertTrue(permission::can_view_other_users($context));
    }

    /**
     * A manager's report contains only their recursive reports and paginates after filtering.
     */
    public function test_manager_report_is_filtered_and_paginated() {
        $this->resetAfterTest(true);
        list($course, $context, $certificate, $cm) = $this->create_certificate_context();
        $manager = $this->getDataGenerator()->create_user();
        $directreport = $this->getDataGenerator()->create_user();
        $indirectreport = $this->getDataGenerator()->create_user();
        $unrelateduser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($manager->id, $course->id, 'student');
        $this->create_manager_hierarchy($manager, $directreport, $indirectreport, $unrelateduser);
        $this->create_certificate_issue($certificate->id, $manager->id, 1);
        $this->create_certificate_issue($certificate->id, $directreport->id, 2);
        $this->create_certificate_issue($certificate->id, $indirectreport->id, 3);
        $this->create_certificate_issue($certificate->id, $unrelateduser->id, 4);
        $this->setUser($manager);

        $firstpage = certificate_get_issues($certificate->id, 'ci.timecreated ASC', 0, $cm, 0, 2);
        $secondpage = certificate_get_issues($certificate->id, 'ci.timecreated ASC', 0, $cm, 1, 2);

        $this->assertSame(array((int) $manager->id, (int) $directreport->id), array_keys($firstpage));
        $this->assertSame(array((int) $indirectreport->id), array_keys($secondpage));
        $this->assertSame(3, certificate_count_issues($certificate->id, $cm));
    }

    /**
     * A facilitator's report contains non-admin users and excludes site admins.
     */
    public function test_facilitator_report_excludes_admins() {
        $this->resetAfterTest(true);
        list($course, $context, $certificate, $cm) = $this->create_certificate_context();
        $facilitator = $this->getDataGenerator()->create_user();
        $staff = $this->getDataGenerator()->create_user();
        $admin = $this->getDataGenerator()->create_user();
        $facilitatorroleid = $this->create_facilitator_role();
        $this->getDataGenerator()->enrol_user($facilitator->id, $course->id, $facilitatorroleid);
        $this->make_site_admin($admin);
        $this->create_certificate_issue($certificate->id, $facilitator->id, 1);
        $this->create_certificate_issue($certificate->id, $staff->id, 2);
        $this->create_certificate_issue($certificate->id, $admin->id, 3);
        $this->setUser($facilitator);

        $users = certificate_get_issues($certificate->id, 'ci.timecreated ASC', SEPARATEGROUPS, $cm);

        $this->assertSame(array((int) $facilitator->id, (int) $staff->id), array_keys($users));
        $this->assertSame(2, certificate_count_issues($certificate->id, $cm));
    }

    /**
     * A staff member's report contains only their own certificate.
     */
    public function test_staff_report_only_contains_self() {
        $this->resetAfterTest(true);
        list($course, $context, $certificate, $cm) = $this->create_certificate_context();
        $staff = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($staff->id, $course->id, 'student');
        $this->create_certificate_issue($certificate->id, $staff->id, 1);
        $this->create_certificate_issue($certificate->id, $otheruser->id, 2);
        $this->setUser($staff);

        $users = certificate_get_issues($certificate->id, 'ci.timecreated ASC', 0, $cm);

        $this->assertSame(array((int) $staff->id), array_keys($users));
        $this->assertSame(1, certificate_count_issues($certificate->id, $cm));
    }

    /**
     * A site administrator's report contains ordinary users and other site administrators.
     */
    public function test_admin_report_contains_all_users() {
        $this->resetAfterTest(true);
        list($course, $context, $certificate, $cm) = $this->create_certificate_context();
        $staff = $this->getDataGenerator()->create_user();
        $otheradmin = $this->getDataGenerator()->create_user();
        $this->make_site_admin($otheradmin);
        $this->create_certificate_issue($certificate->id, $staff->id, 1);
        $this->create_certificate_issue($certificate->id, $otheradmin->id, 2);
        $this->setAdminUser();

        $users = certificate_get_issues($certificate->id, 'ci.timecreated ASC', 0, $cm);

        $this->assertSame(array((int) $staff->id, (int) $otheradmin->id), array_keys($users));
        $this->create_certificate_issue($certificate->id, $staff->id, 3);
        $this->assertSame(2, certificate_count_issues($certificate->id, $cm));
    }

    /**
     * The upgrade registers the new capability and migrates only the established facilitator role.
     */
    public function test_upgrade_migrates_legacy_facilitator_permissions() {
        global $DB;

        $this->resetAfterTest(true);
        list(, $context) = $this->create_certificate_context();
        $systemcontext = context_system::instance();
        $facilitatorroleid = $this->getDataGenerator()->create_role(array(
            'shortname' => 'facilitator',
        ));
        $teacherroleid = $this->getDataGenerator()->create_role(array(
            'shortname' => 'legacy-certificate-teacher',
        ));

        assign_capability(
            'mod/certificate:manage',
            CAP_ALLOW,
            $facilitatorroleid,
            $systemcontext->id
        );
        assign_capability(
            'mod/certificate:manage',
            CAP_PREVENT,
            $facilitatorroleid,
            $context->id
        );
        assign_capability(
            'mod/certificate:manage',
            CAP_ALLOW,
            $teacherroleid,
            $systemcontext->id
        );

        $DB->delete_records('role_capabilities', array(
            'capability' => 'mod/certificate:viewallnonadmincertificates',
        ));
        $DB->delete_records('capabilities', array(
            'name' => 'mod/certificate:viewallnonadmincertificates',
        ));
        set_config('version', 2023061502, 'mod_certificate');

        xmldb_certificate_upgrade(2023061502);

        $this->assertSame(CAP_ALLOW, (int) $DB->get_field('role_capabilities', 'permission', array(
            'roleid' => $facilitatorroleid,
            'contextid' => $systemcontext->id,
            'capability' => 'mod/certificate:viewallnonadmincertificates',
        ), MUST_EXIST));
        $this->assertSame(CAP_PREVENT, (int) $DB->get_field('role_capabilities', 'permission', array(
            'roleid' => $facilitatorroleid,
            'contextid' => $context->id,
            'capability' => 'mod/certificate:viewallnonadmincertificates',
        ), MUST_EXIST));
        $this->assertFalse($DB->record_exists('role_capabilities', array(
            'roleid' => $teacherroleid,
            'capability' => 'mod/certificate:viewallnonadmincertificates',
        )));
        $this->assertTrue($DB->record_exists('capabilities', array(
            'name' => 'mod/certificate:viewallnonadmincertificates',
        )));
    }

    /**
     * The upgrade preserves an explicitly configured facilitator override.
     */
    public function test_upgrade_preserves_existing_facilitator_override() {
        global $DB;

        $this->resetAfterTest(true);
        list(, $context) = $this->create_certificate_context();
        $systemcontext = context_system::instance();
        $facilitatorroleid = $this->getDataGenerator()->create_role(array(
            'shortname' => 'facilitator',
        ));

        assign_capability(
            'mod/certificate:manage',
            CAP_ALLOW,
            $facilitatorroleid,
            $systemcontext->id
        );
        assign_capability(
            'mod/certificate:manage',
            CAP_PREVENT,
            $facilitatorroleid,
            $context->id
        );
        assign_capability(
            'mod/certificate:viewallnonadmincertificates',
            CAP_PROHIBIT,
            $facilitatorroleid,
            $systemcontext->id
        );
        set_config('version', 2023061503, 'mod_certificate');

        xmldb_certificate_upgrade(2023061503);

        $this->assertSame(CAP_PROHIBIT, (int) $DB->get_field('role_capabilities', 'permission', array(
            'roleid' => $facilitatorroleid,
            'contextid' => $systemcontext->id,
            'capability' => 'mod/certificate:viewallnonadmincertificates',
        ), MUST_EXIST));
        $this->assertSame(CAP_PREVENT, (int) $DB->get_field('role_capabilities', 'permission', array(
            'roleid' => $facilitatorroleid,
            'contextid' => $context->id,
            'capability' => 'mod/certificate:viewallnonadmincertificates',
        ), MUST_EXIST));
    }
}
