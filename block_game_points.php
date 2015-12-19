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
			
			// Footer
			global $COURSE;
			 
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
			global $COURSE;
			 
		}
		
		return $this->content;
    }

    public function has_config()
	{
        return true;
    }
}

?>