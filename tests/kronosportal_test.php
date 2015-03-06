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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for Kronos portal authentication.
 *
 * @package    auth_kronosportal
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

/**
 * Test kronosportal auth plugin and web service
 */
class auth_kronosportal_testcase extends advanced_testcase {
    /**
     * @var array $users Array of test users.
     */
    private $users = null;

    /**
     * @var array $users Array of custom field ids.
     */
    private $customfields = null;

    /***
     * Test token is created successfully and inserted into database
     */
    public function test_auth_kronosportal_create_token() {
        global $CFG, $DB;

        $auth = get_auth_plugin('kronosportal');
        $webservice = new auth_kronosportal_external();
        $user = $this->getDataGenerator()->create_user();
        $result = $webservice->create_token($user->username);

        $this->assertEquals($result['status'], 'success');
        $token = $result['token'];

        $this->assertEquals(strlen($token), 32);
        $tokenrecords = $DB->get_record('kronosportal_tokens', array('token' => $token));
        $this->assertEquals($tokenrecords->token, $token);
        $this->assertEquals($tokenrecords->userid, $user->id);
        $this->assertEquals($tokenrecords->sid, '');
    }

    /***
     * Test is_configuration_valid.
     */
    public function test_auth_kronosportal_is_configuration_valid() {
        global $CFG, $DB;
        $auth = get_auth_plugin('kronosportal');
        $this->assertTrue($auth->is_configuration_valid());
        set_config('expiry', '', 'auth_kronosportal');
        $auth = get_auth_plugin('kronosportal');
        $this->assertFalse($auth->is_configuration_valid());
    }

    /***
     * Test creation of user and token.
     */
    public function test_auth_kronosportal_create_token_user() {
        global $CFG, $DB;

        $auth = get_auth_plugin('kronosportal');
        $webservice = new auth_kronosportal_external();
        $result = $webservice->create_token("newusertest", "Guy", "Ord", "testsolutionid", "Kronos#1pass",
                "email2@test.com", "city", "CA", "en", "testlearningpath");
        $user = $DB->get_record('user', array('username' => 'newusertest'));
        // Retrieve custom user fields.
        profile_load_data($user);
        $this->assertEquals("newusertest", $user->username);
        $this->assertEquals("Guy", $user->firstname);
        $this->assertEquals("testsolutionid", $user->profile_field_customerid);

        $this->assertEquals($result['status'], 'success');
        $token = $result['token'];

        $this->assertEquals(strlen($token), 32);
        $tokenrecords = $DB->get_record('kronosportal_tokens', array('token' => $token));
        $this->assertEquals($tokenrecords->token, $token);
        $this->assertEquals($tokenrecords->userid, $user->id);
        $this->assertEquals($tokenrecords->sid, '');
        // Test invalid data.
        $message = 'Invalid parameter value detected (The following fields are required to create an account:';
        $message .= ' username, firstname, lastname, customerid, password and email)';
        $this->setExpectedException('invalid_parameter_exception', $message);
        $result = $webservice->create_token("newusertest1", "", "Ord", "testsolutionid", "Kronos#1pass",
                "email2@test.com", "city", "CA", "en", "testlearningpath");
    }

    /***
     * Test creation of user and token with invalid solution id.
     */
    public function test_auth_kronosportal_create_token_user_invalidsolution() {
        global $CFG, $DB;

        $auth = get_auth_plugin('kronosportal');
        $webservice = new auth_kronosportal_external();
        $message = 'Invalid parameter value detected (SolutionID/Userset does not exist.)';
        $this->setExpectedException('invalid_parameter_exception', $message);
        $result = $webservice->create_token("newusertest", "Guy", "Ord", "invalidsolutionid", "Kronos#1pass",
                "email2@test.com", "city", "CA", "en", "testlearningpath");
    }

