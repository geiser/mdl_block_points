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
 * Points block definition.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/game_points/classes/helper.php');
require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
require_once($CFG->dirroot.'/blocks/game_points/lib.php');

class block_game_points extends block_base
{
	
    public function init()
	{
        $this->title = get_string('title', 'block_game_points');
    }

	public function applicable_formats()
	{
        return array(
            'all'    => true
        );
    }
	
	public function instance_allow_multiple()
	{
	  return true;
	}
	
    public function get_content()
	{
		global $DB, $USER;
		$this->content = new stdClass;
		
		if(user_has_role_assignment($USER->id, 5)) // Verificar se é estudante? inverter e colcoar contexto pode ser melhor
		{
			$showblock = false;
			/*if(context::instance_by_id($this->instance->parentcontextid)->contextlevel < $this->page->context->contextlevel && $this->has_points_systems($this->instance->id))
			{
				$showblock = true;
			}
			else if($this->has_points_systems($this->instance->id, true))
			{
				$showblock = true;
			}*/
			
			$eventsarray = null;
			if($this->page->course->id != 1) // Pagina de curso
			{
				$eventsarray = get_events_list();
				$pss = $this->get_points_systems();
					
				if(!empty($pss))
				{
					$pointslist = '';
										
					foreach($pss as $pointsystem)
					{
						
						if(!$this->is_available($pointsystem->restrictions, $this->page->course->id, $USER->id))
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

						$params['userid'] = $USER->id;
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
										$user_points = get_points($psrestriction->prblockid, $USER->id);
									}
									else // Se a restrição for por pontos em um sistema de pontos específico
									{
										$user_points = get_points_system_points($psrestriction->prpointsystemid, $USER->id);
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
									$params['userid'] = $USER->id;
									
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
									$unlocked_achievement = $DB->record_exists('achievements_log', array('userid' => $USER->id, 'achievementid' => $psrestriction->arachievementid));
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
							$points = $pointsystem->valuepoints;
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
							$params['userid'] = $USER->id;
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
							$params['userid'] = $USER->id;
							$params['pointsystemid'] = $pointsystem->id;
							
							$times = $DB->count_records_sql($sql, $params) + 1;
							$pointsystem->valuepoints = str_replace('x', (string)$times, $pointsystem->valuepoints);
							eval('$points = ' . $pointsystem->valuepoints . ';');
							$points = (int)$points;
						}
						
						if($points > 0)
						{
							if(isset($pointsystem->pointslimit))
							{
								if($pointsystem->type == 'random')
								{
									$separate = explode("-", $pointsystem->valuepoints);
									$min = $separate[0];
									$max = $separate[1];
									
									if($psuserpoints->points + $min > $pointsystem->pointslimit)
									{
										$min = $pointsystem->pointslimit - $psuserpoints->points;
									}
									if($psuserpoints->points + $max > $pointsystem->pointslimit)
									{
										$max = $pointsystem->pointslimit - $psuserpoints->points;
									}
									
									$points = $min . "-" . $max;
								}
								else
								{
									if($psuserpoints->points + $points > $pointsystem->pointslimit)
									{
										$points = $pointsystem->pointslimit - $psuserpoints->points;
									}
								}
							}
							
							$eventdescription = is_null($pointsystem->eventdescription) ? $eventsarray[$pointsystem->conditionpoints] : $pointsystem->eventdescription;
							$pointslist = $pointslist . '<li>' . $points . ' pontos por ' . $eventdescription . '</li>';
						}
						
					}
					
					if(strlen($pointslist) > 0)
                    {
						$this->content->footer = 'Você pode ganhar:<ul>' . $pointslist . '</ul>';
                        $showblock = true;
                        // ugly hack to avoid displaying points that we can win
                        if ($this->title == 'Pontos no curso') {
                            $this->content->footer = '';
                        }
					}	
				}
			}
			
			$points = get_points($this->instance->id, $USER->id);
			$showblock = $showblock || $points > 0;
			if($showblock)
			{
				$this->content->text = 'Seus pontos: <br><p align="center"><font size="28">' . $points . '</font></center>';
				
				if($this->page->course->id != 1) // Pagina de curso
				{
					$this->content->text = $this->content->text . $this->get_group_points_message();
					
					if(isset($this->config))
					{
						$lastpointsnumber = isset($this->config->lastpointsnumber) ? $this->config->lastpointsnumber : 1;
					}
					else
					{
						$lastpointsnumber = 0;
					}
					
					if($lastpointsnumber > 0)
					{
						$sql = "SELECT p.logid as logid, sum(p.points) as points, s.eventdescription as eventdescription, s.conditionpoints as conditionpoints
							FROM {points_log} p
							INNER JOIN {logstore_standard_log} l ON p.logid = l.id
							INNER JOIN {points_system} s ON p.pointsystemid = s.id
							WHERE l.userid = :userid
								AND l.courseid = :courseid
								AND s.blockinstanceid = :blockinstanceid
								AND p.points > 0
							GROUP BY p.logid
							ORDER BY p.logid DESC";

						$params['userid'] = $USER->id;
						$params['courseid'] = $this->page->course->id;
						$params['blockinstanceid'] = $this->instance->id;

						$lastpoints = $DB->get_records_sql($sql, $params, 0, isset($this->config->lastpointsnumber) ? $this->config->lastpointsnumber : 1);
						
						if(!empty($lastpoints))
						{
							$lastpointslist = '';
							foreach($lastpoints as $lp)
							{
								$eventdescription = is_null($lp->eventdescription) ? $eventsarray[$lp->conditionpoints] : $lp->eventdescription;
								$lastpointslist = $lastpointslist . '<li>' . $lp->points . ' pontos por ' . $eventdescription . '</li>';
							}
							$this->content->footer = $this->content->footer . 'Você ganhou recentemente:<ul>' . $lastpointslist . '</ul>';
						}
					}
				}
			}
			else
			{
				$this->content = new stdClass;
			}
		}
		else
		{
			$this->content->text = 'Você não é um estudante neste contexto!<br>Seus pontos: <br><p align="center"><font size="28">' . get_points($this->instance->id, $USER->id) . '</font></center>';
        }

