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

use context_module;
use moodle_exception;
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

    /**
     * Managed-user SQL, cached by manager user ID for the current request.
     *
     * @var array
     */
    protected static $manageduserssql = array();

    /**
     * Targeted managed-user checks, cached for the current request.
     *
     * @var array
     */
    protected static $manageduserchecks = array();

    /**
     * Whether managers have at least one visible report, cached for the current request.
     *
     * @var array
     */
    protected static $hasvisiblemanagedusers = array();

    /**
     * Reset request-level permission caches.
     *
     * @return void
     */
    public static function reset_caches() {
        self::$manageduserssql = array();
        self::$manageduserchecks = array();
        self::$hasvisiblemanagedusers = array();
    }

    /**
     * Check whether the current user can view a target user's certificate.
     *
     * @param context_module $context certificate activity context
     * @param int $targetuserid target user ID
     * @return bool
     */
    public static function can_view_user_certificate(context_module $context, $targetuserid) {
        global $USER;

        $targetuserid = (int) $targetuserid;

        if (!has_capability('mod/certificate:view', $context)) {
            return false;
        }

        if (is_siteadmin()) {
            return true;
        }

        if (is_siteadmin($targetuserid)) {
            return false;
        }

        if (self::is_facilitator($context)) {
            return true;
        }

        if ($targetuserid === (int) $USER->id) {
            return true;
        }

        return self::is_managed_user((int) $USER->id, $targetuserid);
    }

    /**
     * Require permission to view a target user's certificate.
     *
     * @param context_module $context certificate activity context
     * @param int $targetuserid target user ID
     * @return void
     * @throws moodle_exception if the target user is not visible
     */
    public static function require_view_user_certificate(context_module $context, $targetuserid) {
        require_capability('mod/certificate:view', $context);

        if (!self::can_view_user_certificate($context, $targetuserid)) {
            throw new moodle_exception('nopermissions', 'error', '',
                get_string('certificate:view', 'certificate'));
        }
    }

    /**
     * Check whether the current user can view at least one other user.
     *
     * @param context_module $context certificate activity context
     * @return bool
     */
    public static function can_view_other_users(context_module $context) {
        global $USER;

        if (!has_capability('mod/certificate:view', $context)) {
            return false;
        }

        if (is_siteadmin() || self::is_facilitator($context)) {
            return true;
        }

        return self::has_visible_managed_user((int) $USER->id);
    }

    /**
     * Get SQL that restricts a user ID field to users visible to the current user.
     *
     * The field expression is supplied by trusted plugin code and must not contain
     * request data.
     *
     * @param context_module $context certificate activity context
     * @param string $useridfield SQL field containing the target user ID
     * @return array SQL where fragment and parameters
     */
    public static function get_viewable_users_sql(context_module $context, $useridfield) {
        global $DB, $USER;

        if (!has_capability('mod/certificate:view', $context)) {
            return array(
                'where' => '1 = 0',
                'params' => array(),
            );
        }

        if (is_siteadmin()) {
            return array(
                'where' => '',
                'params' => array(),
            );
        }

        $params = array();
        if (self::is_facilitator($context)) {
            $where = '';
        } else {
            $sqlparts = self::get_managed_users_sql((int) $USER->id);
            $params = $sqlparts['params'];
            $params['certrequester'] = (int) $USER->id;
            $where = "($useridfield = :certrequester OR $useridfield IN (
                          SELECT DISTINCT u.id
                            FROM {user} u
                                 {$sqlparts['joins']}
                           WHERE {$sqlparts['where']}
                      ))";
        }

        $adminids = array_map('intval', array_keys(get_admins()));
        if (!empty($adminids)) {
            list($adminsql, $adminparams) = $DB->get_in_or_equal(
                $adminids,
                SQL_PARAMS_NAMED,
                'certadmin',
                false
            );
            $params += $adminparams;
            $adminwhere = "$useridfield $adminsql";
            $where = $where === '' ? $adminwhere : "$where AND $adminwhere";
        }

        return array(
            'where' => $where,
            'params' => $params,
        );
    }

    /**
     * Check whether the current user is a facilitator.
     *
     * The dedicated capability distinguishes facilitators from other users
     * whose roles may happen to use the non-editing teacher archetype.
     *
     * @param context_module $context certificate activity context
     * @return bool
     */
    protected static function is_facilitator(context_module $context) {
        return has_capability('mod/certificate:viewallnonadmincertificates', $context);
    }

    /**
     * Get SQL for users recursively managed through organisation roles.
     *
     * @param int $manageruserid manager user ID
     * @return array
     */
    protected static function get_managed_users_sql($manageruserid) {
        if (!array_key_exists($manageruserid, self::$manageduserssql)) {
            self::$manageduserssql[$manageruserid] = api::get_myusers_sql(
                $manageruserid,
                false,
                array(
                    role_permission::MANAGER,
                    role_permission::MANAGE_USERS,
                )
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
    protected static function is_managed_user($manageruserid, $targetuserid) {
        global $DB;

        $cachekey = "$manageruserid:$targetuserid";
        if (array_key_exists($cachekey, self::$manageduserchecks)) {
            return self::$manageduserchecks[$cachekey];
        }

        $sqlparts = self::get_managed_users_sql($manageruserid);
        $joins = $sqlparts['joins'];
        $where = $sqlparts['where'];
        $params = $sqlparts['params'];
        $params['certtargetuserid'] = $targetuserid;

        $sql = "
            SELECT 1
              FROM {user} u
                   $joins
             WHERE u.id = :certtargetuserid
               AND ($where)
        ";

        self::$manageduserchecks[$cachekey] = $DB->record_exists_sql($sql, $params);

        return self::$manageduserchecks[$cachekey];
    }

    /**
     * Check whether a manager has at least one non-admin report.
     *
     * @param int $manageruserid manager user ID
     * @return bool
     */
    protected static function has_visible_managed_user($manageruserid) {
        global $DB;

        if (array_key_exists($manageruserid, self::$hasvisiblemanagedusers)) {
            return self::$hasvisiblemanagedusers[$manageruserid];
        }

        $sqlparts = self::get_managed_users_sql($manageruserid);
        $joins = $sqlparts['joins'];
        $where = $sqlparts['where'];
        $params = $sqlparts['params'];

        $adminids = array_map('intval', array_keys(get_admins()));
        $adminwhere = '';
        if (!empty($adminids)) {
            list($adminsql, $adminparams) = $DB->get_in_or_equal(
                $adminids,
                SQL_PARAMS_NAMED,
                'certmanagedadmin',
                false
            );
            $params += $adminparams;
            $adminwhere = "AND u.id $adminsql";
        }

        $sql = "
            SELECT 1
              FROM {user} u
                   $joins
             WHERE ($where)
                   $adminwhere
        ";

        self::$hasvisiblemanagedusers[$manageruserid] = $DB->record_exists_sql($sql, $params);

        return self::$hasvisiblemanagedusers[$manageruserid];
    }
}