    /***
     * Test creation of user and token with expired solution id.
     */
    public function test_auth_kronosportal_create_token_user_expiredsolution() {
        global $CFG, $DB;
        $auth = get_auth_plugin('kronosportal');
        $webservice = new auth_kronosportal_external();
        $message = 'Invalid parameter value detected (SolutionID/Userset is expired.)';
        $this->setExpectedException('invalid_parameter_exception', $message);
        $result = $webservice->create_token("newusertest", "Guy", "Ord", "expiredsolution", "Kronos#1pass",
                "email2@test.com", "city", "CA", "en", "testlearningpath");
    }

    /***
     * Test creation of user.
     */
    public function test_auth_kronosportal_create_user() {
        global $CFG, $DB;

        $auth = get_auth_plugin('kronosportal');
        $webservice = new auth_kronosportal_external();
        $result = $webservice->create_user("newusertest", "Guy", "Ord", "testsolutionid", "Kronos#1pass",
                "email2@test.com", "city", "CA", "en", "testlearningpath");
        $user = $DB->get_record('user', array('username' => 'newusertest'));
        // Retrieve custom user fields.
        profile_load_data($user);
        $this->assertEquals("newusertest", $user->username);
        $this->assertEquals("Guy", $user->firstname);
        $this->assertEquals("testsolutionid", $user->profile_field_customerid);

        $this->assertEquals($result['status'], 'success');
        $this->assertEquals($result['userid'], $user->id);

        // Test calling of web service with no country, city, language, learning path.
        $result = $webservice->create_user("newusertest1", "Another", "Lastname", "testsolutionid", "Kronos#1pass",
                "email2@test1.com");
        $user = $DB->get_record('user', array('username' => 'newusertest1'));
        // Retrieve custom user fields.
        profile_load_data($user);
        $this->assertEquals("newusertest1", $user->username);
        $this->assertEquals("Another", $user->firstname);

        $this->assertEquals($result['status'], 'success');
        $this->assertEquals($result['userid'], $user->id);
    }

    /***
     * Test update of user.
     */
    public function test_auth_kronosportal_update_user() {
        global $CFG, $DB;

        $auth = get_auth_plugin('kronosportal');
        $webservice = new auth_kronosportal_external();
        $result = $webservice->create_user("newusertest", "Guy", "Ord", "testsolutionid", "Kronos#1pass",
                "email2@test.com", "city", "CA", "en", "testlearningpath");
        $result = $webservice->update_user("newusertest", "Guy1", "Ord1", "extensionsolution", "Kronos#1pass1",
                "email2@test1.com", "city1", "us", "en", "testlearningpath1");
        $user = $DB->get_record('user', array('username' => 'newusertest'));
        // Retrieve custom user fields.
        profile_load_data($user);
        $this->assertEquals("success", $result['status']);
        $this->assertEquals($user->id, $result['userid']);
        $this->assertEquals("newusertest", $user->username);
        $this->assertEquals("Guy1", $user->firstname);
        $this->assertEquals("extensionsolution", $user->profile_field_customerid);

        $this->assertEquals($result['status'], 'success');
        $this->assertEquals($result['userid'], $user->id);

        // Test optional fields city, country, lang, learning path are not updated.
        $result = $webservice->update_user("newusertest", "Guy1", "Ord1", "extensionsolution", "Kronos#1pass1",
                "email2@test1.com");
        $user = $DB->get_record('user', array('username' => 'newusertest'));
        // Retrieve custom user fields.
        profile_load_data($user);
        $this->assertEquals("success", $result['status']);
        $this->assertEquals($user->id, $result['userid']);
        $this->assertEquals("newusertest", $user->username);
        $this->assertEquals("Guy1", $user->firstname);
        $this->assertEquals("city1", $user->city);
        $this->assertEquals("us", $user->country);
        $this->assertEquals($CFG->lang, $user->lang);
        $this->assertEquals("testlearningpath1", $user->profile_field_learningpath);
        $this->assertEquals("extensionsolution", $user->profile_field_customerid);

        // Test optional fields city, country are not updated and country, learning path are.
        $result = $webservice->update_user("newusertest", "Guy1", "Ord1", "extensionsolution", "Kronos#1pass1",
                "email2@test1.com", null, 'ca', null, 'path');

        $user = $DB->get_record('user', array('username' => 'newusertest'));
        // Retrieve custom user fields.
        profile_load_data($user);
        $this->assertEquals("success", $result['status']);
        $this->assertEquals($user->id, $result['userid']);
        $this->assertEquals("newusertest", $user->username);
        $this->assertEquals("Guy1", $user->firstname);
        $this->assertEquals("city1", $user->city);
        $this->assertEquals("ca", $user->country);
        $this->assertEquals($CFG->lang, $user->lang);
        $this->assertEquals("path", $user->profile_field_learningpath);
        $this->assertEquals("extensionsolution", $user->profile_field_customerid);
    }

