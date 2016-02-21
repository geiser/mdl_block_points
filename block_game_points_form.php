<?php

require_once("{$CFG->libdir}/formslib.php");
 
class block_game_points_form extends moodleform {
 
    function definition()
	{
		global $COURSE, $CFG, $DB;
 
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('addpointsystemheading', 'block_game_points'));

		$typesarray = array(
			'random' => 'Randômico',
			'fixed' => 'Fixo',
			'unique' => 'Único',
			'scalar' => 'Escalar'
		);
		$mform->addElement('select', 'type', 'Tipo', $typesarray, null);
		$mform->addRule('type', null, 'required', null, 'client');
		 
		$eventslist = report_eventlist_list_generator::get_non_core_event_list();
		$eventsarray = array();
		foreach($eventslist as $value)
		{
			$description = explode("\\", explode(".", strip_tags($value['fulleventname']))[0]);
			$eventsarray[$value['eventname']] = $description[0] . " (" . $value['eventname'] . ")";
		}
		$mform->addElement('select', 'event', 'Evento', $eventsarray, null);
		$mform->addRule('event', null, 'required', null, 'client');
		
		$mform->addElement('text', 'value', 'Valor<br><font size="1"><p align="right">Randômico: [min]-[max]<br>Fixo: [valor]<br>Único: [valor]<br>Escalar: função matemática em que x é o número da<br>vez que o usuário está realizando a ação</font></right>');
		$mform->addRule('value', null, 'required', null, 'client');
		
		$mform->addElement('text', 'description', 'Descrição');
		
		$mform->addElement('text', 'pointslimit', 'Limite de pontos');
		
		// Restrict access
		if(!empty($CFG->enableavailability))
		{
			$mform->addElement('header', 'availabilityconditionsheader', get_string('restrictaccess', 'availability'));
			$mform->addElement('textarea', 'availabilityconditionsjson', get_string('accessrestrictions', 'availability'));

			\core_availability\frontend::include_all_javascript($COURSE, null);
		}
		
		// Common group settings
		$mform->addElement('header', 'modstandardelshdr', get_string('modstandardels', 'form'));

		$options = array(NOGROUPS       => get_string('groupsnone'),
						 SEPARATEGROUPS => get_string('groupsseparate'),
						 VISIBLEGROUPS  => get_string('groupsvisible'));
		$mform->addElement('select', 'groupmode', get_string('groupmode', 'group'), $options, NOGROUPS);
		$mform->addHelpButton('groupmode', 'groupmode', 'group');
		
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
		
		// Elementos escondidos
		$mform->addElement('hidden', 'blockid');
		$mform->addElement('hidden', 'courseid');
		
		$this->add_action_buttons();
    }
}

?>