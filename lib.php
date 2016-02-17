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
require_once(elispm::lib('data/userset.class.php'));
require_once(elispm::lib('data/usermoodle.class.php'));
require_once(elispm::file('enrol/userset/manual/lib.php'));

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
        if (isset($userdata->$name)) {
            $user->$name = $userdata->$name;
        }
    }

    $updatepassword = false;
    // Update user, update password only if there is a value for the password field.
    if (isset($userdata->password) && !empty($userdata->password)) {
        $updatepassword = true;
    }
    // Assign custom fields.
    profile_save_data($userdata);
    // Call to update user must be placed after profile_save_data to ensure custom profile fields are saved before trigger is made.
    user_update_user($user, $updatepassword);

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
 * @param string $usersolutionid Solution id as a string.
 * @return boolean Returns true on expired false on userset is not expired.
 */
function kronosportal_is_user_userset_expired($auth, $usersolutionid) {
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
 * @param string $usersolutionid Solution id as a string.
 * @return boolean Returns true on expired false on userset is not expired.
 */
function kronosportal_is_user_userset_valid($auth, $usersolutionid) {
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

/**
 * This function applies the Kronos business rules to user profile data.
 * @param array $data Array of portal field names (key) and field data (value).
 * @return mixed An array containing the formatted data.  False is return is an error is encountered.
 */
function kronosportal_apply_kronos_business_rules($data) {
    $newusr = new stdClass();

    // Set the username.
    $conditions = isset($data['solutionid']) && isset($data['personnumber'])
        && isset($data['firstname']) && isset($data['lastname']);

    $nonemptyconditions = !empty($data['solutionid']) && !empty($data['personnumber'])
        && !empty($data['firstname']) && !empty($data['lastname']);

    if ($conditions && $nonemptyconditions) {
        $newusr->username = 'wfc'.strtolower($data['solutionid'].$data['personnumber']);
        $newusr->username = clean_param($newusr->username, PARAM_USERNAME);
        $newusr->password = "pwd".strrev($data['personnumber']).strrev($data['solutionid']);
        $newusr->email = $newusr->username.'@wfc.kronos.com';
        $newusr->firstname = $data['firstname'];
        $newusr->lastname = $data['lastname'];
        $newusr->country = isset($data['country']) ? $data['country'] : '';
        $newusr->lang = 'en';
        if (isset($data['learningpath'])) {
            $newusr->learningpath = ('-R' === substr($data['learningpath'], -2)) ? substr_replace($data['learningpath'], '', -2, 2) : $data['learningpath'];
            $newusr->restricted = ('-R' === substr($data['learningpath'], -2)) ? '1' : '0';
        }
        $newusr->personnumber = $data['personnumber'];
        $newusr->solutionid = $data['solutionid'];
    } else {
        return false;
    }

    return (array)$newusr;
}

/**
 * This function updates the standard profile data of a user coming from the WFC portal.
 * @param object $muser A Moodle user object retrieved from the mdl_user table.
 * @param object $data A WFC user object obtained from (@see kronosportal_apply_kronos_business_rules())
 */
function kronosportal_sync_standard_wfc_profile_fields($muser, $data) {
    $muser->password = $data->password;
    $muser->firstname = $data->firstname;
    $muser->lastname = $data->lastname;
    $muser->country = $data->country;
    $muser->lang = $data->lang;
}

/**
 * This function returns an array of Moodle profile fields (key) and protal profile values (value).
 * The mapping is taking from the authentication configuration.
 * @todo add PHPUnit test.
 * @param object $muserdata A Moodle user object.
 * @param array $wfcuserdata A WFC user array.
 * @param boolean $update Set to true when updating record.
 * @return object A Moodle profile fields (property) and protarl profile values (value).
 */
function kronosportal_sync_user_profile_to_portal_profile($muserdata, $wfcuserdata, $update = false) {
    $config = get_config('auth_kronosportal');

    $updatefields = array();
    foreach ((array)$config as $moodlefieldname => $value) {
        if (false !== strpos($moodlefieldname, 'update_profile_field_')) {
            $moodlefieldname = preg_replace('/update_profile_field_/', 'profile_field_', $moodlefieldname);
            $updatefields[$moodlefieldname] = $value;
        }
    }

    foreach ((array)$config as $moodlefieldname => $wfcfieldname) {
        if (false !== strpos($moodlefieldname, 'profile_field_')) {
            $lowercase = strtolower($wfcfieldname);
            if (!isset($muserdata->$moodlefieldname) || !isset($wfcuserdata[$lowercase])) {
                continue;
            }
            if (!$update || $update && !empty($updatefields[$moodlefieldname])) {
                $muserdata->$moodlefieldname = clean_param($wfcuserdata[$lowercase], PARAM_ALPHANUMEXT);
            } else {
                unset($muserdata->$moodlefieldname);
            }
        }
    }

    return $muserdata;
}
