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
 * Kronos portal authentication.
 *
 * @package    auth_kronosportal
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

if (!isset($_SERVER['HTTPS']) || 'off' == $_SERVER['HTTPS']) {
    die('A connection using SSL is required.');
}

require('../../config.php');
require_once('lib.php');

global $DB, $PAGE;

$wfcsolid = '';
$wfcpnum = '';
$wfcfname = '';
$wfclname = '';
$wfccountry = '';
$wfclp = '';
$missingparameters = 'solutionid,personnumber,firstname,lastname';
$assigntolearningpath = false;
$userset = null;

// This block of code is needd to handle POST variables from WFC portal sites, that contain with different case characters as there is not standard.
foreach ($_POST as $paramname => $paramvalue) {
    switch (strtolower($paramname)) {
        case 'solutionid':
            $wfcsolid = clean_param($paramvalue, PARAM_ALPHANUMEXT);
            $missingparameters = str_replace(strtolower($paramname), '', $missingparameters);
            break;
        case 'personnumber':
            $wfcpnum = clean_param($paramvalue, PARAM_ALPHANUMEXT);
            $missingparameters = str_replace(strtolower($paramname), '', $missingparameters);
            break;
        case 'firstname':
            $wfcfname = clean_param($paramvalue, PARAM_ALPHANUMEXT);
            $missingparameters = str_replace(strtolower($paramname), '', $missingparameters);
            break;
        case 'lastname':
            $wfclname = clean_param($paramvalue, PARAM_ALPHANUMEXT);
            $missingparameters = str_replace(strtolower($paramname), '', $missingparameters);
            break;
        case 'country':
            $wfccountry = clean_param($paramvalue, PARAM_ALPHA);
            $missingparameters = str_replace(strtolower($paramname), '', $missingparameters);
            break;
        case 'learningpath':
            $wfclp = clean_param($paramvalue, PARAM_ALPHANUMEXT);
            $missingparameters = str_replace(strtolower($paramname), '', $missingparameters);
            break;
    }
}

if (empty($wfcsolid) || empty($wfcpnum) || empty($wfcfname) || empty($wfclname)) {
    print_error('missingparam', 'auth_kronosportal', '', trim($missingparameters, ','));
}

$continueurl = new moodle_url("{$CFG->httpswwwroot}/index.php");
$continuestringurl = $continueurl->out();
$PAGE->set_url($continueurl);
$context = context_system::instance();
$PAGE->set_context($context);

// Check if the auth plug-in is enabled.
$authsequence = get_enabled_auth_plugins(true);
$authplugin = null;

foreach ($authsequence as $authname) {
    if (false !== strpos('auth_kronosportal', $authname)) {
        $authplugin = get_auth_plugin($authname);
        break;
    }
}

// If the auth plug-in doesn't exist or is not enabled then redirect the user.
if (is_null($authplugin)) {
    notice(get_string('wfc_auth_not_enabled', 'auth_kronosportal'), $continuestringurl);
}

// Check if the User's User Set exists.
$solutionuserset = $authplugin->userset_solutionid_exists($wfcsolid);
if (!$solutionuserset) {
    notice(get_string('wfc_auth_solutionid_not_found', 'auth_kronosportal'), $continuestringurl);
}

// Check if the User Set has a valid subscription.
if (kronosportal_is_user_userset_expired($authplugin, $wfcsolid)) {
    notice(get_string('wfc_auth_solutionid_expired', 'auth_kronosportal'), $continuestringurl);
}

$email = '';
$usr = new stdClass();
$usr->solutionid = $wfcsolid;
$usr->personnumber = $wfcpnum;
$usr->firstname = $wfcfname;
$usr->lastname = $wfclname;
$usr->country = $wfccountry;
$usr->learningpath = $wfclp;

// Format the data.
$newusr = kronosportal_apply_kronos_business_rules((array)$usr);

// Check if user the already exists in Moodle.
if (empty($newusr)) {
    notice(get_string('wfc_auth_error_applying_business_rules', 'auth_kronosportal'), $continuestringurl);
}

