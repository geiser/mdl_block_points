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
 * Add Points block link page.
 *
 * @package    block_game_points
 * @copyright  2016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $DB, $OUTPUT, $PAGE, $USER;
 
require_once('../../config.php');
require_once('block_game_points_import_form.php');
 
// Required variables
$blockinstanceid = required_param('blockinstanceid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
 
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_game_points', $courseid);
}
 
require_login($course);
 
$PAGE->set_url('/blocks/game_points/import.php', array('blockinstanceid' => $blockinstanceid,'courseid' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('importheader', 'block_game_points'));
$PAGE->set_title(get_string('importheader', 'block_game_points'));

$settingsnode = $PAGE->settingsnav->add(get_string('gamepointssettings', 'block_game_points'));
$importurl = new moodle_url('/blocks/game_points/import.php', array('blockinstanceid' => $blockinstanceid,'courseid' => $courseid));
$importnode = $settingsnode->add(get_string('importheader', 'block_game_points'), $importurl);
$importnode->make_active();

$form = new block_game_points_import_form();
if($form->is_cancelled())
{
    $url = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($url);
}
else if($data = $form->get_data())
{
	$points_systems = $DB->get_records('points_system', array('blockinstanceid' => $data->from, 'deleted' => 0));
	foreach($points_systems as $points_system)
	{
		// Insert points system
		$points_system_id = $points_system->id;
		unset($points_system->id);
		$points_system->blockinstanceid = $blockinstanceid;
		$new_points_system_id = $DB->insert_record('points_system', $points_system);

		// Insert points system processor
		$points_system_processor = new stdClass();
		$points_system_processor->pointsystemid = $new_points_system_id;
		$points_system_processor->processorid = $USER->id;
		$DB->insert_record('points_system_processor', $points_system_processor);

		// Insert points system restrictions
		$points_system_restrictions = $DB->get_records('points_system_restriction', array('pointsystemid' => $points_system_id));
		foreach($points_system_restrictions as $points_system_restriction)
		{
			unset($points_system_restriction->id);
			$points_system_restriction->pointsystemid = $new_points_system_id;
		}
		$DB->insert_records('points_system_restriction', $points_system_restrictions);

		// Insert points system advanced restrictions
		$points_system_advanced_restrictions = $DB->get_records('points_system_advrestriction', array('pointsystemid' => $points_system_id));
		foreach($points_system_advanced_restrictions as $points_system_advanced_restriction)
		{
			unset($points_system_advanced_restriction->id);
			$points_system_advanced_restriction->pointsystemid = $new_points_system_id;
		}
		$DB->insert_records('points_system_advrestriction', $points_system_advanced_restrictions);
	}
	
    $url = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($url);
}
else
{
	$toform['blockinstanceid'] = $blockinstanceid;
	$toform['courseid'] = $courseid;
	$form->set_data($toform);
	$site = get_site();
	echo $OUTPUT->header();
	$form->display();
	echo $OUTPUT->footer();
}

?>