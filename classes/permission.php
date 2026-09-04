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

namespace mod_certificate;

use context;
use context_module;
use moodle_exception;
use stdClass;
use tool_organisation\api;
use tool_organisation\local\type\role_permission;

defined('MOODLE_INTERNAL') || die();

/**
 * Certificate target-user permission checks.
 *
 * @package    mod_certificate
 * @copyright  2026 Gold Coast Health
 * @author     Yucheng Zhu
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission {

    public const CAP_VIEW_ANY = 'mod/certificate:viewany';

    /**
     * Managed-user SQL, cached by manager user ID for the current request.
     *
     * @var array<int, array{joins: string, where: string, params: array<string, int>, directreports: int[]}>
     */
    protected static array $manageduserssql = [];

    /**
     * Targeted managed-user checks, cached for the current request.
     *
     * @var array<string, bool>
     */
    protected static array $manageduserchecks = [];

    /**
     * Whether managers have at least one visible report, cached for the current request.
     *
     * @var array<int, bool>
     */
    protected static array $hasvisiblemanagedusers = [];

    /**
     * Reset request-level permission caches.
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$manageduserssql = [];
        self::$manageduserchecks = [];
        self::$hasvisiblemanagedusers = [];
    }

    /**
     * Check whether the current user can view a target user's certificate.
     *
     * @param context $context certificate activity context
     * @param int $targetuserid target user ID
     * @return bool
     */
    public static function can_view_user_certificate(context $context, int $targetuserid): bool {
        global $USER;

        if (!has_capability('mod/certificate:view', $context)) {
            return false;
        }

        if (is_siteadmin()) {
            return true;
        }

        if (is_siteadmin($targetuserid)) {
            return false;
        }

        if (static::is_facilitator($context)) {
            return true;
        }

        if ($targetuserid === (int)$USER->id) {
            return true;
        }

        return static::is_managed_user((int)$USER->id, $targetuserid);
    }

    /**
     * Require permission to view a target user's certificate.
     *
     * @param context $context certificate activity context
     * @param int $targetuserid target user ID
     * @return void
     * @throws moodle_exception if the target user is not visible
     */
    public static function require_view_user_certificate(context $context, int $targetuserid): void {
        require_capability('mod/certificate:view', $context);

        if (!static::can_view_user_certificate($context, $targetuserid)) {
            throw new moodle_exception('nopermissions', 'error', '', get_string('certificate:view', 'certificate'));
        }
    }

    /**
     * Get the requested certificate owner for the current request.
     *
     * @param context_module $context certificate activity context
     * @return array{int, stdClass} requested user ID and user record
     */
    public static function get_requested_user(context_module $context): array {
        global $DB, $USER;

        $userid = optional_param('userid', $USER->id, PARAM_INT);
        static::require_view_user_certificate($context, (int)$userid);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        return [$userid, $user];
    }

    /**
     * Check whether the current user can view at least one other user.
     *
     * @param context $context certificate activity context
     * @return bool
     */
    public static function can_view_other_users(context $context): bool {
        global $USER;

        if (!has_capability('mod/certificate:view', $context)) {
            return false;
        }

        if (is_siteadmin() || static::is_facilitator($context)) {
            return true;
        }

        return static::has_visible_managed_user((int)$USER->id);
    }

    /**
     * Get SQL that restricts a user ID field to users visible to the current user.
     *
     * The field expression is supplied by trusted plugin code and must not contain
     * request data.
     *
     * @param context $context certificate activity context
     * @param string $useridfield SQL field containing the target user ID
     * @return array{where: string, params: array<string, int>} SQL where fragment and parameters
     */
    public static function get_viewable_users_sql(context $context, string $useridfield): array {
        global $DB, $USER;

        if (!has_capability('mod/certificate:view', $context)) {
            return [
                'where' => "1 = 0",
                'params' => [],
            ];
        }

        if (is_siteadmin()) {
            return [
                'where' => '',
                'params' => [],
            ];
        }

        $params = [];
        if (static::is_facilitator($context)) {
            $where = '';

        } else {
            $sqlparts = static::get_managed_users_sql((int)$USER->id);
            $params = $sqlparts['params'];
            $params['certrequester'] = (int)$USER->id;
            $where = "
                (
                    $useridfield = :certrequester OR
                    $useridfield IN (
                        SELECT  DISTINCT u.id

                        FROM    {user} u
                                {$sqlparts['joins']}

                        WHERE   {$sqlparts['where']}
                    )
                )
            ";
        }

        $adminids = array_map('intval', array_keys(get_admins()));
        if (!empty($adminids)) {
            [$adminsql, $adminparams] = $DB->get_in_or_equal(
                $adminids,
                SQL_PARAMS_NAMED,
                'certadmin',
                false
            );
            $params += $adminparams;
            $adminwhere = "$useridfield $adminsql";
            $where = $where === '' ? $adminwhere : "
                $adminwhere AND
                $where
            ";
        }

        return [
            'where' => $where,
            'params' => $params,
        ];
    }

    /**
     * Check whether the current user is a facilitator.
     *
     * The dedicated capability identifies facilitators and is granted to
     * non-editing teacher roles by default.
     *
     * @param context $context certificate activity context
     * @return bool
     */
    protected static function is_facilitator(context $context): bool {
        return has_capability(static::CAP_VIEW_ANY, $context);
    }

    /**
     * Get SQL for users recursively managed through organisation roles.
     *
     * @param int $manageruserid manager user ID
     * @return array{joins: string, where: string, params: array<string, int>, directreports: int[]}
     */
    protected static function get_managed_users_sql(int $manageruserid): array {
        if (!array_key_exists($manageruserid, self::$manageduserssql)) {
            self::$manageduserssql[$manageruserid] = api::get_myusers_sql(
                $manageruserid,
                false,
                [
                    role_permission::MANAGER,
                    role_permission::MANAGE_USERS,
                ]
            );
        }

        return self::$manageduserssql[$manageruserid];
    }

    /**
     * Check whether a manager recursively manages one target user.
     *
     * @param int $manageruserid manager user ID
     * @param int $targetuserid target user ID
     * @return bool
     */
    protected static function is_managed_user(int $manageruserid, int $targetuserid): bool {
        global $DB;

        $cachekey = "$manageruserid:$targetuserid";
        if (array_key_exists($cachekey, self::$manageduserchecks)) {
            return self::$manageduserchecks[$cachekey];
        }

        $sqlparts = static::get_managed_users_sql($manageruserid);
        $joins = $sqlparts['joins'];
        $where = $sqlparts['where'];
        $params = $sqlparts['params'];
        $params['certtargetuserid'] = $targetuserid;

        $manageduserssql = "
            SELECT  1

            FROM    {user} u
                    $joins

            WHERE   u.id = :certtargetuserid AND
                    (
                        $where
                    )
        ";

        self::$manageduserchecks[$cachekey] = $DB->record_exists_sql($manageduserssql, $params);

        return self::$manageduserchecks[$cachekey];
    }

    /**
     * Check whether a manager has at least one non-admin report.
     *
     * @param int $manageruserid manager user ID
     * @return bool
     */
    protected static function has_visible_managed_user(int $manageruserid): bool {
        global $DB;

        if (array_key_exists($manageruserid, self::$hasvisiblemanagedusers)) {
            return self::$hasvisiblemanagedusers[$manageruserid];
        }

        $sqlparts = static::get_managed_users_sql($manageruserid);
        $joins = $sqlparts['joins'];
        $where = $sqlparts['where'];
        $params = $sqlparts['params'];

        $adminids = array_map('intval', array_keys(get_admins()));
        $adminwhere = '';
        if (!empty($adminids)) {
            [$adminsql, $adminparams] = $DB->get_in_or_equal(
                $adminids,
                SQL_PARAMS_NAMED,
                'certmanagedadmin',
                false
            );
            $params += $adminparams;
            $adminwhere = "u.id $adminsql AND";
        }

        $visiblemanageduserssql = "
            SELECT  1

            FROM    {user} u
                    $joins

            WHERE   $adminwhere
                    (
                        $where
                    )
        ";

        self::$hasvisiblemanagedusers[$manageruserid] = $DB->record_exists_sql($visiblemanageduserssql, $params);

        return self::$hasvisiblemanagedusers[$manageruserid];
    }
}
