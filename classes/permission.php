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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission {

    /**
     * Managed user IDs, cached by manager user ID for the current request.
     *
     * @var array
     */
    protected static $manageduserids = array();

    /**
     * Reset request-level permission caches.
     *
     * @return void
     */
    public static function reset_caches() {
        self::$manageduserids = array();
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

        return in_array($targetuserid, self::get_managed_user_ids((int) $USER->id), true);
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

        foreach (self::get_managed_user_ids((int) $USER->id) as $userid) {
            if (!is_siteadmin($userid)) {
                return true;
            }
        }

        return false;
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
            $sqlparts = api::get_myusers_sql(
                (int) $USER->id,
                false,
                array(
                    role_permission::MANAGER,
                    role_permission::MANAGE_USERS,
                )
            );
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
     * Get all users recursively managed by a user through organisation roles.
     *
     * @param int $manageruserid manager user ID
     * @return int[]
     */
    protected static function get_managed_user_ids($manageruserid) {
        global $DB;

        if (array_key_exists($manageruserid, self::$manageduserids)) {
            return self::$manageduserids[$manageruserid];
        }

        $sqlparts = api::get_myusers_sql(
            $manageruserid,
            false,
            array(
                role_permission::MANAGER,
                role_permission::MANAGE_USERS,
            )
        );
        $joins = $sqlparts['joins'];
        $where = $sqlparts['where'];
        $params = $sqlparts['params'];

        $sql = "
            SELECT DISTINCT user.id

            FROM {user} user
                $joins

            WHERE $where
        ";

        self::$manageduserids[$manageruserid] = array_map('intval', $DB->get_fieldset_sql($sql, $params));

        return self::$manageduserids[$manageruserid];
    }
}
