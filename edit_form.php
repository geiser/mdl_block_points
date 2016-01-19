<?php
 
class block_game_points_edit_form extends block_edit_form {
 
    protected function specific_definition($mform)
	{
 		global $COURSE, $DB, $USER;
 
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
			
			$sql = "SELECT *
				FROM {points_system} s
					INNER JOIN {points_system_processor} p ON s.id = p.pointsystemid
				WHERE p.processorid = :processorid
					AND s.blockinstanceid = :blockinstanceid
					AND s.deleted = 0";
			$params['processorid'] = $USER->id;
			$params['blockinstanceid'] = $this->block->instance->id;
			$points_systems = $DB->get_records_sql($sql, $params);
			
			$html = '<table><tr><th>ID</th><th>Tipo</th><th>Condições</th><th>Valor</th><th>Descrição</th><th>Editar</th><th>Remover</th></tr>';
			foreach($points_systems as $value)
			{
				$urledit = new moodle_url('/blocks/game_points/editpointsystem.php', array('courseid' => $COURSE->id, 'pointsystemid' => $value->id));
				$urlremove = new moodle_url('/blocks/game_points/deletepointsystem.php', array('courseid' => $COURSE->id, 'pointsystemid' => $value->id));
				$html = $html . '<tr><td>' . $value->id . '</td><td>' . $typesarray[$value->type] . '</td><td>' . $eventsarray[$value->conditionpoints] . '</td><td>' . $value->valuepoints . '</td><td>' . $value->eventdescription . '</td><td>' . html_writer::link($urledit, 'Editar') . '</td><td>' . html_writer::link($urlremove, 'Remover') . '</td></tr>';
			}
			$url = new moodle_url('/blocks/game_points/addpointsystem.php', array('blockid' => $this->block->instance->id, 'courseid' => $COURSE->id));
			$html = $html . '</table>' . html_writer::link($url, get_string('addpointsystempage', 'block_game_points'));
			$mform->addElement('html', $html);
			
		}
	}
}

?>