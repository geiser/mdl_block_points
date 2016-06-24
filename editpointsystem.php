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
 * Edit points system page.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $DB, $OUTPUT, $PAGE, $USER;
 
require_once('../../config.php');
require_once('block_game_points_editps_form.php');
 
global $DB;
 
// Required variables
$courseid = required_param('courseid', PARAM_INT);
$pointsystemid = required_param('pointsystemid', PARAM_INT);
 
// Optional variables
$id = optional_param('id', 0, PARAM_INT);
 
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_game_points', $courseid);
}
 
require_login($course);
 
$PAGE->set_url('/blocks/game_points/editpointsystem.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('editpointsystemheading', 'block_game_points')); 

$settingsnode = $PAGE->settingsnav->add(get_string('gamepointssettings', 'block_game_points'));
$editurl = new moodle_url('/blocks/game_points/editpointsystem.php', array('id' => $id, 'courseid' => $courseid, 'pointsystemid' => $pointsystemid));
$editnode = $settingsnode->add(get_string('editpointsystempage', 'block_game_points'), $editurl);
$editnode->make_active();

$addform = new block_game_points_editps_form($pointsystemid, $courseid);
if($addform->is_cancelled())
{
    $url = new moodle_url('/my/index.php');
    redirect($url);
}
else if($data = $addform->get_data())
{
	$oldpointsystem = $DB->get_record('points_system', array('id' => $pointsystemid));
	
	$record = new stdClass();
	$record->id = $oldpointsystem->id;
	$record->type = $oldpointsystem->type;
	$record->conditionpoints = $oldpointsystem->conditionpoints;
	$record->valuepoints = $oldpointsystem->valuepoints;
	$record->eventdescription = $oldpointsystem->eventdescription;
	$record->pointslimit = $oldpointsystem->pointslimit;
	$record->blockinstanceid = $oldpointsystem->blockinstanceid;
	$record->restrictions = $oldpointsystem->restrictions;
	$record->groupmode = $oldpointsystem->groupmode;
	$record->groupingid = $oldpointsystem->groupingid;
	$record->deleted = 1;
	$DB->update_record('points_system', $record);
	
	$record = new stdClass();
	$record->type = $data->type;
	$record->conditionpoints = $data->event;
	$record->valuepoints = $data->value;
	$record->eventdescription = empty($data->description) ? null : $data->description;
	$record->pointslimit = empty($data->pointslimit) ? null : $data->pointslimit;
	$record->blockinstanceid = $oldpointsystem->blockinstanceid;
	$record->restrictions = $oldpointsystem->restrictions;
	$record->groupmode = $data->groupmode;
	$record->groupingid = ($data->groupmode == NOGROUPS || $data->groupingid == 0) ? null : $data->groupingid;
	$psid = $DB->insert_record('points_system', $record);
	
	$record = new stdClass();
	$record->pointsystemid = $psid;
	$record->processorid = $USER->id;
	$DB->insert_record('points_system_processor', $record);
	
	// Update restrictions to match the new points system id
	$sql = 'UPDATE {points_system_restriction}
				SET pointsystemid = :newpointsystemid
				WHERE pointsystemid = :oldpointsystemid';
	$params['newpointsystemid'] = $psid;
	$params['oldpointsystemid'] = $oldpointsystem->id;
	$DB->execute($sql, $params);
	
	// Update restrictions to match the new restriction points system id
	$sql = 'UPDATE {points_system_restriction}
				SET prpointsystemid = :newpointsystemid
				WHERE prpointsystemid = :oldpointsystemid';
	$params['newpointsystemid'] = $psid;
	$params['oldpointsystemid'] = $oldpointsystem->id;
	$DB->execute($sql, $params);
	
    $url = new moodle_url('/my/index.php');
    redirect($url);
}
else
{
	$toform['pointsystemid'] = $pointsystemid;
	$toform['courseid'] = $courseid;
	$addform->set_data($toform);
	$site = get_site();
	echo $OUTPUT->header();
	$addform->display();
	echo $OUTPUT->footer();
}

?>