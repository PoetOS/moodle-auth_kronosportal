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
 * Kronos portal authentication events tests.
 *
 * @package    auth_kronosportal
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class report_completion_events_testcase
 *
 * @group auth_kronosportal
 *
 * Class for tests related to completion report events.
 *
 * @package    report_completion
 * @copyright  2014 onwards Ankit Agarwal<ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class auth_kronosportal_events_testcase extends advanced_testcase {

    /**
     * Setup testcase.
     */
    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    /**
     * Test the elisuser_not_created event.
     */
    public function test_elisuser_not_created() {
        $user = $this->getDataGenerator()->create_user();
        $message = "Unable to find ELIS user linked to Moodle user.  Moodle username {$user->username}";
        $data = ['other' => ['username' => $user->username, 'message' => $message]];
        $this->event_with_data_test('\auth_kronosportal\event\kronosportal_elisuser_not_created', $data);
    }

    /**
     * Test the invalid_configuration event.
     */
    public function test_invalid_configuration() {
        $this->event_with_data_test('\auth_kronosportal\event\kronosportal_invalid_configuration', []);
    }

    /**
     * Test the learningpath_not_exist event.
     */
    public function test_learningpath_not_exist() {
        $user = $this->getDataGenerator()->create_user();
        $message = "Unable to create user (username: {$user->username}).";
        $data = ['other' => ['username' => $user->username, 'message' => $message, 'wfc_learning_path' => 'learningpath',
            'solution_userset_name' => 'solutionuserset']];
        $this->event_with_data_test('\auth_kronosportal\event\kronosportal_learningpath_not_exist', $data);
    }

    /**
     * Test the user_profile_solutionid_not_found event.
     */
    public function test_user_profile_solutionid_not_found() {
        $user = $this->getDataGenerator()->create_user();
        $solutionid = 73;
        $message = "Unable to find userid: {$user->id} custom profile fieldid: {$solutionid}";
        $data = ['userid' => $user->id, 'other' => ['message' => $message, 'user_moodle_custom_field_id' => $solutionid]];
        $this->event_with_data_test('\auth_kronosportal\event\kronosportal_user_profile_solutionid_not_found', $data);
    }

    /**
     * Test the userset_expiry_not_found event.
     */
    public function test_userset_expiry_not_found() {
        $user = $this->getDataGenerator()->create_user();
        $context = \context_system::instance();
        $datefieldid1 = 199;
        $datefieldid2 = 86;
        $message = "Login attempt by {$user->username}in context (Context Instance ID: {$context->id}.";
        $data = ['other' => ['username' => $user->username, 'message' => $message, 'context_id' => $context->id,
            'user_set_expriy_date_field_id' => $datefieldid1, 'user_set_extension_date_field_id' => $datefieldid2, ]];
        $this->event_with_data_test('\auth_kronosportal\event\kronosportal_userset_expiry_not_found', $data);
    }

    /**
     * Test the userset_expiry_not_found event.
     */
    public function test_userset_has_expired() {
        $user = $this->getDataGenerator()->create_user();
        $context = \context_system::instance();
        $usersetname = 'user set one';
        $datefieldid1 = 199;
        $datefieldid2 = 86;
        $currenttime = time();
        $message = "Login attempt by {$user->username}.  User Set {$usersetname} (Contextid {$context->id}) has expired";
        $data = ['other' => ['username' => $user->username, 'message' => $message, 'context_id' => $context->id,
            'user_set_name' => $usersetname, 'user_set_expriy_date_field_id' => $datefieldid1,
            'user_set_extension_date_field_id' => $datefieldid2, 'current_time' => $currenttime]];
        $this->event_with_data_test('\auth_kronosportal\event\kronosportal_userset_has_expired', $data);
    }

    /**
     * Test the userset_expiry_not_found event.
     */
    public function test_userset_not_found() {
        $user = $this->getDataGenerator()->create_user();
        $message = "Login attempt by {$user->username}.";
        $solutionfieldid = 1023;
        $solutionfieldvalue = 'solution value';
        $data = ['other' => ['username' => $user->username, 'message' => $message,
            'user_set_solutionid_field_id' => $solutionfieldid, 'user_solutionid_value' => $solutionfieldvalue]];
        $this->event_with_data_test('\auth_kronosportal\event\kronosportal_userset_not_found', $data);
    }

    /**
     * Helper function to test the specified event with the specified data.
     */
    private function event_with_data_test($eventpath, $data) {
        $event = $eventpath::create($data);
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        $this->assertInstanceOf($eventpath, $event);
        $eventdata = $event->get_data();
        $this->assertEquals('r', $eventdata['crud']);
        $this->assertEquals(\core\event\base::LEVEL_PARTICIPATING, $eventdata['edulevel']);
        $this->assertEquals(\context_system::instance()->id, $eventdata['contextid']);

        foreach ($data as $name1 => $datum) {
            if (is_array($datum)) {
                foreach ($datum as $name2 => $value) {
                    $this->assertEquals($value, $eventdata[$name1][$name2]);
                }
            } else {
                $this->assertEquals($datum, $eventdata[$name1]);
            }
        }
    }
}