    /**
     * Test throwing of expection when user does not exist
     */
    public function test_auth_kronosportal_create_token_invaliduser() {
        global $CFG;

        $webservice = new auth_kronosportal_external();
        $message = 'Invalid parameter value detected (The following fields are required to create an account:';
        $message .= ' username, firstname, lastname, customerid, password and email)';
        $this->setExpectedException('invalid_parameter_exception', $message);
        $result = $webservice->create_token('doesnotexists');
    }


    /**
     * Test kronosportal_validate_user
     */
    public function test_auth_kronosportal_validate_user() {
        global $CFG;
        profile_load_data($this->users[0]);
        profile_load_data($this->users[1]);
        profile_load_data($this->users[2]);
        $this->assertEquals("success", kronosportal_validate_user($this->users[0]));
        $this->assertEquals("expired", kronosportal_validate_user($this->users[1]));
        $this->assertEquals("success", kronosportal_validate_user($this->users[2]));
    }

    /**
     * Test use of token for login and logout by token.
     */
    public function test_auth_kronosportal_login_logout_by_token() {
        global $user, $CFG, $DB;

        $auth = get_auth_plugin('kronosportal');

        $webservice = new auth_kronosportal_external();
        $testuser = $this->users[0];
        $result = $webservice->create_token($testuser->username);

        $token = $result['token'];
        $_GET['token'] = $token;
        $auth->loginpage_hook();

        $this->assertEquals($user->username, $testuser->username);
        $this->assertEquals($user->id, $testuser->id);

        $sessionid = md5(rand(1, 100000));
        // Similate a login.
        // This assumes you are using database sessions.
        $DB->insert_record('sessions', array(
            'sid' => $sessionid,
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time()
        ));

        $tokenrecords = $DB->get_record('kronosportal_tokens', array('token' => $token));
        $tokenrecords->sid = $sessionid;
        $DB->update_record('kronosportal_tokens', $tokenrecords);
        $result = $webservice->logout_by_token($token);
        $this->assertEquals($result['status'], 'success');

        // Test token was deleted.
        $tokenrecords = $DB->get_record('kronosportal_tokens', array('token' => $token));

        $this->assertEquals($tokenrecords, false);

        // Test Token not found error.
        $message = 'Invalid parameter value detected (Token not found)';
        $this->setExpectedException('invalid_parameter_exception', $message);
        $result = $webservice->logout_by_token($token);
    }

    /**
     * Test use of token for login and logout by user.
     */
    public function test_auth_kronosportal_login_logout_by_username() {
        global $user, $CFG, $DB;
        $auth = get_auth_plugin('kronosportal');
        $webservice = new auth_kronosportal_external();
        $testuser = $this->users[0];
        $result = $webservice->create_token($testuser->username);

        $token = $result['token'];
        $_GET['token'] = $token;
        $auth->loginpage_hook();

        $this->assertEquals($user->username, $testuser->username);
        $this->assertEquals($user->id, $testuser->id);

        $sessionid = md5(rand(1, 100000));
        // Similate a login.
        // This assumes you are using database sessions.
        $DB->insert_record('sessions', array(
            'sid' => $sessionid,
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time()
        ));

        $tokenrecords = $DB->get_record('kronosportal_tokens', array('token' => $token));
        $tokenrecords->sid = $sessionid;
        $DB->update_record('kronosportal_tokens', $tokenrecords);
        $result = $webservice->logout_by_user($testuser->username);
        $this->assertEquals($result['status'], 'success');

        // Test token was deleted.
        $tokenrecords = $DB->get_record('kronosportal_tokens', array('token' => $token));

        $this->assertEquals($tokenrecords, false);

        // Test Token not found error.
        $message = 'Invalid parameter value detected (Token not found)';
        $this->setExpectedException('invalid_parameter_exception', $message);
        $result = $webservice->logout_by_user($testuser->username);
    }

