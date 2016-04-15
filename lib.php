<?php

require_once('classes/event/points_earned.php');

define("EQUAL", 0);
define("GREATER", 1);
define("LESS", 2);
define("EQUALORGREATER", 3);
define("EQUALORLESS", 4);
define("BETWEEN", 5);
define("AND_CONNECTIVE", 0);
define("OR_CONNECTIVE", 1);

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

function get_points_system_points($pointsystemid, $userid)
{
	global $DB;
	
	$sql = "SELECT sum(p.points) as points
		FROM
			{points_log} p
		INNER JOIN {logstore_standard_log} l ON p.logid = l.id
		WHERE l.userid = :userid
			AND  p.pointsystemid = :pointsystemid
		GROUP BY l.userid";
		
	$params['userid'] = $userid;
	$params['pointsystemid'] = $pointsystemid;

	$points = $DB->get_record_sql($sql, $params);

	if(empty($points))
	{
		$points = new stdClass();
		$points->points = 0;
	}
	
	return $points->points;
}

function get_block_points($blockid, $userid)
{
	global $DB;
	
	$sql = "SELECT sum(p.points) as points
		FROM
			{points_log} p
		INNER JOIN {logstore_standard_log} l ON p.logid = l.id
		INNER JOIN {points_system} s ON p.pointsystemid = s.id
		WHERE l.userid = :userid
			AND  s.blockinstanceid = :blockinstanceid
		GROUP BY l.userid";
		
	$params['userid'] = $userid;
	$params['blockinstanceid'] = $blockid;

	$points = $DB->get_record_sql($sql, $params);

	if(empty($points))
	{
		$points = new stdClass();
		$points->points = 0;
	}
	
	return $points->points;
}

function get_points($blockid, $userid)
{
	global $DB;

	$points = get_block_points($blockid, $userid);
	
	$links = $DB->get_records('points_link', array('blockinstanceid' => $blockid), '', 'accfromblockinstanceid');
	if(empty($links))
	{
		return $points;
	}
	
	foreach($links as $link)
	{
		$points += get_points($link->accfromblockinstanceid, $userid);
	}
	
	return $points;
}