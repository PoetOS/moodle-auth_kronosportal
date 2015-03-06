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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot."/auth/kronosportal/lib.php");

class auth_plugin_kronosportal extends auth_plugin_base {
    /** @var string The Moodle username. */
    public $username = '';

    /**
     * Constructor, set authentication plugin configuration
     */
    public function __construct() {
        $this->authtype = 'kronosportal';
        $this->config = get_config('auth_kronosportal');
    }

    /**
     * This function checks the plug-in configuration settings and return true if the required fields have been set.
     * @return bool True if the required settings are valid.  Otherwise false.
     */
    public function is_configuration_valid() {
        $valid = false;
        foreach ($this->config as $configname => $value) {
            if ('expiry' == $configname || 'extension' == $configname || 'solutionid' == $configname || 'user_field_solutionid' == $configname) {
                if (empty($value)) {
                    $valid = false;
                    break;
                } else {
                    $valid = true;
                }
            }
        }

        return $valid;
    }

    /**
     * This function verifies whether the user's Solutiond ID profile field exists in the custom profiles table.
     * @todo write phpunit tests at some point in the future.
     * @param int $userid A user id.
     * @return bool Returns true of the field exists.  Otherwise false.
     */
    public function user_solutionid_field_exists($userid) {
        global $DB;

        if (!is_numeric($userid)) {
            return false;
        }

        $exists = $DB->record_exists('user_info_data', array('userid' => $userid, 'fieldid' => $this->config->user_field_solutionid), 'id,data');

        if (empty($exists)) {
            $event = \auth_kronosportal\event\kronosportal_user_profile_solutionid_not_found::create(array(
                'userid' => $userid,
                'other' => array(
                    'message' => "Unable to find userid: {$userid} custom profile fieldid: {$this->config->user_field_solutionid}",
                    'user_moodle_custom_field_id' => $this->config->user_field_solutionid
                )
            ));
            $event->trigger();
            return false;
        }

        return true;
    }