    /**
     * Test userset_solutionid_exists.
     */
    public function test_auth_kronosportal_userset_solutionid_exists() {
        $auth = get_auth_plugin('kronosportal');
        $result = $auth->userset_solutionid_exists('testsolutionid');
        $this->assertEquals("testuserset", $result->name);
        $result = kronosportal_is_user_userset_valid($auth, 'testsolutionid');
        $this->assertTrue($result);
        $result = $auth->userset_solutionid_exists('testsolutionidaaa');
        $this->assertFalse($result);
        $result = kronosportal_is_user_userset_valid($auth, 'testsolutionidaaa');
        $this->assertFalse($result);
        $result = kronosportal_is_user_userset_valid($auth, '00000006-solutionid');
        $this->assertTrue($result);
        $result = kronosportal_is_user_userset_valid($auth, '00000006');
        $this->assertTrue($result);
    }

    /**
     * Test user_set_has_valid_subscription.
     */
    public function test_auth_kronosportal_user_set_has_valid_subscription() {
        $auth = get_auth_plugin('kronosportal');

        // Valid.
        $usersolutionid = $auth->get_user_solution_id($this->users[0]->id);
        $usersetcontextandname = $auth->userset_solutionid_exists($usersolutionid);
        $result = $auth->user_set_has_valid_subscription($usersolutionid, $usersetcontextandname->id, $usersetcontextandname->name);
        $this->assertTrue($result);

        // Expired.
        $usersolutionid = $auth->get_user_solution_id($this->users[1]->id);
        $usersetcontextandname = $auth->userset_solutionid_exists($usersolutionid);
        $result = $auth->user_set_has_valid_subscription($usersolutionid, $usersetcontextandname->id, $usersetcontextandname->name);
        $this->assertFalse($result);

        // Extention.
        $usersolutionid = $auth->get_user_solution_id($this->users[2]->id);
        $usersetcontextandname = $auth->userset_solutionid_exists($usersolutionid);
        $result = $auth->user_set_has_valid_subscription($usersolutionid, $usersetcontextandname->id, $usersetcontextandname->name);
        $this->assertTrue($result);
    }

    /**
     * Test kronosportal_is_user_userset_expired.
     */
    public function test_auth_kronosportal_is_user_userset_expired() {
        $auth = get_auth_plugin('kronosportal');

        // Valid.
        $solutionid = $auth->get_user_solution_id($this->users[0]->id);
        $this->assertFalse(kronosportal_is_user_userset_expired($auth, $solutionid));
        $this->assertFalse(kronosportal_is_user_userset_expired($auth, 'testsolutionid'));

        // Expired.
        $solutionid = $auth->get_user_solution_id($this->users[1]->id);
        $this->assertTrue(kronosportal_is_user_userset_expired($auth, $solutionid));
        $this->assertTrue(kronosportal_is_user_userset_expired($auth, 'expiredsolution'));

        // Extension.
        $solutionid = $auth->get_user_solution_id($this->users[2]->id);
        $this->assertFalse(kronosportal_is_user_userset_expired($auth, $solutionid));
        $this->assertFalse(kronosportal_is_user_userset_expired($auth, 'extensionsolution'));
    }

    /**
     * Test user_solutionid_field_exists.
     */
    public function test_auth_kronosportal_user_solutionid_field_exists() {
        $auth = get_auth_plugin('kronosportal');
        $result = $auth->user_solutionid_field_exists($this->users[0]->id);
        $this->assertTrue($result);
        $temp = $this->getDataGenerator()->create_user();
        $result = $auth->user_solutionid_field_exists($temp->id);
        $this->assertFalse($result);
    }

