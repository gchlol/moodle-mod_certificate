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

namespace mod_certificate\local;

use context_module;
use mod_certificate\permission;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Certificate issue operations.
 *
 * @package    mod_certificate
 * @copyright  2026 Gold Coast Health
 * @author     Yucheng Zhu
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issue {

    /**
     * Checks whether a certificate type is a site-specific portfolio implementation.
     *
     * Portfolio implementations follow the portfolio_ prefix naming convention.
     * The legacy Portfolio support directory is deliberately excluded.
     *
     * @param string $certificatetype certificate type identifier
     * @return bool
     */
    public static function is_portfolio_type(string $certificatetype): bool {
        return str_starts_with($certificatetype, 'portfolio_');
    }

    /**
     * Gets the issue record used to render a certificate for a target user.
     *
     * The caller must authorise access to the target user before calling this method. Users may create their own
     * issue record, and portfolio certificates may be created on demand for an authorised target. Other certificate
     * types require an existing issue record when viewed by somebody else.
     *
     * @param stdClass $course course containing the certificate activity
     * @param stdClass $user target user
     * @param stdClass $certificate certificate instance
     * @param stdClass $cm course module
     * @return stdClass certificate issue record
     * @throws moodle_exception when a delegated certificate cannot be viewed or issued
     */
    public static function get_for_view(
        stdClass $course,
        stdClass $user,
        stdClass $certificate,
        stdClass $cm
    ): stdClass {
        global $DB, $USER;

        $isowncertificate = (int)$user->id === (int)$USER->id;
        $isportfolio = static::is_portfolio_type($certificate->certificatetype);

        if ($isowncertificate) {
            return certificate_get_issue($course, $user, $certificate, $cm);
        }

        $certissue = $DB->get_record(
            'certificate_issues',
            ['userid' => $user->id, 'certificateid' => $certificate->id]
        );

        if ($certissue) {
            return $certissue;
        }

        if (!$isportfolio) {
            throw new moodle_exception('nocertificatesissued', 'certificate');
        }

        $context = context_module::instance($cm->id);
        // Check the owner before issuing so a delegate cannot bypass the required course time.
        if (
            $certificate->requiredtime &&
            !has_capability('mod/certificate:manage', $context, $user->id) &&
            certificate_get_course_time($course->id, $user->id) < ($certificate->requiredtime * 60)
        ) {
            $a = new stdClass();
            $a->requiredtime = $certificate->requiredtime;
            throw new moodle_exception('requiredtimenotmet', 'certificate', '', $a);
        }

        return certificate_get_issue($course, $user, $certificate, $cm);
    }

    /**
     * Get the SQL conditions that restrict the issue report to visible users.
     *
     * @param stdClass $cm the course module
     * @param bool $groupmode are we in group mode?
     * @param string $useridfield SQL field containing the user ID
     * @return array{conditionssql: string, params: array<string, int>, isempty: bool} SQL fragment, params, and
     *     whether the result set is empty
     */
    public static function get_visible_report_conditions(stdClass $cm, bool $groupmode, string $useridfield): array {
        global $DB, $USER;

        $context = context_module::instance($cm->id);
        $conditionssql = '';
        $conditionsparams = [];
        $emptyresult = ['conditionssql' => '', 'params' => [], 'isempty' => true];

        $visibility = permission::get_viewable_users_sql($context, $useridfield);
        $visibilityconditionssql = '';
        if ($visibility['where'] !== '') {
            $visibilityconditionssql = " AND
                    (
                        {$visibility['where']}
                    )";
            $conditionsparams += $visibility['params'];
        }

        $visibleresult = [
            'conditionssql' => $visibilityconditionssql,
            'params' => $conditionsparams,
            'isempty' => false,
        ];

        // Organisation managers, facilitators, and admins use the target-user policy
        // across groups. Group filtering remains an additional restriction for users
        // whose scope is limited to themselves.
        if (!$groupmode || permission::can_view_other_users($context)) {
            return $visibleresult;
        }

        $canaccessallgroups = has_capability('moodle/site:accessallgroups', $context);
        $currentgroup = groups_get_activity_group($cm);

        // If we are viewing all participants and the user does not have access to all groups then return nothing.
        if (!$currentgroup && !$canaccessallgroups) {
            return $emptyresult;
        }

        if (!$currentgroup) {
            return $visibleresult;
        }

        if (!$canaccessallgroups) {
            // Guest users do not belong to any groups.
            if (isguestuser()) {
                return $emptyresult;
            }

            // Check that the user belongs to the group we are viewing.
            $usersgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);
            if (
                empty($usersgroups) ||
                !isset($usersgroups[$currentgroup])
            ) {
                return $emptyresult;
            }
        }

        $groupusers = array_keys(groups_get_members($currentgroup, 'u.*'));
        if (empty($groupusers)) {
            return $emptyresult;
        }

        [$groupsql, $groupparams] = $DB->get_in_or_equal($groupusers, SQL_PARAMS_NAMED, 'grp');
        $conditionssql .= " AND
            $useridfield $groupsql";
        $conditionsparams += $groupparams;

        $conditionssql .= $visibilityconditionssql;

        return [
            'conditionssql' => $conditionssql,
            'params' => $conditionsparams,
            'isempty' => false,
        ];
    }

    /**
     * Count issued certificates visible to the current user.
     *
     * @param int $certificateid certificate instance ID
     * @param stdClass $cm course module
     * @param bool $groupmode are we in group mode?
     * @return int
     */
    public static function count_visible(int $certificateid, stdClass $cm, bool $groupmode = false): int {
        global $DB;

        $reportconditions = static::get_visible_report_conditions($cm, $groupmode, "user.id");
        if ($reportconditions['isempty']) {
            return 0;
        }

        $params = $reportconditions['params'] + ['certificateid' => $certificateid];
        // Count in the database instead of loading every visible issue and user into PHP.
        $countsql = "
            SELECT  COUNT(DISTINCT user.id)

            FROM    {user} user
                    JOIN {certificate_issues} certificate_issues ON
                        certificate_issues.userid = user.id

            WHERE   user.deleted = 0 AND
                    certificate_issues.certificateid = :certificateid
                    {$reportconditions['conditionssql']}
        ";

        return $DB->count_records_sql($countsql, $params);
    }
}
