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
require_once($CFG->dirroot.'/auth/kronosportal/auth.php');
require_once($CFG->dirroot.'/local/elisprogram/enrol/userset/moodleprofile/lib.php');

/**
 * Create kronos user.
 *
 * @throws moodle_exception.
 * @param object $userdata User data.
 * @return object User object.
 */
function kronosportal_create_user($userdata) {
    global $DB, $CFG;
    if (is_array($userdata)) {
        $userdata = (object)$userdata;
    }
    $userdata->confirmed = 1;
    $userdata->auth = 'kronosportal';
    if (empty($userdata->idnumber)) {
        $userdata->idnumber = $userdata->username;
    }
    if (empty($userdata->mnethostid)) {
        // If empty, we restrict to local users.
        $userdata->mnethostid = $CFG->mnet_localhost_id;
    }
    // Throws expection on error.
    $userid = user_create_user($userdata);

    $user = $DB->get_record('user', array('id' => $userid));
    // Assign custom fields.
    $userdata->id = $userid;
    profile_save_data($userdata);
    // Retrieve custom user fields.
    profile_load_data($user);
    cluster_profile_update_handler($user);
    return $user;
}

/**
 * Update kronos user.
 *
 * @throws moodle_exception.
 * @param object $userdata User data.
 * @return object User object.
 */
function kronosportal_update_user($userdata) {
    global $DB, $CFG;
    if (is_array($userdata)) {
        $userdata = (object)$userdata;
    }
    if (empty($userdata->id)) {
        throw new moodle_expection('webserviceerrorinvaliduser', 'auth_kronosportal');
    }
    $userid = $userdata->id;
    $userdata->auth = 'kronosportal';
    // Update Moodle user.
    // Throws expection on error.
    $user = $DB->get_record('user', array('id' => $userid));
    $solutionfield = "profile_field_".kronosportal_get_solutionfield();
    foreach (array("firstname", "lastname", $solutionfield, "password", "email", "city", "country") as $name) {
        $user->$name = $userdata->$name;
    }
    // Update user, update password only if there is a value for the password field.
    user_update_user($user, !empty($user->password));
    // Assign custom fields.
    profile_save_data($userdata);
    // Retrieve custom user fields.
    $user = $DB->get_record('user', array('id' => $userid));
    profile_load_data($user);
    cluster_profile_update_handler($user);
    return $user;
}

/**
 * Validate kronos user.
 *
 * @param object $user User data.
 * @param object $create True if user is being created.
 * @return string Returns success on passing validation on fail message string.
 */
function kronosportal_validate_user($user, $create = false) {
    if (!is_object($user)) {
        return 'invaliduser';
    }
    $solutionfield = "profile_field_".kronosportal_get_solutionfield();
    $fields = array("username", "firstname", "lastname", $solutionfield, "email");
    if ($create) {
        $fields[] = "password";
    }
    foreach ($fields as $name) {
        if (empty($user->$name)) {
            return 'missingdata';
        }
    }

    $auth = get_auth_plugin('kronosportal');

    if (!$create) {
        // Validation for updating a user.
        if (empty($user->id)) {
            return 'invaliduser';
        }
        // Check if the user's User Set exists, by searching for a User Set via a the Solution ID profile field.
        if (!$auth->user_solutionid_field_exists($user->id)) {
            return 'missingusersolutionfield';
        }
    }
    if (!kronosportal_is_user_userset_valid($auth, $user->$solutionfield)) {
        return 'invalidsolution';
    }
    if (kronosportal_is_user_userset_expired($auth, $user->$solutionfield)) {
        return 'expired';
    }
    return 'success';
}

/**
 * Check if the userset that userid is part of is expired or not.
 *
 * @param object $auth Authentication plugin object.
 * @param int|string $id User id or Solution id as a string.
 * @return boolean Returns true on expired false on userset is not expired.
 */
function kronosportal_is_user_userset_expired($auth, $id) {
    if (is_numeric($id)) {
        $usersolutionid = $auth->get_user_solution_id($id);
        if (empty($usersolutionid)) {
            return true;
        }
    } else {
        $usersolutionid = $id;
    }
    // Search for a User Set that contains a matching Solutions ID with the user logging in.  Kronos User Set Soultion Ids are meant to be unique.
    $usersetcontextandname = $auth->userset_solutionid_exists($usersolutionid);
    if (empty($usersetcontextandname)) {
        return true;
    }
    // Check if the User Set expiry and extension date are less than the current date.
    return !$auth->user_set_has_valid_subscription($usersolutionid, $usersetcontextandname->id, $usersetcontextandname->name);
}

/**
 * Check if the userset is valid.
 *
 * @param object $auth Authentication plugin object.
 * @param int|string $id User id or Solution id as a string.
 * @return boolean Returns true on expired false on userset is not expired.
 */
function kronosportal_is_user_userset_valid($auth, $id) {
    if (is_numeric($id)) {
        $usersolutionid = $auth->get_user_solution_id($id);
        if (empty($usersolutionid)) {
            return false;
        }
    } else {
        $usersolutionid = $id;
    }
    // Search for a User Set that contains a matching Solutions ID with the user logging in.  Kronos User Set Soultion Ids are meant to be unique.
    $usersetcontextandname = $auth->userset_solutionid_exists($usersolutionid);
    if (empty($usersetcontextandname)) {
        return false;
    }
    return true;
}

/**
 * Get shortname of Moodle user profile field containing solutionid.
 *
 * @return string Shortname of Moodle profile field for solutionid.
 */
function kronosportal_get_solutionfield() {
    global $DB;
    $config = get_config('auth_kronosportal');
    $result = $DB->get_record('user_info_field', array('id' => $config->user_field_solutionid));
    return $result->shortname;
}
