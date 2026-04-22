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

/**
 * Event fired when a manager issues a certificate for another user via a URL parameter.
 *
 * @package     mod_certificate
 * @copyright   2026 Gold Coast Health
 * @author      Jonas Sajonas
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certificate\event;

use core\event\base;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a manager issues a certificate for another user via a URL parameter.
 */
class certificate_issued_via_url extends base {

    /**
     * Initialise event data.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'certificate_issues';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcertificateissuedviaurl', 'certificate');
    }

    /**
     * Non-localised event description with id info for logs.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' issued a certificate (issue id '{$this->objectid}')"
            . " for the user with id '{$this->relateduserid}' via a URL parameter.";
    }

    /**
     * URL to the certificate view page for this event.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/mod/certificate/view.php', array('id' => $this->contextinstanceid));
    }
}
