<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 
/**
 * Points system edit form definition.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");
require_once($CFG->dirroot.'/blocks/game_points/lib.php');

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
		 
		$eventsarray = get_events_list(true);
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