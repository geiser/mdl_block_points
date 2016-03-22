<?php

require_once('classes/event/points_earned.php');

function get_events_list($showeventname = false)
{
	$eventsarray = array();
	
	$eventsarray['\block_game_points\event\points_earned'] = ($showeventname === true ? (\block_game_points\event\points_earned::get_name() . " (\block_game_points\event\points_earned)") : \block_game_points\event\points_earned::get_name());
	
	$eventslist = report_eventlist_list_generator::get_non_core_event_list();
	foreach($eventslist as $value)
	{
		$description = explode("\\", explode(".", strip_tags($value['fulleventname']))[0]);
		$eventsarray[$value['eventname']] = ($showeventname === true ? ($description[0] . " (" . $value['eventname'] . ")") : $description[0]);
	}
	
	return $eventsarray;
}