    /**
     * Test get_user_solution_id.
     */
    public function test_auth_kronosportal_get_user_solution_id() {
        $auth = get_auth_plugin('kronosportal');
        $result = $auth->get_user_solution_id($this->users[0]->id);
        $this->assertEquals("testsolutionid", $result);
        $temp = $this->getDataGenerator()->create_user();
        $result = $auth->get_user_solution_id($temp->id);
        $this->assertEquals(0, $result);
    }

    /**
     * Test kronosportal_get_solutionfield.
     */
    public function test_auth_kronosportal_get_solutionfield() {
        $this->assertEquals("customerid", kronosportal_get_solutionfield());
    }

    /**
     * Test that sid is set properly for a token.
     */
    public function test_auth_kronosportal_observer() {
        global $user, $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/auth/kronosportal/classes/observer.php');

        $this->resetAfterTest();
        $this->enable_plugin();
        $auth = get_auth_plugin('kronosportal');
        $webservice = new auth_kronosportal_external();
        $testuser = $this->users[0];
        $result = $webservice->create_token($testuser->username);

        $token = $result['token'];

        $_GET['token'] = $token;
        $auth->loginpage_hook();

        $USER = $user;
        $sessionid = md5(rand(1, 100000));
        session_id($sessionid);
        auth_kronosportal_observer::user_loggedin(null);

        $tokenrecord = $DB->get_record('kronosportal_tokens', array('token' => $token));

        $this->assertEquals($tokenrecord->sid, $sessionid);
        $this->assertEquals($tokenrecord->userid, $USER->id);
    }

    /**
     * Test creating user.
     */
    public function test_kronosportal_create_user() {
        // Create user.
        $user = array(
            'username' => 'test1',
            'email' => 'test1@kronos.com',
            'firstname' => 'firstname1',
            'lastname' => 'lastname1',
            'profile_field_customerid' => 'testsolutionid'
        );
        $fulluser = kronosportal_create_user($user);
        $this->assertEquals($user['username'], $fulluser->username);
        $this->assertEquals($user['profile_field_customerid'], $fulluser->profile_field_customerid);
        $result = kronosportal_validate_user($fulluser);
        $this->assertEquals('success', $result);
    }

    /**
     * Enable auth plugin.
     */
    protected function enable_plugin() {
        $auths = get_enabled_auth_plugins(true);
        if (! in_array('kronosportal', $auths)) {
            $auths [] = 'kronosportal';
        }
        set_config('auth', implode(',', $auths));
    }

    /**
     * Setup custom field.
     */
    public function setupcustomfield() {
        global $DB;
        // Add a custom field customerid of text type.
        $this->customfields = array();
        $this->customfields['customerid'] = $DB->insert_record('user_info_field', array(
                'shortname' => 'customerid', 'name' => 'Description of customerid', 'categoryid' => 1,
                'datatype' => 'text'));
        $this->customfields['learningpath'] = $DB->insert_record('user_info_field', array(
                'shortname' => 'learningpath', 'name' => 'Description of learning path', 'categoryid' => 1,
                'datatype' => 'text'));
    }

    /**
     * Set custom field data.
     *
     * @param int $fieldid Id of field to set data.
     * @param int $userid User id to set the field on.
     * @param string $value Value to set field to.
     */
    public function setcustomfielddata($fieldid, $userid, $value) {
        global $DB;
        // Set up data.
        $record = new stdClass;
        $record->fieldid = $fieldid;
        $record->userid = $userid;
        $record->data = $value;
        $DB->insert_record('user_info_data', $record);
    }

