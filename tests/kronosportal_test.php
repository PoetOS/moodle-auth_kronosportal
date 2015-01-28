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
 * @package    auth_kronossandvm
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */
class mod_auth_kronosportal_testcase extends advanced_testcase {
    /**
     * @var array $users Array of test users.
     */
    private $users = null;

    /**
     * Tests set up.
     */
    public function setUp() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/auth/kronosportal/lib.php');
        $this->resetAfterTest();
        $this->users = array();
        $this->users[] = $this->getDataGenerator()->create_user();
        $this->setupcustomfield();
    }

    /**
     * Setup custom field.
     */
    public function setupcustomfield() {
        global $DB;
        // Add a custom field customerid of text type.
        $this->fieldid = $DB->insert_record('user_info_field', array(
                'shortname' => 'customerid', 'name' => 'Description of customerid', 'categoryid' => 1,
                'datatype' => 'text'));
        $this->setcustomfielddata($this->users[0]->id, 'test');
    }

    /**
     * Set custom field data.
     *
     * @param int $userid User id to set the field on.
     * @param string $value Value to set field to.
     */
    public function setcustomfielddata($userid, $value) {
        global $DB;
        // Set up data.
        $record = new stdClass;
        $record->fieldid = $this->fieldid;
        $record->userid = $userid;
        $record->data = $value;
        $DB->insert_record('user_info_data', $record);
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
            'profile_field_customerid' => 'testcustomerid'
        );
        $fulluser = kronosportal_create_user($user);
        $this->assertEquals($user['username'], $fulluser->username);
        $this->assertEquals($user['profile_field_customerid'], $fulluser->profile_field_customerid);
        $result = kronosportal_validate_user($fulluser);
        $this->assertEquals('success', $result);
    }

    /**
     * Test validating user.
     */
    public function test_kronosportal_validate_user() {
        $result = kronosportal_validate_user($this->users[0]);
        $this->assertEquals('success', $result);
        $result = kronosportal_validate_user(null);
        $this->assertEquals('invaliduser', $result);
        $result = kronosportal_validate_user(new stdClass());
        $this->assertEquals('invaliduser', $result);
        // TODO create test for expired userset.
    }
}
