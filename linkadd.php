<?php

global $DB, $OUTPUT, $PAGE, $USER;
 
require_once('../../config.php');
require_once('block_game_points_linkadd_form.php');
 
global $DB;
 
// Required variables
$courseid = required_param('courseid', PARAM_INT);
$blockid = required_param('blockid', PARAM_INT);
 
// Optional variables
$id = optional_param('id', 0, PARAM_INT);
 
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_game_points', $courseid);
}
 
require_login($course);
 
$PAGE->set_url('/blocks/game_points/linkadd.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('linkaddheading', 'block_game_points')); 

$settingsnode = $PAGE->settingsnav->add(get_string('gamepointssettings', 'block_game_points'));
$editurl = new moodle_url('/blocks/game_points/linkadd.php', array('id' => $id, 'courseid' => $courseid, 'blockid' => $blockid));
$editnode = $settingsnode->add(get_string('linkaddpage', 'block_game_points'), $editurl);
$editnode->make_active();

$addform = new block_game_points_linkadd_form($blockid);
if($addform->is_cancelled())
{
    $url = new moodle_url('/my/index.php');
    redirect($url);
}
else if($data = $addform->get_data())
{
	$record = new stdClass();
	$record->blockinstanceid = $blockid;
	$record->accfromblockinstanceid = $data->accfromblockinstanceid;
	$plid = $DB->insert_record('points_link', $record);
	
	$record = new stdClass();
	$record->linkid = $plid;
	$record->processorid = $USER->id;
	$DB->insert_record('points_link_processor', $record);
	
    $url = new moodle_url('/my/index.php');
    redirect($url);
}
else
{
	$toform['blockid'] = $blockid;
	$toform['courseid'] = $courseid;
	$addform->set_data($toform);
	$site = get_site();
	echo $OUTPUT->header();
	$addform->display();
	echo $OUTPUT->footer();
}

?>