    /**
     * Tests set up.
     */
    public function setUp() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/auth/kronosportal/lib.php');
        require_once($CFG->dirroot.'/auth/kronosportal/auth.php');
        require_once($CFG->dirroot.'/auth/kronosportal/externallib.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        $this->resetAfterTest();
        $this->enable_plugin();

        $this->setupcustomfield();

        $this->users = array();
        // Valid solution id.
        $this->users[] = $this->getDataGenerator()->create_user();
        $this->setcustomfielddata($this->customfields['customerid'], $this->users[0]->id, 'testsolutionid');
        $this->setcustomfielddata($this->customfields['learningpath'], $this->users[0]->id, 'testlearningdata');

        // Expired solution id.
        $this->users[] = $this->getDataGenerator()->create_user();
        $this->setcustomfielddata($this->customfields['customerid'], $this->users[1]->id, 'expiredsolution');
        $this->setcustomfielddata($this->customfields['learningpath'], $this->users[1]->id, 'testlearningdata');

        // Extension solution id.
        $this->users[] = $this->getDataGenerator()->create_user();
        $this->setcustomfielddata($this->customfields['customerid'], $this->users[2]->id, 'extensionsolution');
        $this->setcustomfielddata($this->customfields['learningpath'], $this->users[2]->id, 'testlearningdata');

        // Setup custom userset field.
        // Create custom field.
        $fieldcat = new field_category;
        $fieldcat->name = 'Kronos';
        $fieldcat->save();

        $categorycontext = new field_category_contextlevel();
        $categorycontext->categoryid = $fieldcat->id;
        $categorycontext->contextlevel = CONTEXT_ELIS_USERSET;
        $categorycontext->save();

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'extension';
        $field->name = 'Extention';
        $field->datatype = 'int';
        $field->save();

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        $this->customfields['userset_extension'] = $field->id;

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'expiry';
        $field->name = 'Expiry';
        $field->datatype = 'int';
        $field->save();

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        $this->customfields['userset_expiry'] = $field->id;

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'customerid';
        $field->name = 'SolutionID';
        $field->datatype = 'char';
        $field->save();

        $this->customfields['userset_solutionid'] = $field->id;

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        // Create parent userset.
        $userset = array(
            'name' => 'Customer Audience',
            'display' => 'test userset description',
        );

        $us = new userset($userset);
        $us->save();

        // Create valid solutionid userset.
        $userset = array(
            'name' => 'testuserset',
            'display' => 'test userset description',
            'field_customerid' => 'testsolutionid',
            'field_expiry' => time() + 3600,
            'field_extension' => time() + 3600,
            'parent' => $us->id
        );

        $usvalid = new userset();
        $usvalid->set_from_data((object)$userset);
        $usvalid->save();

        // Create expired solutionid userset.
        $userset = array(
            'name' => 'expiredsolution name',
            'display' => 'test userset description',
            'field_customerid' => 'expiredsolution',
            'field_expiry' => time() - 3600,
            'field_extension' => time() - 3600,
            'parent' => $us->id
        );

        $usinvalid = new userset();
        $usinvalid->set_from_data((object)$userset);
        $usinvalid->save();

        // Create solutionid with extension.
        $userset = array(
            'name' => 'solutionextension name',
            'display' => 'test userset description',
            'field_customerid' => 'extensionsolution',
            'field_expiry' => time() - 3600,
            'field_extension' => time() + 3600,
            'parent' => $us->id
        );

        $usinvalid = new userset();
        $usinvalid->set_from_data((object)$userset);
        $usinvalid->save();

        // Create solutionid with only numeric charcters.
        $userset = array(
            'name' => 'solutionextension name 00000006',
            'display' => 'test userset description',
            'field_customerid' => '00000006',
            'field_expiry' => time() - 3600,
            'field_extension' => time() + 3600,
            'parent' => $us->id
        );

        $usinvalid = new userset();
        $usinvalid->set_from_data((object)$userset);
        $usinvalid->save();

        // Create solutionid with alpha, numeric charcters.
        $userset = array(
            'name' => 'solutionextension name 00000006-solutionid',
            'display' => 'test userset description',
            'field_customerid' => '00000006-solutionid',
            'field_expiry' => time() - 3600,
            'field_extension' => time() + 3600,
            'parent' => $us->id
        );

        $usinvalid = new userset();
        $usinvalid->set_from_data((object)$userset);
        $usinvalid->save();

        // Setup configuration.
        set_config('expiry', $this->customfields['userset_expiry'], 'auth_kronosportal');
        set_config('extension', $this->customfields['userset_extension'], 'auth_kronosportal');
        set_config('solutionid', $this->customfields['userset_solutionid'], 'auth_kronosportal');
        set_config('user_field_solutionid', $this->customfields['customerid'], 'auth_kronosportal');
    }
}
