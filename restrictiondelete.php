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
 * Delete points system restriction page.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $DB, $OUTPUT, $PAGE, $USER;
 
require_once('../../config.php');
require_once('block_game_points_restrictiondelete_form.php');
 
// Required variables
$courseid = required_param('courseid', PARAM_INT);
$restrictionid = required_param('restrictionid', PARAM_INT);
 
// Optional variables
$id = optional_param('id', 0, PARAM_INT);
 
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_game_points', $courseid);
}
 
require_login($course);
 
$PAGE->set_url('/blocks/game_points/restrictiondelete.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('restrictiondeleteheading', 'block_game_points')); 

$settingsnode = $PAGE->settingsnav->add(get_string('gamepointssettings', 'block_game_points'));
$editurl = new moodle_url('/blocks/game_points/restrictiondelete.php', array('id' => $id, 'courseid' => $courseid, 'restrictionid' => $restrictionid));
$editnode = $settingsnode->add(get_string('restrictiondeletepage', 'block_game_points'), $editurl);
$editnode->make_active();

$addform = new block_game_points_restrictiondelete_form();
if($addform->is_cancelled())
{
	$psid = $DB->get_field('points_system_restriction', 'pointsystemid', array('id' => $restrictionid));
	
    $url = new moodle_url('/blocks/game_points/restrictionmanage.php', array('courseid' => $courseid, 'pointsystemid' => $psid));
    redirect($url);
}
else if($data = $addform->get_data())
{
	$psid = $DB->get_field('points_system_restriction', 'pointsystemid', array('id' => $restrictionid));
	
	$DB->delete_records('points_system_restriction', array('id' => $restrictionid));
	
    $url = new moodle_url('/blocks/game_points/restrictionmanage.php', array('courseid' => $courseid, 'pointsystemid' => $psid));
    redirect($url);
}
else
{
	$toform['restrictionid'] = $restrictionid;
	$toform['courseid'] = $courseid;
	$addform->set_data($toform);
	$site = get_site();
	echo $OUTPUT->header();
	$addform->display();
	echo $OUTPUT->footer();
}

?>