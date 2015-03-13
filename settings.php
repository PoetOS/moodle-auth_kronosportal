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

require_once(dirname(__FILE__).'/lib.php');

$langfile = AUTH_KRONOSPORTAL_COMP_NAME;
$settings->add(new \admin_setting_heading('kronosportal_heading', '', get_string('header_desc', $langfile)));

// Add a single drop down for selecting the Course Description custom field that denotes a featured course.
$fields = field::get_for_context_level('cluster');
$customfieldsdatetime = array();
$customfieldtext = array();
$moodlecustomfields = array();
$solmoodlecustomfield = array();

if ($fields->valid() === true) {
    foreach ($fields as $field) {
        if ('datetime' == $field->datatype) {
            $customfieldsdatetime[$field->id] = $field->name;
        } else if ('char' == $field->datatype) {
            $customfieldtext[$field->id] = $field->name;
        }
    }
}

// If the custom fields array is empty add a 'none' value.
if (empty($customfieldsdatetime)) {
    $customfieldsdatetime['0'] = get_string('no_field_selected', $langfile);
}

if (empty($customfieldtext)) {
    $customfieldtext['0'] = get_string('no_field_selected', $langfile);
}

// Find all Moodle profile fields for types text.
$rs = $DB->get_recordset_select('user_info_field', "datatype = 'text' OR datatype = 'checkbox'");

if ($rs->valid()) {
    foreach ($rs as $customfield) {
        if ('text' == $customfield->datatype) {
            $solmoodlecustomfield[$customfield->id] = format_string($customfield->name);

            $moodlecustomfields[$customfield->shortname] = format_string($customfield->name);
        } else {
            $moodlecustomfields[$customfield->shortname] = format_string($customfield->name);
        }
    }
}

$rs->close();

if (empty($solmoodlecustomfield)) {
    $solmoodlecustomfield['0'] = get_string('no_field_selected', $langfile);
}

$title = get_string('header_userset_solid', $langfile);
$desc = get_string('header_userset_solid_desc', $langfile);
$settings->add(new \admin_setting_heading('kronosportal_userset_solid', $title, $desc));

$title = get_string('user_field_solutionid', $langfile);
$desc = get_string('user_field_solutionid_desc', $langfile);
$setting = new \admin_setting_configselect('user_field_solutionid', $title, $desc, null, $solmoodlecustomfield);
$setting->plugin = AUTH_KRONOSPORTAL_COMP_NAME;
$settings->add($setting);

$title = get_string('solutionid', $langfile);
$desc = get_string('solutionid_desc', $langfile);
$setting = new \admin_setting_configselect('solutionid', $title, $desc, null, $customfieldtext);
$setting->plugin = AUTH_KRONOSPORTAL_COMP_NAME;
$settings->add($setting);

$title = get_string('header_userset_sub', $langfile);
$desc = get_string('header_userset_sub_desc', $langfile);
$settings->add(new \admin_setting_heading('kronosportal_userset_sub', $title, $desc));

$title = get_string('expiry_field', $langfile);
$desc = get_string('expiry_field_desc', $langfile);
$setting = new \admin_setting_configselect('expiry', $title, $desc, null, $customfieldsdatetime);
$setting->plugin = AUTH_KRONOSPORTAL_COMP_NAME;
$settings->add($setting);

$title = get_string('extension_field', $langfile);
$desc = get_string('extension_field_desc', $langfile);
$setting = new \admin_setting_configselect('extension', $title, $desc, null, $customfieldsdatetime);
$setting->plugin = AUTH_KRONOSPORTAL_COMP_NAME;
$settings->add($setting);

$title = get_string('header_portal_map', $langfile);
$desc = get_string('header_portal_map_desc', $langfile);
$settings->add(new \admin_setting_heading('kronosportal_userset_map', $title, $desc));

foreach ($moodlecustomfields as $shortname => $name) {
    $setting = new \admin_setting_configtext('profile_field_'.$shortname, $name, '', '', PARAM_TEXT);
    $setting->plugin = AUTH_KRONOSPORTAL_COMP_NAME;
    $settings->add($setting);
}

$title = get_string('header_portal_update', $langfile);
$desc = get_string('header_portal_update_desc', $langfile);
$settings->add(new \admin_setting_heading('kronosportal_update_map', $title, $desc));

foreach ($moodlecustomfields as $shortname => $name) {
    $setting = new \admin_setting_configcheckbox('update_profile_field_'.$shortname, $name, '', '', PARAM_TEXT);
    $setting->plugin = AUTH_KRONOSPORTAL_COMP_NAME;
    $settings->add($setting);
}

$title = get_string('header_portal_urls', $langfile);
$desc = get_string('header_portal_urls_desc', $langfile);
$settings->add(new \admin_setting_heading('kronosportal_urls', $title, $desc));

$field = 'kronosportal_errorurl';
$title = get_string($field, $langfile);
$desc = get_string($field.'_desc', $langfile);
$setting = new \admin_setting_configtext($field, $title, $desc, '', PARAM_TEXT);
$setting->plugin = AUTH_KRONOSPORTAL_COMP_NAME;
$settings->add($setting);

$field = 'kronosportal_successurl';
$title = get_string($field, $langfile);
$desc = get_string($field.'_desc', $langfile);
$setting = new \admin_setting_configtext($field, $title, $desc, '', PARAM_TEXT);
$setting->plugin = AUTH_KRONOSPORTAL_COMP_NAME;
$settings->add($setting);
