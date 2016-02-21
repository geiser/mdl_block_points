<?php

global $DB, $OUTPUT, $PAGE, $USER;
 
require_once('../../config.php');
require_once('block_game_points_form.php');
 
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
 
$PAGE->set_url('/blocks/game_points/addpointssystem.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('addpointsystemheading', 'block_game_points')); 

$settingsnode = $PAGE->settingsnav->add(get_string('gamepointssettings', 'block_game_points'));
$editurl = new moodle_url('/blocks/game_points/addpointsystem.php', array('id' => $id, 'courseid' => $courseid, 'blockid' => $blockid));
$editnode = $settingsnode->add(get_string('addpointsystempage', 'block_game_points'), $editurl);
$editnode->make_active();

$addform = new block_game_points_form();
if($addform->is_cancelled())
{
    $url = new moodle_url('/my/index.php');
    redirect($url);
}
else if($data = $addform->get_data())
{
	$record = new stdClass();
	$record->type = $data->type;
	$record->conditionpoints = $data->event;
	$record->valuepoints = $data->value;
	$record->eventdescription = empty($data->description) ? null : $data->description;
	$record->pointslimit = empty($data->pointslimit) ? null : $data->pointslimit;
	$record->blockinstanceid = $blockid;
	$record->restrictions = empty($data->availabilityconditionsjson) ? null : $data->availabilityconditionsjson;
	$record->groupmode = $data->groupmode;
	$record->groupingid = ($data->groupmode == NOGROUPS || $data->groupingid == 0) ? null : $data->groupingid;
	$psid = $DB->insert_record('points_system', $record);
	
	$record = new stdClass();
	$record->pointsystemid = $psid;
	$record->processorid = $USER->id;
	$DB->insert_record('points_system_processor', $record);
	
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