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

class auth_kronosportal_wfc_testcases extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/auth/kronosportal/lib.php');
        $this->resetAfterTest();
    }

    /**
     * This function tests the application of the business roles for WFC users.
     */
    public function test_kronosportal_apply_kronos_business_rules_restricted_users() {
        $usr = new stdClass();
        $usr->solutionid = 'phpu_SOLID';
        $usr->personnumber = 'phpu_pernum';
        $usr->firstname = 'phpu_fname';
        $usr->lastname = 'phpu_lname';
        $usr->country = 'phpu_ca';
        $usr->learningpath = 'phpu_-R';

        $usrexpected = new stdclass();
        $usrexpected->username = strtolower('wfc'.$usr->solutionid.$usr->personnumber);
        $usrexpected->username = clean_param($usrexpected->username, PARAM_USERNAME);
        $usrexpected->password = "pwd".strrev($usr->personnumber).strrev($usr->solutionid);
        $usrexpected->email = $usrexpected->username."@wfc.kronos.com";
        $usrexpected->firstname = $usr->firstname;
        $usrexpected->lastname = $usr->lastname;
        $usrexpected->country = $usr->country;
        $usrexpected->lang = 'en';
        $usrexpected->learningpath = 'phpu_';
        $usrexpected->restricted = '1';
        $usrexpected->solutionid = $usr->solutionid;
        $usrexpected->personnumber = $usr->personnumber;

        $result = kronosportal_apply_kronos_business_rules((array)$usr);
        $this->assertEquals((array)$usrexpected, $result);

        $usr = new stdClass();
        $usr->solutionid = 'phpu_SOLID';
        $usr->personnumber = 'phpu_pernum';
        $usr->firstname = 'phpu_fname';
        $usr->lastname = 'phpu_lname';
        $usr->country = 'phpu_ca';
        $usr->learningpath = '-Rph-Rpu_-R';

        $usrexpected->learningpath = '-Rph-Rpu_';

        $result = kronosportal_apply_kronos_business_rules((array)$usr);
        $this->assertEquals((array)$usrexpected, $result);
    }

    /**
     * This function tests the application of the business roles for WFC users.
     */
    public function test_kronosportal_apply_kronos_business_rules() {
        $usr = new stdClass();
        $usr->solutionid = 'phpu_SOLID';
        $usr->personnumber = 'phpu_pernum';
        $usr->firstname = 'phpu_fname';
        $usr->lastname = 'phpu_lname';
        $usr->country = 'phpu_ca';
        $usr->learningpath = 'phpu_';

        $usrexpected = new stdclass();
        $usrexpected->username = strtolower('wfc'.$usr->solutionid.$usr->personnumber);
        $usrexpected->username = clean_param($usrexpected->username, PARAM_USERNAME);
        $usrexpected->password = "pwd".strrev($usr->personnumber).strrev($usr->solutionid);
        $usrexpected->email = $usrexpected->username."@wfc.kronos.com";
        $usrexpected->firstname = $usr->firstname;
        $usrexpected->lastname = $usr->lastname;
        $usrexpected->country = $usr->country;
        $usrexpected->lang = 'en';
        $usrexpected->learningpath = 'phpu_';
        $usrexpected->restricted = '0';
        $usrexpected->solutionid = $usr->solutionid;
        $usrexpected->personnumber = $usr->personnumber;

        $result = kronosportal_apply_kronos_business_rules((array)$usr);
        $this->assertEquals((array)$usrexpected, $result);

        // Test setting a different learning patch string.
        $usr = new stdClass();
        $usr->solutionid = 'phpu_SOLID';
        $usr->personnumber = 'phpu_pernum';
        $usr->firstname = 'phpu_fname';
        $usr->lastname = 'phpu_lname';
        $usr->country = 'phpu_ca';
        $usr->learningpath = '-Rph-Rpu_';

        $usrexpected->learningpath = '-Rph-Rpu_';

        $result = kronosportal_apply_kronos_business_rules((array)$usr);
        $this->assertEquals((array)$usrexpected, $result);
    }

    /**
     * This function is a data provider containing empty values
     * @return array An array of invalid parameters.
     */
    public function empty_parameters() {
        return array(
                array(array(
                        'solutionid' => '',
                        'personnumber' => 'A',
                        'firstname' => 'A',
                        'lastname' => 'A',
                        'country' => 'A',
                        'learningpath' => 'A')
                ),
                array(array(
                        'solutionid' => 'A',
                        'personnumber' => '',
                        'firstname' => 'A',
                        'lastname' => 'A',
                        'country' => 'A',
                        'learningpath' => 'A')
                ),
                array(array(
                        'solutionid' => 'A',
                        'personnumber' => 'A',
                        'firstname' => '',
                        'lastname' => 'A',
                        'country' => 'A',
                        'learningpath' => 'A')
                ),
                array(array(
                        'solutionid' => 'A',
                        'personnumber' => 'A',
                        'firstname' => 'A',
                        'lastname' => '',
                        'country' => 'A',
                        'learningpath' => 'A')
                ),
        );
    }

    /**
     * This function is a data provider containing missing values
     * @return array An array of invalid parameters.
     */
    public function missing_parameters() {
        return array(
                // Missing solutionid.
                array(array(
                        'personnumber' => 'A',
                        'firstname' => 'A',
                        'lastname' => 'A',
                        'country' => 'A',
                        'learningpath' => 'A')
                ),
                // Missing personnumber.
                array(array(
                        'solutionid' => 'A',
                        'firstname' => 'A',
                        'lastname' => 'A',
                        'country' => 'A',
                        'learningpath' => 'A')
                ),
                // Missing firstname.
                array(array(
                        'solutionid' => 'A',
                        'personnumber' => 'A',
                        'lastname' => 'A',
                        'country' => 'A',
                        'learningpath' => 'A')
                ),
                array(array(
                // Missing lastname.
                        'solutionid' => 'A',
                        'personnumber' => 'A',
                        'firstname' => 'A',
                        'country' => 'A',
                        'learningpath' => 'A')
                ),
        );
    }

    /**
     * This function tests the application of the business roles for WFC users.
     * @dataProvider empty_parameters
     */
    public function test_kronosportal_apply_kronos_business_rules_empty_data($data) {
        $result = kronosportal_apply_kronos_business_rules($data);
        $this->assertFalse($result);
    }

    /**
     * This function tests the application of the business roles for WFC users.
     * @dataProvider missing_parameters
     */
    public function test_kronosportal_apply_kronos_business_rules_missing_data($data) {
        $result = kronosportal_apply_kronos_business_rules($data);
        $this->assertFalse($result);
    }

    /**
     * This function tests updating a Moodle user object with WFC fields.
     */
    public function test_kronosportal_sync_standard_wfc_profile_fields() {
        $wfc = new stdClass();
        $wfc->username = 'wfcTEST1234pn12';
        $wfc->password = 'pwd4321TSET21np';
        $wfc->email = 'wfcTEST1234pn12@wfc.kronos.com';
        $wfc->firstname = 'phpufirstname';
        $wfc->lastname = 'phpulastname';
        $wfc->country = 'CA';
        $wfc->learningpath = 'LP';
        $wfc->restricted = '1';
        $wfc->lang = 'en';

        $muser = new stdClass();
        $muser->username = 'phpunit_value';
        $muser->password = 'phpunit_value';
        $muser->email = 'phpunit_value@u.com';
        $muser->firstname = 'phpunit_value';
        $muser->lastname = 'phpunit_value';
        $muser->country = 'phpunit_value';
        $muser->learningpath = 'phpunit_value';
        $muser->restricted = 'phpunit_value';
        $muser->lang = 'phpunit_value';

        kronosportal_sync_standard_wfc_profile_fields($muser, $wfc);

        $this->assertEquals('phpunit_value', $muser->username);
        $this->assertEquals('pwd4321TSET21np', $muser->password);
        $this->assertEquals('phpunit_value@u.com', $muser->email);
        $this->assertEquals('phpufirstname', $muser->firstname);
        $this->assertEquals('phpulastname', $muser->lastname);
        $this->assertEquals('CA', $muser->country);
        $this->assertEquals('en', $muser->lang);
    }
}