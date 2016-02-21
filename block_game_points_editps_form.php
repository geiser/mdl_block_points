<?php

require_once("{$CFG->libdir}/formslib.php");
 
class block_game_points_editps_form extends moodleform {
 
	function __construct($id, $courseid)
	{
		$this->id = $id;
		$this->courseid = $courseid;
		parent::__construct();
	}
 
    function definition()
	{
		global $COURSE, $CFG, $DB;
 
		$pointsystem = $DB->get_record('points_system', array('id' => $this->id));
 
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('editpointsystemheading', 'block_game_points'));

		$typesarray = array(
			'random' => 'Randômico',
			'fixed' => 'Fixo',
			'unique' => 'Único',
			'scalar' => 'Escalar'
		);
		$select = $mform->addElement('select', 'type', 'Tipo', $typesarray, null);
		$mform->addRule('type', null, 'required', null, 'client');
		$select->setSelected($pointsystem->type);
		 
		$eventslist = report_eventlist_list_generator::get_non_core_event_list();
		$eventsarray = array();
		foreach($eventslist as $value)
		{
			$description = explode("\\", explode(".", strip_tags($value['fulleventname']))[0]);
			$eventsarray[$value['eventname']] = $description[0] . " (" . $value['eventname'] . ")";
		}
		$select = $mform->addElement('select', 'event', 'Evento', $eventsarray, null);
		$mform->addRule('event', null, 'required', null, 'client');
		$select->setSelected($pointsystem->conditionpoints);
		
		$mform->addElement('text', 'value', 'Valor<br><font size="1"><p align="right">Randômico: [min]-[max]<br>Fixo: [valor]<br>Único: [valor]<br>Escalar: função matemática em que x é o número da<br>vez que o usuário está realizando a ação</font></right>');
		$mform->addRule('value', null, 'required', null, 'client');
		$mform->setDefault('value', $pointsystem->valuepoints);
		
		$mform->addElement('text', 'description', 'Descrição');
		$mform->setDefault('description', $pointsystem->eventdescription);
		
		$mform->addElement('text', 'pointslimit', 'Limite de pontos');
		$mform->setDefault('pointslimit', $pointsystem->pointslimit);
		
		// Restrict access
		if(!empty($CFG->enableavailability))
		{
			$mform->addElement('header', 'availabilityconditionsheader', get_string('restrictaccess', 'availability'));
			$mform->addElement('textarea', 'availabilityconditionsjson', get_string('accessrestrictions', 'availability'));
			$mform->setDefault('availabilityconditionsjson', $pointsystem->restrictions);

			\core_availability\frontend::include_all_javascript($COURSE, null);
		}
		
		// Common group settings
		$mform->addElement('header', 'modstandardelshdr', get_string('modstandardels', 'form'));

		$options = array(NOGROUPS       => get_string('groupsnone'),
						 SEPARATEGROUPS => get_string('groupsseparate'),
						 VISIBLEGROUPS  => get_string('groupsvisible'));
		$select = $mform->addElement('select', 'groupmode', get_string('groupmode', 'group'), $options, NOGROUPS);
		$select->setSelected($pointsystem->groupmode);
		$mform->addHelpButton('groupmode', 'groupmode', 'group');
		
		$options = array();
		if ($groupings = $DB->get_records('groupings', array('courseid'=>$COURSE->id))) {
			foreach ($groupings as $grouping) {
				$options[$grouping->id] = format_string($grouping->name);
			}
		}
		core_collator::asort($options);
		$options = array(0 => get_string('none')) + $options;
		$select = $mform->addElement('select', 'groupingid', get_string('grouping', 'group'), $options);
		if(isset($pointsystem->groupingid))
		{
			$select->setSelected($pointsystem->groupingid);
		}
		$mform->addHelpButton('groupingid', 'grouping', 'group');
		
		// Elementos escondidos
		$mform->addElement('hidden', 'courseid');
		$mform->addElement('hidden', 'pointsystemid');
		
		$this->add_action_buttons();
    }
	
}

?>