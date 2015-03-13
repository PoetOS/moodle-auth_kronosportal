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

$string['pluginname'] = 'Kronos portal';
$string['auth_kronosportaldescription'] = 'This method validates whether the user belongs to a User Set with a valid subscription.';
$string['tokennotfound'] = 'Token not found';
$string['usermessageinvaliduser'] = 'Please contact your training manager, your account is expired';
$string['usermessageexpired'] = 'Please contact your training manager, your account is expired';
$string['usermessageinvalidsolution'] = 'Please contact your training manager, your account appears to not be assigned to a solutionid';
$string['usermessagemissingdata'] = 'Please contact your training manager, your account is incomplete';
$string['usermessagemissingusersolutionfield'] = 'Please contact your training manager, your account is incomplete';
$string['webserviceerrorinvaliduser'] = 'Invalid data sent.';
$string['webserviceerrorexpired'] = 'SolutionID/Userset is expired.';
$string['webserviceerrorinvalidsolution'] = 'SolutionID/Userset does not exist.';
$string['webserviceerrormissingdata'] = 'The following fields are required to create an account: username, firstname, lastname, customerid, password and email';
$string['webserviceerrortokennotfound'] = 'Token not found';
$string['webserviceerroruserexists'] = 'User exists';
$string['webserviceerrormissingusersolutionfield'] = 'Missing custom Moodle user field for solutionid';
$string['username'] = 'Username';
$string['username_desc'] = 'Map the name of the username field to Moodle profile field.';
$string['header_desc'] = 'Configure the Kronos portal by mapping fields coming from customer portal sites to Moodle custom profile fields.';
$string['header_userset_sub'] = 'User set subscription expiry field mapping';
$string['header_userset_sub_desc'] = 'Select User Set custom fields needed to verify whether the User Set has a valid subscription.';
$string['header_portal_map'] = 'Portal field mapping';
$string['header_portal_map_desc'] = 'Map Moodle custom profile fields to fields that are sent as part of the portal request.  If a field is left blank, it will not get updated.';
$string['header_portal_update'] = 'WFC Portal field update';
$string['header_portal_update_desc'] = 'Indicate which feilds can be updated by the WFC Portal';
$string['no_field_selected'] = 'No field selected';
$string['extension_field'] = 'Extension date';
$string['expiry_field'] = 'Expiry date';
$string['extension_field_desc'] = 'Select the ELIS User Set profile field that will be used for the Extension date.  Only User Set custom fields that are defined as datetime fields will be shown.';
$string['expiry_field_desc'] = 'Select the ELIS User Set profile field that will be used for the Expiry date.  Only User Set custom fields that are defined as datetime fields will be shown.';
$string['custom_text_desc'] = 'Type in the name of the portal field that is mapped to the Moodle custom profile field';
$string['custom_checkbox_desc'] = 'Type in the name of the portal field that is mapped to the Moodle custom profile field.  If the portal field is empty then the checkbox field will be unchecked.  Otherwise it will be checked.';
$string['header_userset_solid'] = 'User Set solution ID';
$string['header_userset_solid_desc'] = 'Identify which ELIS User Set profile field contains the Solution ID';
$string['solutionid'] = 'Solution ID';
$string['solutionid_desc'] = 'Select the User Set custom field that represents the Solution ID';
$string['header_portal_urls'] = 'Redirect URL configuration';
$string['header_portal_urls_desc'] = '';
$string['kronosportal_successurl'] = 'Successful login URL';
$string['kronosportal_successurl_desc'] = 'Redirect to this url upon successful login if url parameter was not passed.';
$string['kronosportal_errorurl'] = 'Failed login URL';
$string['kronosportal_errorurl_desc'] = 'Redirect to this url upon failed login if error parameter was not passed.';
$string['user_field_solutionid'] = 'User field Solution ID';
$string['user_field_solutionid_desc'] = 'Select the Moodle profile field being used to store the user\'s Solution ID.';
$string['eventkronosportal_invalid_configuration'] = 'Invalid configuration settings';
$string['eventkronosportal_user_profile_solutionid_not_found'] = 'User Solution ID profile field not found';
$string['eventkronosportal_userset_not_found'] = 'Solution ID User Set profile field not found';
$string['eventkronosportal_userset_expiry_not_found'] = 'User Set expiry and extension date not found';
$string['eventkronosportal_userset_has_expired'] = 'User User Set expired';
$string['wfc_auth_not_enabled'] = 'Kronos Portal authentication is not enabled.  Unable to login to Moodle.';
$string['wfc_auth_solutionid_not_found'] = 'Customer ID not found.  Unable to login to Moodle.';
$string['wfc_auth_solutionid_expired'] = 'Customer subscription has expired.  Unable to login to Moodle.';
$string['wfc_auth_error_applying_business_rules'] = 'Incomplete account information.  Unable to login to Moodle.';
$string['missingparam'] = 'The following parameters are missing: {$a}';
