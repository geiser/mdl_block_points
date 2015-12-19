<?php
 
class block_game_points_edit_form extends block_edit_form {
 
    protected function specific_definition($mform)
	{
 		global $COURSE;
 
		$context = context_course::instance($COURSE->id);
		if(has_capability('block/game_points:addpointsystem', $context))
		{
			$mform->addElement('header', 'configheader', get_string('addpointsystempage', 'block_game_points'));
			
			$url = new moodle_url('/blocks/game_points/addpointsystem.php', array('blockid' => $this->instance->id, 'courseid' => $COURSE->id));
			$mform->addElement('static', 'description', 'Sistema de pontos', html_writer::link($url, get_string('addpointsystempage', 'block_game_points')));
		}
	}
}

?>