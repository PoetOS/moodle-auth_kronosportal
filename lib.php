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

define('AUTH_KRONOSPORTAL_COMP_NAME', 'auth_kronosportal');

require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

/**
 * Create kronos user.
 *
 * @throws moodle_exception.
 * @param object $userdata User data.
 * @return object User object.
 */
function kronosportal_create_user($userdata) {
    global $DB;
    if (!is_object($userdata)) {
        $userdata = (object)$userdata;
    }
    $userdata->auth = 'kronosportal';
    // Create Moodle user with out checking Moodle password policy.
    // Throws expection on error.
    $userid = user_create_user($userdata, false);
    $user = $DB->get_record('user', array('id' => $userid));
    // Assign custom fields.
    $userdata->id = $userid;
    profile_save_data($userdata);
    // Retrieve custom user fields.
    profile_load_data($user);
    return $user;
}

/**
 * Validate kronos user.
 *
 * @param object $user User data.
 * @return string Returns success on passing validation on fail message string.
 */
function kronosportal_validate_user($user) {
    if (!is_object($user)) {
        return 'invaliduser';
    }
    if (empty($user->id)) {
        return 'invaliduser';
    }
    if (kronosportal_is_user_userset_expired($user->id)) {
        return 'expired';
    }
    return 'success';
}

/**
 * Check if the userset that userid is part of is expired or not.
 *
 * @param int $userid User id.
 * @return boolean Returns true on expired false on userset is not expired.
 */
function kronosportal_is_user_userset_expired($userid) {
    return false;
}