<?php

defined('MOODLE_INTERNAL') || die();

class block_game_points_helper {

	public static function observer(\core\event\base $event)
	{
        global $DB;
		
        if(!block_game_points_helper::is_student($event->userid)) {
            return;
        }
				
		$pss = $DB->get_records('points_system', array('conditionpoints' => $event->eventname, 'deleted' => 0));
		foreach($pss as $pointsystem)
		{
			
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
				return;
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
			$DB->insert_record('points_log', $record);
			
		}
		
    }

    protected static function is_student($userid) {
        return user_has_role_assignment($userid, 5);
    }

}
