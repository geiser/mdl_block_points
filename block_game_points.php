<?php

defined('MOODLE_INTERNAL') || die();

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
	
    public function get_content()
	{
		global $DB, $USER;
		$this->content = new stdClass;
	
		if($this->page->course->id == 1) // Pagina inicial
		{
			$sql = "SELECT sum(p.points) as points
				FROM
					{points_log} p
				INNER JOIN {logstore_standard_log} l ON p.logid = l.id
				WHERE l.userid = :userid
				GROUP BY l.userid";	

			$params['userid'] = $USER->id;

			$points = $DB->get_record_sql($sql, $params);
			
			if(empty($points))
			{
				$points = new stdClass();
				$points->points = 0;
			}
			
			$this->content->text = 'Seus pontos: <br><p align="center"><font size="28">' . $points->points . '</font></center>';
			
		}
		else // Pagina de um curso
		{
			$sql = "SELECT sum(p.points) as points
				FROM
					{points_log} p
				INNER JOIN {logstore_standard_log} l ON p.logid = l.id
				WHERE l.userid = :userid
					AND l.courseid = :courseid
				GROUP BY l.userid";	

			$params['userid'] = $USER->id;
			$params['courseid'] = $this->page->course->id;

			$points = $DB->get_record_sql($sql, $params);
			
			if(empty($points))
			{
				$points = new stdClass();
				$points->points = 0;
			}
			
			$this->content->text = 'Seus pontos: <br><p align="center"><font size="28">' . $points->points . '</font></center>';
			
			// Footer
			if(user_has_role_assignment($USER->id, 5))
			{
				$pss = null;
				if(is_null($this->page->cm->modname))
				{
					$pss = $DB->get_records('points_system', array('deleted' => 0));
				}
				else
				{
					$sql = "SELECT *
					FROM
						{points_system} p
					WHERE p.deleted = 0
						AND p.conditionpoints LIKE '%" . $this->page->cm->modname . "%'";
					
					$pss = $DB->get_records_sql($sql);
				}
				if(!empty($pss))
				{
					$pointslist = '';
					
					$eventslist = report_eventlist_list_generator::get_non_core_event_list();
					$eventsarray = array();
					foreach($eventslist as $value)
					{
						$description = explode("\\", explode(".", strip_tags($value['fulleventname']))[0]);
						$eventsarray[$value['eventname']] = $description[0];
					}
					
					foreach($pss as $pointsystem)
					{
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
						
						if($points != 0)
						{
							$eventdescription = is_null($pointsystem->eventdescription) ? $eventsarray[$pointsystem->conditionpoints] : $pointsystem->eventdescription;
							$pointslist = $pointslist . '<li>' . $points . ' pontos por ' . $eventdescription . '</li>';
						}
						
					}
					
					if(strlen($pointslist) > 0)
					{
						$this->content->footer = 'Você pode ganhar:<ul>' . $pointslist . '</ul>';
					}
					
				}
			 
			}
			 
		}
		
		return $this->content;
    }

    public function has_config()
	{
        return true;
    }
}

?>