$muser = $DB->get_record('user', array('username' => $newusr['username']));

// If the user does not exist, create the user account.  Sync the WFC portal fields with the Moodle custom fields.
if (empty($muser)) {

    // Check if the new user's learning path exists.  This check is only for new users.
    if (isset($newusr['learningpath']) && !empty($newusr['learningpath'])) {
        // Retrieve a User Set whose parent is equal to the solution id and whose display name is equal to Learning Path display name (Subset).
        $userset = userset::find(array(
                new field_filter('parent', $solutionuserset->usersetid),
                new field_filter('displayname', $newusr['learningpath']))
        );

        // If a learning path is found.  Set flag to true.
        if ($userset->valid()) {
            $assigntolearningpath = true;
        } else {
            // Log event.
            $message = "Unable to create user (username: {$newusr['username']}).";
            $message .= "  Learning Path (display name: {$newusr['learningpath']}) does not exist as a subset of User Set {$solutionuserset->name}";
            // Assign the user to the Learning Path.
            $event = \auth_kronosportal\event\kronosportal_learningpath_not_exist::create(array(
                'other' => array(
                    'username' => $newusr['username'],
                    'message' => $message,
                    'wfc_learning_path' => $newusr['learningpath'],
                    'solution_userset_name' => $solutionuserset->name
                )
            ));
            $event->trigger();

            // Print an error message and do not proceed to create the new account.
            notice(get_string('wfc_auth_error_learning_path_not_exist', 'auth_kronosportal'), $continuestringurl);
        }
    }

    $muser = kronosportal_create_user((object)$newusr);
    // Update the Moodle object with new custom field values.
    kronosportal_sync_user_profile_to_portal_profile($muser, $newusr);

    // Assign user to learning path.
    if ($assigntolearningpath) {
        // This should never be null as there is an earlier check for the same empty value.
        $userset = ($userset->valid()) ? $userset->current() : null;

        // Retrieve the ELIS user record.
        $elisuser = usermoodle::find(array(
                new field_filter('muserid', $muser->id),
                new field_filter('idnumber', $muser->username))
        );

        $elisuser = ($elisuser->valid() && !is_null($userset)) ? $elisuser->current() : null;

        if (!is_null($elisuser)) {
            cluster_manual_assign_user($userset->id, $elisuser->cuserid);
        } else {
            $message = "Unable to find ELIS user linked to Moodle user.  Moodle username {$muser->username}";
            $event = \auth_kronosportal\event\kronosportal_elisuser_not_created::create(array(
                'other' => array(
                    'username' => $muser->username,
                    'message' => $message,
                )
            ));
            $event->trigger();

            notice(get_string('wfc_auth_error_could_not_find_elis_user', 'auth_kronosportal'), $continuestringurl);
        }
    }

    unset($muser->password);
} else {
    // Load the user profile data.
    profile_load_data($muser);
    // Update the Moodle user object properties with new WFC data.
    kronosportal_sync_standard_wfc_profile_fields($muser, (object) $newusr);
    // Update the custom profile fields of the Moodle object with the WFC.
    kronosportal_sync_user_profile_to_portal_profile($muser, $newusr, true);
    // Temporarily copy the user's email address.
    $email = $muser->email;
    // Unset the email so that it doesn't get updated. As per Kronos' business rules for WFC portal.
    unset($muser->email);
}

// Update the user record in Moodle.
kronosportal_update_user($muser);

require_once("{$CFG->libdir}/moodlelib.php");
$redirecturl = get_config('auth_kronosportal', 'kronosportal_successurl');
$url = get_login_url();
if (!empty($redirecturl)) {
    $url = $redirecturl;
    if (preg_match("/^\//", $url)) {
        $url = $CFG->wwwroot.$url;
    }
} else {
    $url = "{$CFG->wwwroot}/index.php";
}

// If the email place holder is not empty then assign it to the email property of the user object.
if (!empty($email)) {
    $muser->email = $email;
}
// Log in the user.
complete_user_login($muser);
// Sets the username cookie.
set_moodle_cookie($USER->username);
redirect(new moodle_url($url));
