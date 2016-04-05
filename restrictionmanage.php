<?php

global $DB, $OUTPUT, $PAGE, $USER;
 
require_once('../../config.php');
require_once('block_game_points_restrictionmanage_form.php');
 
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
 
$PAGE->set_url('/blocks/game_points/restrictionmanage.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('restrictionmanageheading', 'block_game_points')); 

$settingsnode = $PAGE->settingsnav->add(get_string('gamepointssettings', 'block_game_points'));
$editurl = new moodle_url('/blocks/game_points/restrictionmanage.php', array('id' => $id, 'courseid' => $courseid, 'pointsystemid' => $pointsystemid));
$editnode = $settingsnode->add(get_string('restrictionmanagepage', 'block_game_points'), $editurl);
$editnode->make_active();

$addform = new block_game_points_restrictionmanage_form($pointsystemid);
if($addform->is_cancelled())
{

}
else if($data = $addform->get_data())
{
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