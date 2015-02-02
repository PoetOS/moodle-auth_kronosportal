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
$functions = array (
    'auth_kronosportal_create_token' => array (
        'classname' => 'auth_kronosportal_external',
        'methodname' => 'create_token',
        'classpath' => 'auth/kronosportal/externallib.php',
        'description' => 'Return token to be used for single sign on authentication',
        'type' => 'read'
    ),
    'auth_kronosportal_create_user' => array (
        'classname' => 'auth_kronosportal_external',
        'methodname' => 'create_user',
        'classpath' => 'auth/kronosportal/externallib.php',
        'description' => 'Return status and userid',
        'type' => 'read'
    ),
    'auth_kronosportal_update_user' => array (
        'classname' => 'auth_kronosportal_external',
        'methodname' => 'update_user',
        'classpath' => 'auth/kronosportal/externallib.php',
        'description' => 'Return status and userid',
        'type' => 'read'
    ),
    'auth_kronosportal_logout_by_token' => array (
        'classname' => 'auth_kronosportal_external',
        'methodname' => 'logout_by_token',
        'classpath' => 'auth/kronosportal/externallib.php',
        'description' => 'Delete token and logout session associated with token',
        'type' => 'read'
    ),
    'auth_kronosportal_logout_by_user' => array (
        'classname' => 'auth_kronosportal_external',
        'methodname' => 'logout_by_user',
        'classpath' => 'auth/kronosportal/externallib.php',
        'description' => 'Delete all tokens assigned to user, logout all associated sessions to tokens',
        'type' => 'read'
    )
);

$services = array (
    'Kronos portal webservices' => array (
        'functions' => array (
            'auth_kronosportal_create_token',
            'auth_kronosportal_create_user',
            'auth_kronosportal_update_user',
            'auth_kronosportal_logout_by_token',
            'auth_kronosportal_logout_by_user'
            ),
        'restrictedusers' => 0,
        'enabled' => 1
    )
);
