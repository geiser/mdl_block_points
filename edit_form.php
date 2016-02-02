<?php
 
class block_game_points_edit_form extends block_edit_form {
 
    protected function specific_definition($mform)
	{
 		global $COURSE, $DB, $USER;
 
		$context = context_course::instance($COURSE->id);
		if(has_capability('block/game_points:addpointsystem', $context))
		{
			$mform->addElement('header', 'configheader', get_string('editpointsystempage', 'block_game_points'));
			
			$mform->addElement('text', 'config_title', 'Título do bloco');
			$mform->setType('config_title', PARAM_TEXT);
			
			$mform->addElement('select', 'config_lastpointsnumber', 'Número de últimas pontuações exibidas', array(0, 1, 2, 3, 4, 5, 6), null);
			$mform->addRule('config_lastpointsnumber', null, 'required', null, 'client');
			$mform->setDefault('config_lastpointsnumber', 1);
			$mform->setType('config_lastpointsnumber', PARAM_TEXT);
			
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
			
			$html = '<table><tr><th>ID</th><th>Tipo</th><th>Condições</th><th>Valor</th><th>Descrição</th><th>Limite de pontos</th><th>Editar</th><th>Remover</th></tr>';
			foreach($points_systems as $value)
			{
				$urledit = new moodle_url('/blocks/game_points/editpointsystem.php', array('courseid' => $COURSE->id, 'pointsystemid' => $value->id));
				$urlremove = new moodle_url('/blocks/game_points/deletepointsystem.php', array('courseid' => $COURSE->id, 'pointsystemid' => $value->id));
				$html = $html . '<tr><td>' . $value->id . '</td><td>' . $typesarray[$value->type] . '</td><td>' . $eventsarray[$value->conditionpoints] . '</td><td>' . $value->valuepoints . '</td><td>' . $value->eventdescription . '</td><td>' . $value->pointslimit . '</td><td>' . html_writer::link($urledit, 'Editar') . '</td><td>' . html_writer::link($urlremove, 'Remover') . '</td></tr>';
			}
			$url = new moodle_url('/blocks/game_points/addpointsystem.php', array('blockid' => $this->block->instance->id, 'courseid' => $COURSE->id));
			$html = $html . '</table>' . html_writer::link($url, get_string('addpointsystempage', 'block_game_points'));
			$mform->addElement('html', $html);
			
			// Common module settings
			/*$mform->addElement('header', 'modstandardelshdr', get_string('modstandardels', 'form'));

			$mform->addElement('modvisible', 'visible', get_string('visible'));
			if (!empty($this->_cm)) {
				$context = context_module::instance($this->_cm->id);
				if (!has_capability('moodle/course:activityvisibility', $context)) {
					$mform->hardFreeze('visible');
				}
			}

			if ($this->_features->idnumber) {
				$mform->addElement('text', 'cmidnumber', get_string('idnumbermod'));
				$mform->setType('cmidnumber', PARAM_RAW);
				$mform->addHelpButton('cmidnumber', 'idnumbermod');
			}

			if ($this->_features->groups) {
				$options = array(NOGROUPS       => get_string('groupsnone'),
								 SEPARATEGROUPS => get_string('groupsseparate'),
								 VISIBLEGROUPS  => get_string('groupsvisible'));
				$mform->addElement('select', 'groupmode', get_string('groupmode', 'group'), $options, NOGROUPS);
				$mform->addHelpButton('groupmode', 'groupmode', 'group');
			}

			if ($this->_features->groupings) {
				// Groupings selector - used to select grouping for groups in activity.
				$options = array();
				if ($groupings = $DB->get_records('groupings', array('courseid'=>$COURSE->id))) {
					foreach ($groupings as $grouping) {
						$options[$grouping->id] = format_string($grouping->name);
					}
				}
				core_collator::asort($options);
				$options = array(0 => get_string('none')) + $options;
				$mform->addElement('select', 'groupingid', get_string('grouping', 'group'), $options);
				$mform->addHelpButton('groupingid', 'grouping', 'group');
			}

			if (!empty($CFG->enableavailability)) {
				// Add special button to end of previous section if groups/groupings
				// are enabled.
				if ($this->_features->groups || $this->_features->groupings) {
					$mform->addElement('static', 'restrictgroupbutton', '',
							html_writer::tag('button', get_string('restrictbygroup', 'availability'),
							array('id' => 'restrictbygroup', 'disabled' => 'disabled')));
				}

				// Availability field. This is just a textarea; the user interface
				// interaction is all implemented in JavaScript.
				$mform->addElement('header', 'availabilityconditionsheader',
						get_string('restrictaccess', 'availability'));
				// Note: This field cannot be named 'availability' because that
				// conflicts with fields in existing modules (such as assign).
				// So it uses a long name that will not conflict.
				$mform->addElement('textarea', 'availabilityconditionsjson',
						get_string('accessrestrictions', 'availability'));
				// The _cm variable may not be a proper cm_info, so get one from modinfo.
				if ($this->_cm) {
					$modinfo = get_fast_modinfo($COURSE);
					$cm = $modinfo->get_cm($this->_cm->id);
				} else {
					$cm = null;
				}
				\core_availability\frontend::include_all_javascript($COURSE, $cm);
			}*/
			
		}
	}
}

?>