    /**
     * This function retrieves the user's Solution ID from a custom profile field.
     * @todo write phpunit tests at some point in the future.
     * @param int $userid A user id.
     * @return int The user's solution id.  Zero is returned if the Solution id is empty.
     */
    public function get_user_solution_id($userid) {
        global $DB;

        $usersolutionid = $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => $this->config->user_field_solutionid));

        if (empty($usersolutionid)) {
            return 0;
        }

        return clean_param(trim($usersolutionid), PARAM_ALPHANUMEXT);
    }

    /**
     * This function searches for a User Set with a matching Solution ID.  The User Set Solution ID needs to be defined as
     * a custom field in the User Set conext.
     * @todo write phpunit tests at some point in the future.
     * @param int $usersolutionid The user's Solution ID.
     * @return mixed An object ('id' -> Context id, 'name' -> User Set name).  Otherwise false.
     */
    public function userset_solutionid_exists($usersolutionid) {
        global $DB;

        $cleansolutionid = clean_param(trim($usersolutionid), PARAM_ALPHANUMEXT);

        $sql = "SELECT ctx.id, uset.name
                  FROM {local_elisprogram_uset} uset
                  JOIN {local_eliscore_field_clevels} fldctx on fldctx.fieldid = ?
                  JOIN {context} ctx ON ctx.instanceid = uset.id AND ctx.contextlevel = fldctx.contextlevel
                  JOIN {local_eliscore_fld_data_char} fldchar ON fldchar.contextid = ctx.id AND fldchar.fieldid = ?
                 WHERE uset.depth = 2
                       AND fldchar.data = ?";

        $usersetcontextandname = $DB->get_record_sql($sql, array($this->config->solutionid, $this->config->solutionid, $cleansolutionid));

        if (empty($usersetcontextandname)) {
            $message = "Login attempt by {$this->username}.  Unable to find User Set with mathcing SolutionID (User Set Solution ID fieldid: {$this->config->solutionid}.".
                " User SolutionID: {$cleansolutionid})";
            $event = \auth_kronosportal\event\kronosportal_userset_not_found::create(array(
                'other' => array(
                    'username' => $this->username,
                    'message' => $message,
                    'user_set_solutionid_field_id' => $this->config->solutionid,
                    'user_solutionid_value' => $cleansolutionid
                )
            ));
            $event->trigger();
            return false;
        }

        return $usersetcontextandname;
    }

    /**
     * This function checks if the User Set  has expired, by checking if the current time is greater than either the expiry or extension date.
     * Note: The user's time zone cannot because at this stage of the login process, the user's session has not been created yet.
     * As a result the system time is being used.
     * @todo write phpunit tests at some point in the future.
     * @param int $usersolutionid The user's Solution ID.
     * @param int $contextid The context id of the User Set.
     * @param string $usersetname The name of the user set (Optional.  Mostly used for logging errors).
     * @return bool Returns True if the User Set has not expired.  Otherwise False.
     */
    public function user_set_has_valid_subscription($usersolutionid, $contextid, $usersetname = '') {
        global $DB;

        $cleansolutionid = clean_param(trim($usersolutionid), PARAM_ALPHANUMEXT);
        // UTC-1.
        $time = time();

        $sql = "SELECT fldint.id, fldint.data
                  FROM {local_eliscore_fld_data_int} fldint
                 WHERE fldint.contextid = ?
                       AND (fldint.fieldid = ?
                           OR fldint.fieldid = ?)";

        $records = $DB->get_records_sql($sql, array($contextid, $this->config->expiry, $this->config->extension));

        // Check if any of the fields exist.
        if (empty($records)) {
            $message = "Login attempt by {$this->username}. Unable to find User Set Expiry or Extended fields (Context Instance ID: {$contextid}, ".
                "Expiry date fieldid: {$this->config->expiry}, Extension date fieldid: {$this->config->extension}, User SolutionID: {$cleansolutionid})";
            $event = \auth_kronosportal\event\kronosportal_userset_expiry_not_found::create(array(
                'other' => array(
                    'username' => $this->username,
                    'message' => $message,
                    'context_id' => $contextid,
                    'user_set_expriy_date_field_id' => $this->config->expiry,
                    'user_set_extension_date_field_id' => $this->config->extension,
                )
            ));
            $event->trigger();
            return false;
        }

        // Check that the current time is greater than the expiry and/or the extension timestamps.  Using server time because the user global has not yet been initialzied.
        $subscriptionsvalid = false;
        foreach ($records as $record) {
            if ($record->data > $time) {
                $subscriptionsvalid = true;
                break;
            }
        }

        if (!$subscriptionsvalid) {
            $message = "Login attempt by {$this->username}.  User Set {$usersetname} (Contextid {$contextid}) has expired";
            $event = \auth_kronosportal\event\kronosportal_userset_has_expired::create(array(
                'other' => array(
                    'username' => $this->username,
                    'message' => $message,
                    'context_id' => $contextid,
                    'user_set_name' => $usersetname,
                    'user_set_expriy_date_field_id' => $this->config->expiry,
                    'user_set_extension_date_field_id' => $this->config->extension,
                    'current_time' => $time
                )
            ));
            $event->trigger();
            return false;
        }

        return true;
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist. (Non-mnet accounts only!)
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $CFG, $DB, $USER, $SESSION;
        // Check if the plug-in is configured correctly.
        if (!$this->is_configuration_valid()) {
            $event = \auth_kronosportal\event\kronosportal_invalid_configuration::create(array());
            $event->trigger();
            return false;
        }

        if (!$user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id))) {
            return false;
        }

        $this->username = $user->username;

        // Load custom fields.
        profile_load_data($user);
        // Do validation checks based on Kronos business rules.
        $result = kronosportal_validate_user($user);
        if ($result == "success") {
            if (!empty($this->config->kronosportal_successurl)) {
                $url = $this->config->kronosportal_successurl;
                if (preg_match("/^\//", $url)) {
                    $url = $CFG->wwwroot.$url;
                }
                $SESSION->wantsurl = $url;
            }
        } else {
            $errorurl = '';
            if (!empty($this->config->kronosportal_errorurl)) {
                $errorurl = $this->config->kronosportal_errorurl;
            }
            $this->handleerror($errorurl, 'usermessage'.$result);
        }

        if (!validate_internal_user_password($user, $password)) {
            return false;
        }

        if ($password === 'changeme') {
            // Force the change - this is deprecated and it makes sense only for manual auth,
            // because most other plugins can not change password easily or
            // passwords are always specified by users.
            set_user_preference('auth_forcepasswordchange', true, $user->id);
        }
        return true;
    }

    /**
     * Updates the user's password. Called when the user password is updated.
     *
     * @param object $user User table object
     * @param string $newpassword Plaintext password
     * @return boolean result
     */
    public function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        set_user_preference('auth_kronosportal_passwordupdatetime', time(), $user->id);
        // This will also update the stored hash to the latest algorithm
        // if the existing hash is using an out-of-date algorithm (or the
        // legacy md5 algorithm).
        return update_internal_user_password($user, $newpassword);
    }

    /**
     * Returns true if this authentication plugin can prevent local passwords.
     *
     * @return bool
     */
    public function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return true;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return true;
    }

    /**
     * Confirm the new user as registered.
     * This should normally not be used,
     * but it may be necessary if the user auth_method is changed to manual
     * before the user is confirmed.
     *
     * @param string $username Username to confirm.
     * @param string $confirmsecret
     * return int Return AUTH_CONFIRM_OK | AUTH_CONFIRM_ALREADY | AUTH_CONFIRM_ERROR
     */
    public function user_confirm($username, $confirmsecret = null) {
        global $DB;
        $user = get_complete_user_data('username', $username);
        if (!empty($user)) {
            if ($user->confirmed) {
                return AUTH_CONFIRM_ALREADY;
            } else {
                $DB->set_field("user", "confirmed", 1, array("id" => $user->id));
                if ($user->firstaccess == 0) {
                    $DB->set_field("user", "firstaccess", time(), array("id" => $user->id));
                }
                return AUTH_CONFIRM_OK;
            }
        } else {
            return AUTH_CONFIRM_ERROR;
        }
    }

    /**
     * Lookup username based on token and setup user authentication.
     *
     * @return void
     */
    public function loginpage_hook() {
        global $frm, $user, $DB, $USER, $SESSION, $CFG;
        $token = optional_param('token', '', PARAM_TEXT);
        $url = optional_param('url', '', PARAM_TEXT);
        if (empty($url) && !empty($this->config->kronosportal_successurl)) {
            $url = $this->config->kronosportal_successurl;
        }
        $errorurl = optional_param('error', '', PARAM_TEXT);
        if (empty($errorurl) && !empty($this->config->kronosportal_errorurl)) {
            $errorurl = $this->config->kronosportal_errorurl;
        }
        if (!empty($token)) {
            $frm = new stdClass();
            try {
                $tokens = $DB->get_record('kronosportal_tokens', array('token' => $token), '*', MUST_EXIST);
            } catch (dml_missing_record_exception $e) {
                // Token not found.
                $this->handleerror($errorurl, 'tokennotfound');
                return;
            }

            if (!empty($tokens->userid) && !empty($USER->id) && $USER->id === $tokens->userid
                    || !empty($tokens->userid) && empty($tokens->sid)) {
                // User is all ready logged in as this user and is reusing token.
                $user = $DB->get_record('user', array('id' => $tokens->userid));
                if (!empty($user) && !empty($user->username)) {
                    // Load custom fields.
                    profile_load_data($user);
                    // Do validation checks based on Kronos business rules.
                    $result = kronosportal_validate_user($user);
                    if ($result == "success") {
                        $frm->username = $user->username;
                        if (preg_match("/^\//", $url)) {
                            $url = $CFG->wwwroot.$url;
                        }
                        $SESSION->wantsurl = $url;
                    } else {
                        $this->handleerror($errorurl, 'usermessage'.$result);
                        return;
                    }
                }
            } else {
                // Token has been used already in another browser.
                $this->handleerror($errorurl, 'tokennotfound');
                return;
            }
        }
    }

    /**
     * Handle error message, display message or redirect to url.
     *
     * @param string $errorurl Url to redirect to.
     * @param string $error Name of error message to display.
     */
    public function handleerror($errorurl, $error) {
        global $CFG;
        if (!empty($errorurl)) {
            if (preg_match('/\?/', $errorurl)) {
                $errorurl .= '&error='.$error;
            } else {
                $errorurl .= '?error='.urlencode($error);
            }
            if (preg_match("/^\//", $errorurl)) {
                $errorurl = $CFG->wwwroot.$errorurl;
            }
            redirect($errorurl);
        } else {
            notice(get_string($error, 'auth_kronosportal'));
        }
    }

    /**
     * Delete token if user logouts from moodle
     *
     * @return void
     */
    public function prelogout_hook() {
        global $DB;
        $DB->delete_records('kronosportal_tokens', array('sid' => session_id()));
    }

    /**
     * Delete tokens older than 24 hours
     *
     * @return void
     */
    public function cron() {
        global $DB;
        // Expire tokens after 24 hours.
        $expiry = time() - 24 * 3600;
        $DB->delete_records_select('kronosportal_tokens', ' timecreated < ? ', array($expiry));
    }

}
