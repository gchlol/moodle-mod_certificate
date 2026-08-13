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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions for mod_certificate (GS-725).
 *
 * @package     mod_certificate
 * @category    test
 * @copyright   2026 Gold Coast Health
 * @author      Jonas Sajonas
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_certificate extends behat_base {
    /**
     * Visits the certificate view page passing a target user's id as the userid URL param.
     *
     * @When /^I visit the "(?P<cert_string>[^"]*)" certificate view page passing the userid of "(?P<user_string>[^"]*)"$/
     * @param string $cert certificate activity name or idnumber
     * @param string $user target user's username or email
     */
    public function i_visit_certificate_view_passing_the_userid_of(string $cert, string $user): void {
        $cm = $this->get_cm_by_activity_name('certificate', $cert);
        $userid = $this->get_user_id_by_identifier($user);
        $url = new moodle_url('/mod/certificate/view.php', ['id' => $cm->id, 'userid' => $userid]);
        $this->execute('behat_general::i_visit', [$url]);
    }
}
