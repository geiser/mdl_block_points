<?php

defined('MOODLE_INTERNAL') || die();

class block_game_points_helper {

    public static function observer(\core\event\base $event) {
        global $DB;

        /*if (!self::is_student($event->relateduserid)) {
            return;
        }*/
		
		$satisfies_conditions = true;
		
		$pss = $DB->get_records('points_system', array('conditionpoints' => $event->eventname, 'deleted' => 0));
		foreach($pss as $pointsystem)
		{
			if($pointsystem != false)
			{
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
					if($DB->count_records('points_log', array('pointsystemid' => $pointsystem->id)) == 0)
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
					$times = $DB->count_records('points_log', array('pointsystemid' => $pointsystem->id));
					$pointsystem->valuepoints = str_replace('x', (string)$times, $pointsystem->valuepoints);
					eval('$points = ' . $pointsystem->valuepoints . ';');
					$points = (int)$points;
				}
				
				if($points == 0)
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
    }

    protected static function is_student($userid) {
        return user_has_role_assignment($userid, 5);
    }

}
