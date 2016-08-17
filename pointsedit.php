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
 * Edit points page.
 *
 * @package    block_game_points
 * @copyright  2016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $DB, $OUTPUT, $PAGE, $USER;
 
require_once('../../config.php');
require_once('block_game_points_pointsedit_form.php');
 
// Required variables
$courseid = required_param('courseid', PARAM_INT);
$pointslogid = required_param('pointslogid', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_game_points', $courseid);
}
 
require_login($course);
 
$PAGE->set_url('/blocks/game_points/pointsedit.php', array('courseid' => $courseid, 'pointslogid' => $pointslogid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('pointseditheading', 'block_game_points')); 
$PAGE->set_title(get_string('pointseditheading', 'block_game_points'));

$settingsnode = $PAGE->settingsnav->add(get_string('gamepointssettings', 'block_game_points'));
$editurl = new moodle_url('/blocks/game_points/pointsedit.php', array('courseid' => $courseid, 'pointslogid' => $pointslogid));
$editnode = $settingsnode->add(get_string('pointseditheading', 'block_game_points'), $editurl);
$editnode->make_active();

$editform = new block_game_points_pointsedit_form();
if($editform->is_cancelled())
{
   	$url = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($url);
}
else if($data = $editform->get_data())
{
	$points_log = new stdClass();
	$points_log->id = $pointslogid;
	$points_log->points = $data->points;
	$DB->update_record('points_log', $points_log);
	
   	$url = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($url);
}
else
{
	$toform['pointslogid'] = $pointslogid;
	$toform['courseid'] = $courseid;
	$editform->set_data($toform);
	$site = get_site();
	echo $OUTPUT->header();
	$editform->display();
	echo $OUTPUT->footer();
}

?>