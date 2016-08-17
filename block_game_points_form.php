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
 * Points system add form definition.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");
require_once($CFG->dirroot.'/blocks/game_points/lib.php');
 
class block_game_points_form extends moodleform {
 
    function definition()
	{
		global $COURSE, $CFG, $DB;
 
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('addpointsystemheading', 'block_game_points'));

		$mform->addElement('text', 'name', 'Nome');
		$mform->setType('name', PARAM_TEXT);

		$typesarray = array(
			'random' => 'Randômico',
			'fixed' => 'Fixo',
			'unique' => 'Único',
			'scalar' => 'Escalar'
		);
		$mform->addElement('select', 'type', 'Tipo', $typesarray, null);
		$mform->addRule('type', null, 'required', null, 'client');
		 
		$eventsarray = get_events_list(true);
		$mform->addElement('select', 'event', 'Evento', $eventsarray, null);
		$mform->addRule('event', null, 'required', null, 'client');
		
		$mform->addElement('text', 'value', 'Valor<br><font size="1"><p align="right">Randômico: [min]-[max]<br>Fixo: [valor]<br>Único: [valor]<br>Escalar: função matemática em que x é o número da<br>vez que o usuário está realizando a ação</font></right>');
		$mform->addRule('value', null, 'required', null, 'client');
		
		$mform->addElement('text', 'description', 'Descrição');
		
		$mform->addElement('text', 'pointslimit', 'Limite de pontos');
		
		// Common group settings
		$mform->addElement('header', 'modstandardelshdr', get_string('addpointsystemgroupsettings', 'block_game_points'));

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