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

use core_shutdown_manager;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Safely exposes a certificate owner through the legacy global user.
 *
 * @package    mod_certificate
 * @copyright  2026 Gold Coast Health
 * @author     Yucheng Zhu
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class temporary_user {

    /** @var stdClass user active before the temporary switch */
    private stdClass $requestinguser;

    /** @var bool whether the requesting user has been restored */
    private bool $restored = false;

    /**
     * Switch the global user without changing the user stored in the session.
     *
     * Legacy certificate types read the owner from $USER and may terminate the request while rendering. Detaching the
     * session reference prevents the owner from being persisted as the logged-in user, while the shutdown callback
     * covers paths that skip the caller's finally block.
     *
     * @param stdClass $targetuser certificate owner
     * @return void
     */
    public function __construct(stdClass $targetuser) {
        global $USER;

        $this->requestinguser = $USER;
        if ((int) $targetuser->id === (int) $USER->id) {
            $this->restored = true;
            return;
        }

        $sessionuser = isset($_SESSION['USER']) ? $_SESSION['USER'] : $this->requestinguser;
        core_shutdown_manager::register_function(array(__CLASS__, 'restore_user'), array($sessionuser));

        $temporaryuser = clone $targetuser;
        unset($temporaryuser->password, $temporaryuser->secret);

        // Break Moodle's session reference before exposing the certificate owner through the legacy global.
        unset($_SESSION['USER']);
        $_SESSION['USER'] = $sessionuser;
        $USER = $temporaryuser;
    }

    /**
     * Return the authenticated user who requested the certificate.
     *
     * @return stdClass
     */
    public function get_requesting_user() {
        return $this->requestinguser;
    }

    /**
     * Restore the authenticated user after certificate rendering.
     *
     * @return void
     */
    public function restore() {
        if ($this->restored) {
            return;
        }

        self::restore_user($this->requestinguser);
        $this->restored = true;
    }

    /**
     * Restore a user and relink it to the session when it is the session owner.
     *
     * This is public so Moodle's shutdown manager can restore requests terminated inside legacy certificate types.
     *
     * @param stdClass $user user to restore
     * @return void
     */
    public static function restore_user(stdClass $user) {
        global $USER;

        $sessionuser = isset($_SESSION['USER']) ? $_SESSION['USER'] : null;
        $USER = $user;

        // A nested temporary user must not replace the original session user.
        if (!$sessionuser || (int) $sessionuser->id === (int) $user->id) {
            $_SESSION['USER'] =& $GLOBALS['USER'];
        }
    }
}
