<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');

class block_game_points_helper {

	public static function observer(\core\event\base $event)
	{
        global $DB;
		
        if(!block_game_points_helper::is_student($event->userid)) {
            return;
        }
				
		$pss = $DB->get_records_sql("SELECT * FROM {points_system} WHERE deleted = ? AND ".$DB->sql_compare_text('conditionpoints')." = ". $DB->sql_compare_text('?'), array('deleted' => 0, 'conditionpoints' => $event->eventname));
		foreach($pss as $pointsystem)
		{
			if(!block_game_points_helper::is_available($pointsystem->restrictions, $event->courseid, $event->userid))
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

			$logid = $reader->get_events_select($selectwhere, $params);
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
