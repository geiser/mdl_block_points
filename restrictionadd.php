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
 * Add points system restriction page.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $DB, $OUTPUT, $PAGE, $USER;
 
require_once('../../config.php');
require_once('block_game_points_restrictionadd_form.php');
 
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
 
$PAGE->set_url('/blocks/game_points/restrictionadd.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('restrictionaddheading', 'block_game_points')); 

$settingsnode = $PAGE->settingsnav->add(get_string('gamepointssettings', 'block_game_points'));
$editurl = new moodle_url('/blocks/game_points/restrictionadd.php', array('id' => $id, 'courseid' => $courseid, 'pointsystemid' => $pointsystemid));
$editnode = $settingsnode->add(get_string('restrictionaddpage', 'block_game_points'), $editurl);
$editnode->make_active();

$addform = new block_game_points_restrictionadd_form($pointsystemid);
if($addform->is_cancelled())
{
    $url = new moodle_url('/blocks/game_points/restrictionmanage.php', array('courseid' => $courseid, 'pointsystemid' => $pointsystemid));
    redirect($url);
}
else if($data = $addform->get_data())
{
	$record = new stdClass();
	$record->pointsystemid = $pointsystemid;
	$record->type = $data->restriction_type;
	if($data->restriction_type == 0) // Se for restrição por pontos
	{
		$block_or_pointsystem_info = explode("::", $data->points_restriction_blockorpointsystemid);
		$type = $block_or_pointsystem_info[0];
		$id = $block_or_pointsystem_info[1];
		
		if($type == 'block')
		{
			$record->prblockid = $id;
		}
		else
		{
			$record->prpointsystemid = $id;
		}
		$record->properator = $data->points_restriction_operator;
		$record->prpoints = $data->points_restriction_points;
		$record->prpointsbetween = isset($data->points_restriction_points_between) ? $data->points_restriction_points_between : null;
	}
	else if($data->restriction_type == 1) // Se for restrição por desbloqueio de conteúdo
	{
		$record->urmust = $data->unlock_restriction_must;
		$record->urunlocksystemid = $data->unlock_restriction_unlocksystemid;
	}
	else // Se for restrição por conquista atingida
	{
		$record->arachievementid = $data->achievements_restriction_achievementid;
	}

	$DB->insert_record('points_system_restriction', $record);
	
    $url = new moodle_url('/blocks/game_points/restrictionmanage.php', array('courseid' => $courseid, 'pointsystemid' => $pointsystemid));
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