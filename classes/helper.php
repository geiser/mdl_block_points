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
 * Points block event observer implementation.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
require_once('event/points_earned.php');
require_once($CFG->dirroot.'/blocks/game_points/lib.php');

class block_game_points_helper {

	public static function observer(\core\event\base $event)
	{
        global $DB;
		
        if(!block_game_points_helper::is_student($event->userid)) {
            return;
        }
				
		$pss = $DB->get_records_sql("SELECT * FROM {points_system} WHERE deleted = ? AND ".$DB->sql_compare_text('conditionpoints')." = ". $DB->sql_compare_text('?'), array('deleted' => 0, 'conditionpoints' => $event->eventname));
		
		$got_points = false;
		foreach($pss as $pointsystem)
		{
		
			if(!block_game_points_helper::is_available($pointsystem->restrictions, $event->courseid, $event->userid))
			{
				continue;
			}
			
			if(!validate_advanced_restrictions($pointsystem, $event))
			{
				continue;
			}

			$blockcontextid = $DB->get_field('block_instances', 'parentcontextid', array('id' => $pointsystem->blockinstanceid));
			if(!$blockcontextid) // Acontece se o bloco for apagado
			{
				continue;
			}
			
			$blockcontext = context::instance_by_id($blockcontextid);
			$context = context::instance_by_id($event->contextid);
			if(strpos($context->path, $blockcontext->path) !== 0) // Se o o contexto atual não estiver na hierarquia do contexto do bloco
			{
				continue;
			}
			
					
			$sql = "SELECT sum(p.points) as points
				FROM
					{points_log} p
				INNER JOIN {logstore_standard_log} l ON p.logid = l.id
				WHERE l.userid = :userid
					AND p.pointsystemid = :pointsystemid
				GROUP BY l.userid";	

			$params['userid'] = $event->userid;
			$params['pointsystemid'] = $pointsystem->id;
			
			$psuserpoints = $DB->get_record_sql($sql, $params);
			
			if(isset($pointsystem->pointslimit) && $psuserpoints->points >= $pointsystem->pointslimit)
			{
				continue;
			}
			
			$psrestrictions = $DB->get_records('points_system_restriction', array('pointsystemid' => $pointsystem->id));
			$satisfies_restrictions = $pointsystem->connective == AND_CONNECTIVE ? true : false;
			if(empty($psrestrictions))
			{
				$satisfies_restrictions = true;
			}
			else
			{
				foreach($psrestrictions as $psrestriction)
				{
					if($psrestriction->type == 0) // Restrição por pontos
					{
						$user_points = null;
						if(isset($psrestriction->prblockid)) // Se a restrição for por pontos no bloco
						{
							$user_points = get_points($psrestriction->prblockid, $event->userid);
						}
						else // Se a restrição for por pontos em um sistema de pontos específico
						{
							$user_points = get_points_system_points($psrestriction->prpointsystemid, $event->userid);
						}
						
						if(($psrestriction->properator == EQUAL && $user_points == $psrestriction->prpoints)
							|| ($psrestriction->properator == GREATER && $user_points > $psrestriction->prpoints)
							|| ($psrestriction->properator == LESS && $user_points < $psrestriction->prpoints)
							|| ($psrestriction->properator == EQUALORGREATER && $user_points >= $psrestriction->prpoints)
							|| ($psrestriction->properator == EQUALORLESS && $user_points <= $psrestriction->prpoints)
							|| ($psrestriction->properator == BETWEEN && ($user_points >= $psrestriction->prpoints || $user_points <= $psrestriction->prpointsbetween))) // Se satisfaz a condição
						{
							if($pointsystem->connective == OR_CONNECTIVE) // E se o conectivo for OR
							{
								$satisfies_restrictions = true;
								break;
							}
						}
						else // Se não satisfaz a condição
						{
							if($pointsystem->connective == AND_CONNECTIVE) // E se o conectivo for AND
							{
								$satisfies_restrictions = false;
								break;
							}
						}
					}
					else if($psrestriction->type == 1) // Restrição por conteúdo desbloqueado
					{
						$sql = "SELECT count(u.id) as times
							FROM
								{content_unlock_log} u
							INNER JOIN {logstore_standard_log} l ON u.logid = l.id
							WHERE l.userid = :userid
								AND  u.unlocksystemid = :unlocksystemid
							GROUP BY l.userid";
						$params['unlocksystemid'] = $psrestriction->urunlocksystemid;
						$params['userid'] = $event->userid;
						
						$times = $DB->get_field_sql($sql, $params);

						if(!isset($times))
						{
							$times = 0;
						}
						
						if(($psrestriction->urmust && $times > 0) || (!$psrestriction->urmust && $times == 0)) // Se satisfaz a condição
						{
							if($pointsystem->connective == OR_CONNECTIVE) // E se o conectivo for OR
							{
								$satisfies_restrictions = true;
								break;
							}
						}
						else // Se não satisfaz a condição
						{
							if($pointsystem->connective == AND_CONNECTIVE) // E se o conectivo for AND
							{
								$satisfies_restrictions = false;
								break;
							}
						}
					}
					else // Restrição por conquista atingida
					{
						$unlocked_achievement = $DB->record_exists('achievements_log', array('userid' => $event->userid, 'achievementid' => $psrestriction->arachievementid));
						if($unlocked_achievement) // Se satisfaz a condição
						{
							if($pointsystem->connective == OR_CONNECTIVE) // E se o conectivo for OR
							{
								$satisfies_restrictions = true;
								break;
							}
						}
						else // Se não satisfaz a condição
						{
							if($pointsystem->connective == AND_CONNECTIVE) // E se o conectivo for AND
							{
								$satisfies_restrictions = false;
								break;
							}
						}
					}
				}
			}
				
			if(!$satisfies_restrictions)
			{
				continue;
			}
			
			if($pointsystem->type == 'random')
			{
				$separate = explode("-", $pointsystem->valuepoints);
				$min = $separate[0];
				$max = $separate[1];
				$points = rand($min, $max);
			}
			else if($pointsystem->type == 'fixed')
			{
				$points = $pointsystem->valuepoints;
			}
			else if($pointsystem->type == 'unique')
			{
				$sql = "SELECT count(p.id)
					FROM {points_log} p
						INNER JOIN {logstore_standard_log} l ON p.logid = l.id
					WHERE l.userid = :userid
						AND p.pointsystemid = :pointsystemid";
				$params['userid'] = $event->userid;
				$params['pointsystemid'] = $pointsystem->id;
				
				if($DB->count_records_sql($sql, $params) == 0)
				{
					$points = $pointsystem->valuepoints;
				}
				else
				{
					$points = 0;
				}
			}
			else if($pointsystem->type == 'scalar')
			{
				$sql = "SELECT count(p.id)
					FROM {points_log} p
						INNER JOIN {logstore_standard_log} l ON p.logid = l.id
					WHERE l.userid = :userid
						AND p.pointsystemid = :pointsystemid";
				$params['userid'] = $event->userid;
				$params['pointsystemid'] = $pointsystem->id;
				
				$times = $DB->count_records_sql($sql, $params) + 1;
				$pointsystem->valuepoints = str_replace('x', (string)$times, $pointsystem->valuepoints);
				eval('$points = ' . $pointsystem->valuepoints . ';');
				$points = (int)$points;
				if($points < 0)
				{
					$points = 0;
				}
			}
			
			if($points <= 0 && $pointsystem->type != 'scalar')
			{
				continue;
			}
			
			if((isset($pointsystem->pointslimit)) && ($psuserpoints->points + $points > $pointsystem->pointslimit))
			{
				$points = $pointsystem->pointslimit - $psuserpoints->points;
			}
			
			$manager = get_log_manager();
			$selectreaders = $manager->get_readers('\core\log\sql_reader');
			if ($selectreaders) {
				$reader = reset($selectreaders);
			}
			$selectwhere = "eventname = :eventname
				AND component = :component
				AND action = :action
				AND target = :target
				AND crud = :crud
				AND edulevel = :edulevel
				AND contextid = :contextid
				AND contextlevel = :contextlevel
				AND contextinstanceid = :contextinstanceid
				AND userid = :userid 
				AND anonymous = :anonymous
				AND timecreated = :timecreated";
			$params['eventname'] = $event->eventname;
			$params['component'] = $event->component;
			$params['action'] = $event->action;
			$params['target'] = $event->target;
			$params['crud'] = $event->crud;
			$params['edulevel'] = $event->edulevel;
			$params['contextid'] = $event->contextid;
			$params['contextlevel'] = $event->contextlevel;
			$params['contextinstanceid'] = $event->contextinstanceid;
			$params['userid'] = $event->userid;
			$params['anonymous'] = $event->anonymous;
			$params['timecreated'] = $event->timecreated;

			$logid = $reader->get_events_select($selectwhere, $params, '', 0, 0);
			$logid = array_keys($logid)[0];
			
			$record = new stdClass();
			$record->logid = $logid;
			$record->pointsystemid = $pointsystem->id;
			$record->points = $points;
			$pointslogid = $DB->insert_record('points_log', $record);
			
			if($pointsystem->groupmode != NOGROUPS)
			{
				$groups = null;				
				if(isset($pointsystem->groupingid))
				{
					$groups = groups_get_all_groups($event->courseid, $event->userid, $pointsystem->groupingid);
				}
				else
				{
					$groups = groups_get_all_groups($event->courseid, $event->userid);
				}
				
				$record = new stdClass();
				$record->pointslogid = $pointslogid;
				foreach($groups as $group)
				{
					$record->groupid = $group->id;
					$DB->insert_record('points_group_log', $record);
				}
			}
			
			if(strcmp($event->eventname, '\block_game_points\event\points_earned') != 0)
			{
				$got_points = true;
			}
		}
		
		if($got_points)
		{
			$params = array(
				'context' => $context,
				'other' => array(
					'logid' => $logid, // Arrumar logid
				)
			);
			$event = \block_game_points\event\points_earned::create($params);
			$event->trigger();
		}
		
    }

	private static function is_available($restrictions, $courseid, $userid)
	{
		global $DB;
		
		if(isset($restrictions))
		{
			$tree = new \core_availability\tree(json_decode($restrictions));
			$course = $DB->get_record('course', array('id' => $courseid));
			$info = new \core_availability\mock_info($course, $userid);
			$result = $tree->check_available(false, $info, true, $userid);
			return $result->is_available();
		}
		
		return true;
	}
	
    protected static function is_student($userid) {
        return user_has_role_assignment($userid, 5);
    }

}
