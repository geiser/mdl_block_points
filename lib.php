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
 * Points block functions definitions.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
	global $DB;
	
	$eventsarray = array();
	
	$eventsarray['\block_game_points\event\points_earned'] = ($showeventname === true ? (\block_game_points\event\points_earned::get_name() . " (\block_game_points\event\points_earned)") : \block_game_points\event\points_earned::get_name());
	
	$game_achievements_installed = $DB->record_exists('block', array('name' => 'game_achievements'));
	if($game_achievements_installed)
	{
		$eventsarray['\block_game_achievements\event\achievement_reached'] = ($showeventname === true ? (\block_game_achievements\event\achievement_reached::get_name() . " (\block_game_achievements\event\achievement_reached)") : \block_game_achievements\event\achievement_reached::get_name());
	}
	
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

function get_block_group_points($blockid, $groupid)
{
	global $DB;
	
	$sql = "SELECT sum(l.points)
				FROM {points_group_log} g
					INNER JOIN {points_log} l ON l.id = g.pointslogid
					INNER JOIN {points_system} s ON s.id = l.pointsystemid
				WHERE g.groupid = :groupid
					AND s.blockinstanceid = :blockinstanceid";
	
	$params['groupid'] = $groupid;
	$params['blockinstanceid'] = $blockid;
	
	$grouppoints = $DB->get_field_sql($sql, $params);
	
	return (empty($grouppoints) ? 0 : $grouppoints);
}

function get_points_system_group_points($pointsystemid, $groupid)
{
	global $DB;
	
	$sql = "SELECT sum(l.points)
				FROM {points_group_log} g
					INNER JOIN {points_log} l ON l.id = g.pointslogid
					INNER JOIN {points_system} s ON s.id = l.pointsystemid
				WHERE g.groupid = :groupid
					AND l.pointsystemid = :pointsystemid";
	
	$params['groupid'] = $groupid;
	$params['pointsystemid'] = $pointsystemid;
	
	$grouppoints = $DB->get_field_sql($sql, $params);
	
	return (empty($grouppoints) ? 0 : $grouppoints);
}