        //uglyhack to remove what is in parenteses
        if(user_has_role_assignment($USER->id, 5)) {
            // Verificar se é estudante? inverter e colcoar contexto pode ser melhor
            $this->title = preg_replace(array("/\(\w+\)/"), array(""), $this->title);
        }
		return $this->content;
    }

	public function specialization()
	{
		if(isset($this->config))
		{
			if(empty($this->config->title))
			{
				$this->title = get_string('title', 'block_game_points');            
			}
			else
			{
				$this->title = $this->config->title;
			}
		}
	}
	
    public function has_config()
	{
        return true;
    }
	
	private function get_points_systems($blockinstanceid = 0)
	{
		global $DB;

		if(!$blockinstanceid)
		{
			$blockinstanceid = $this->instance->id;
		}
		$points_systems = $DB->get_records('points_system', array('deleted' => 0, 'blockinstanceid' => $blockinstanceid));
		
		$links = $DB->get_records('points_link', array('blockinstanceid' => $blockinstanceid));
		foreach($links as $link)
		{
			$points_systems += $this->get_points_systems($link->accfromblockinstanceid);
		}

		return $points_systems;
	}

	private function is_available($restrictions, $courseid, $userid)
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
	
	private function get_groupmode()
	{
		global $DB;
		
		$sql = "SELECT max(groupmode) as groupmode
			FROM
				{points_system}
			WHERE deleted = :deleted
				AND  blockinstanceid = :blockinstanceid";
			
		$params['deleted'] = 0;
		$params['blockinstanceid'] = $this->instance->id;
		
		$groupmode = $DB->get_field_sql($sql, $params);
		
		return $groupmode;		
	}
	
	private function get_groupids()
	{
		global $DB;
		
		$sql = "SELECT DISTINCT(groupid)
			FROM {groupings_groups}
			WHERE groupingid IN
				(
					SELECT DISTINCT(groupingid)
						FROM {points_system}
						WHERE groupingid IS NOT NULL
							AND deleted = :deleted
							AND blockinstanceid = :blockinstanceid
				)";
				
		$params['deleted'] = 0;
		$params['blockinstanceid'] = $this->instance->id;
		
		$groupids = $DB->get_fieldset_sql($sql, $params);
		
		return $groupids;
	}
	
	private function get_group_points_message()
	{
		global $USER, $DB;
		
		$detailedview = (isset($this->config->usedetailedview) ? (bool)$this->config->usedetailedview : false);
		
		$message = '<p align="left">';
		
		$groupmode = $this->get_groupmode();
		if($groupmode == SEPARATEGROUPS)
		{
			$usergroups = groups_get_all_groups($this->page->course->id, $USER->id);
			$blockgroupids = $this->get_groupids();
			
			if(empty($blockgroupids)) // Se agrupamentos não foram especificados
			{
				foreach($usergroups as $group)
				{
					//Calcular e imprimir os pontos do grupo!
					$message = $message . '<p>Pontos do seu grupo ' . $group->name . ': ' . $this->get_group_points($group->id);
					
					if($detailedview)
					{
						$sql = 'SELECT l.pointsystemid as pointsystemid, sum(l.points) as points
									FROM {points_group_log} g
										INNER JOIN {points_log} l ON l.id = g.pointslogid
									WHERE g.groupid = :groupid
									GROUP BY l.pointsystemid';
									
						$params['groupid'] = $group->id;
						
						$result = $DB->get_records_sql($sql, $params);
						
						$message = $message . '<ul>';
						foreach($result as $pointsystem)
						{
							$message = $message . '<li>' . $pointsystem->points . ' pontos pelo sistema ' . $pointsystem->pointsystemid . '</li>';
						}
						$message = $message . '</ul>';
					}
					
					$message = $message . '</p>';
				}
			}
			else // Se foram
			{
				foreach($usergroups as $group)
				{
					if(in_array($group->id, $blockgroupids))
					{
						//Calcular e imprimir os pontos do grupo!
						$message = $message . '<p>Pontos do seu grupo ' . $group->name . ': ' . $this->get_group_points($group->id);
						
						if($detailedview)
						{
							$sql = 'SELECT l.pointsystemid as pointsystemid, sum(l.points) as points
										FROM {points_group_log} g
											INNER JOIN {points_log} l ON l.id = g.pointslogid
										WHERE g.groupid = :groupid
										GROUP BY l.pointsystemid';
										
							$params['groupid'] = $group->id;
							
							$result = $DB->get_records_sql($sql, $params);
							
							$message = $message . '<ul>';
							foreach($result as $pointsystem)
							{
								$message = $message . '<li>' . $pointsystem->points . ' pontos pelo sistema ' . $pointsystem->pointsystemid . '</li>';
							}
							$message = $message . '</ul>';
						}
						
						$message = $message . '</p>';
					}
				}
			}
		}
		else if($groupmode == VISIBLEGROUPS)
		{
			$usergroups = groups_get_all_groups($this->page->course->id, $USER->id);
			$blockgroupids = $this->get_groupids();
			
			if(empty($blockgroupids)) // Se agrupamentos não foram especificados
			{
				$allgroups = groups_get_all_groups($this->page->course->id);

				foreach($usergroups as $group)
				{
					unset($allgroups[$group->id]);
					
					//Calcular e imprimir os pontos do grupo!
					$message = $message . '<p>Pontos do seu grupo ' . $group->name . ': ' . $this->get_group_points($group->id);
					
					if($detailedview)
					{
						$sql = 'SELECT l.pointsystemid as pointsystemid, sum(l.points) as points
									FROM {points_group_log} g
										INNER JOIN {points_log} l ON l.id = g.pointslogid
									WHERE g.groupid = :groupid
									GROUP BY l.pointsystemid';
									
						$params['groupid'] = $group->id;
						
						$result = $DB->get_records_sql($sql, $params);
						
						$message = $message . '<ul>';
						foreach($result as $pointsystem)
						{
							$message = $message . '<li>' . $pointsystem->points . ' pontos pelo sistema ' . $pointsystem->pointsystemid . '</li>';
						}
						$message = $message . '</ul>';
					}
					
					$message = $message . '</p>';
					
				}
				
				foreach($allgroups as $group)
				{
					//Calcular e imprimir os pontos do grupo!
					$message = $message . '<p>Pontos do grupo ' . $group->name . ': ' . $this->get_group_points($group->id);
					
					if($detailedview)
					{
						$sql = 'SELECT l.pointsystemid as pointsystemid, sum(l.points) as points
									FROM {points_group_log} g
										INNER JOIN {points_log} l ON l.id = g.pointslogid
									WHERE g.groupid = :groupid
									GROUP BY l.pointsystemid';
									
						$params['groupid'] = $group->id;
						
						$result = $DB->get_records_sql($sql, $params);
						
						$message = $message . '<ul>';
						foreach($result as $pointsystem)
						{
							$message = $message . '<li>' . $pointsystem->points . ' pontos pelo sistema ' . $pointsystem->pointsystemid . '</li>';
						}
						$message = $message . '</ul>';
					}
					
					$message = $message . '</p>';
				}
				
				
			}
			else // Se foram
			{
				foreach($usergroups as $group)
				{
					$found = array_search($group->id, $blockgroupids);
					if($found !== false)
					{
						unset($blockgroupids[$found]);
						
						//Calcular e imprimir os pontos do grupo!
						$message = $message . '<p>Pontos do seu grupo ' . $group->name . ': ' . $this->get_group_points($group->id);
						
						if($detailedview)
						{
							$sql = 'SELECT l.pointsystemid as pointsystemid, sum(l.points) as points
										FROM {points_group_log} g
											INNER JOIN {points_log} l ON l.id = g.pointslogid
										WHERE g.groupid = :groupid
										GROUP BY l.pointsystemid';
										
							$params['groupid'] = $group->id;
							
							$result = $DB->get_records_sql($sql, $params);
							
							$message = $message . '<ul>';
							foreach($result as $pointsystem)
							{
								$message = $message . '<li>' . $pointsystem->points . ' pontos pelo sistema ' . $pointsystem->pointsystemid . '</li>';
							}
							$message = $message . '</ul>';
						}
						
						$message = $message . '</p>';
					}
				}
				
				foreach($blockgroupids as $group)
				{
					//Calcular e imprimir os pontos do grupo!
					$message = $message . '<br>Pontos do grupo ' . groups_get_group_name($group) . ': ' . $this->get_group_points($group);
					
					if($detailedview)
					{
						$sql = 'SELECT l.pointsystemid as pointsystemid, sum(l.points) as points
									FROM {points_group_log} g
										INNER JOIN {points_log} l ON l.id = g.pointslogid
									WHERE g.groupid = :groupid
									GROUP BY l.pointsystemid';
									
						$params['groupid'] = $group->id;
						
						$result = $DB->get_records_sql($sql, $params);
						
						$message = $message . '<ul>';
						foreach($result as $pointsystem)
						{
							$message = $message . '<li>' . $pointsystem->points . ' pontos pelo sistema ' . $pointsystem->pointsystemid . '</li>';
						}
						$message = $message . '</ul>';
					}
					
					$message = $message . '</p>';
				}
			}
		}
		
		return $message;
	}
	
	private function get_group_points($groupid)
	{
		global $DB;
		
		$sql = "SELECT sum(l.points)
					FROM {points_group_log} g
						INNER JOIN {points_log} l ON l.id = g.pointslogid
						INNER JOIN {points_system} s ON s.id = l.pointsystemid
					WHERE g.groupid = :groupid
						AND s.blockinstanceid = :blockinstanceid";
		
		$params['groupid'] = $groupid;
		$params['blockinstanceid'] = $this->instance->id;
		
		$grouppoints = $DB->get_field_sql($sql, $params);
		
		return (empty($grouppoints) ? 0 : $grouppoints);
	}
	
	private function has_points_systems($blockinstanceid, $uselinks = false)
	{
		global $DB;

		if($uselinks)
		{
			$result = $this->has_points_systems($blockinstanceid);
			if($result)
			{
				return true;
			}
			
			$links = $DB->get_records('points_link', array('blockinstanceid' => $blockinstanceid), '', 'accfromblockinstanceid');
			if(empty($links))
			{
				return false;
			}
		
			foreach($links as $link)
			{
				$result = $this->has_points_systems($link->accfromblockinstanceid, true);
				if($result)
				{
					return true;
				}
			}
		}
		else
		{
			$count = $DB->count_records('points_system', array('deleted' => 0, 'blockinstanceid' => $blockinstanceid));
			
			return ($count > 0);			
		}
		
		return false;
	}
}

?>
