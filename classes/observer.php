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

/**
 * Kronos virtual machine request web service.
 *
 * @package    auth_kronosportal
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

class auth_kronosportal_observer {

    /**
     * Observer for when a user is logged in. Session id is set to allow deletion of token on logout or a webservice logout.
     *
     * @param event $event Event passed by core
     * @return void
     */
    public static function user_loggedin($event) {
        global $USER, $DB;
        $token = optional_param('token', '', PARAM_RAW);
        if (!empty($token)) {
            $tokenrecords = $DB->get_record('kronosportal_tokens', array('token' => $token));
            if ($tokenrecords) {
                $tokenrecords->sid = session_id();
                $tokenrecords->userid = $USER->id;
                $DB->update_record('kronosportal_tokens', $tokenrecords);
            }
        }
    }
}
