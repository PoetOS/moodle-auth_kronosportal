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

namespace auth_kronosportal\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The kronosportal_user_profile_solutionid_not_found event class.
 *
 * @property-read array $other {
 *
 *      The user's Solutions ID (level 2 User Set) does not exist.extended date are less than the current date.
 * }
 *
 */
class kronosportal_user_profile_solutionid_not_found extends \core\event\base {
    /**
     * This function initializes class properties.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->context = \context_system::instance();
    }

    /**
     * This function is overridden from the parent class.
     */
    public static function get_name() {
        return get_string('eventkronosportal_user_profile_solutionid_not_found', 'auth_kronosportal');
    }

    /**
     * This fnction is overridden from the parent class.
     */
    public function get_description() {
        return "{$this->other['message']}.";
    }
}