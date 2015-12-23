<?php
 
class block_game_points_edit_form extends block_edit_form {
 
    protected function specific_definition($mform)
	{
 		global $COURSE, $DB;
 
		$context = context_course::instance($COURSE->id);
		if(has_capability('block/game_points:addpointsystem', $context))
		{
			$mform->addElement('header', 'configheader', get_string('editpointsystempage', 'block_game_points'));
			
			$typesarray = array(
				'random' => 'Randômico',
				'fixed' => 'Fixo',
				'unique' => 'Único',
				'scalar' => 'Escalar'
			);
			
			$eventslist = report_eventlist_list_generator::get_non_core_event_list();
			$eventsarray = array();
			foreach($eventslist as $value)
			{
				$description = explode("\\", explode(".", strip_tags($value['fulleventname']))[0]);
				$eventsarray[$value['eventname']] = $description[0] . " (" . $value['eventname'] . ")";
			}
			
			$points_systems = $DB->get_records('points_system');
			$html = '<table><tr><th>ID</th><th>Tipo</th><th>Condições</th><th>Valor</th><th>Editar</th></tr>';
			foreach($points_systems as $value)
			{
				$url = new moodle_url('/blocks/game_points/editpointsystem.php', array('courseid' => $COURSE->id, 'pointsystemid' => $value->id));
				$html = $html . '<tr><td>' . $value->id . '</td><td>' . $typesarray[$value->type] . '</td><td>' . $eventsarray[$value->conditionpoints] . '</td><td>' . $value->valuepoints . '</td><td>' . html_writer::link($url, 'Editar') . '</td></tr>';
			}
			$url = new moodle_url('/blocks/game_points/addpointsystem.php', array('blockid' => $this->instance->id, 'courseid' => $COURSE->id));
			$html = $html . '</table>' . html_writer::link($url, get_string('addpointsystempage', 'block_game_points'));
			$mform->addElement('html', $html);
			
		}
	}
}

?>