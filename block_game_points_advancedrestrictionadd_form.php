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
 * Points system add advanced restriction form definition.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");

class block_game_points_advancedrestrictionadd_form extends moodleform
{
 
    function definition()
	{
		global $DB, $COURSE;
 
		$mform =& $this->_form;
		$mform->addElement('header','displayinfo', get_string('advancedrestrictionaddheading', 'block_game_points'));
		
		$mform->addElement('hidden', 'pointsystemid');
		$mform->setType('pointsystemid', PARAM_INT);
		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
    }

	public function definition_after_data()
	{
		global $DB, $USER;
        parent::definition_after_data();
				
        $mform =& $this->_form;
		
        $pointsystemid_element = $mform->getElement('pointsystemid');
        $pointsystemid = $pointsystemid_element->getValue();
		
		$courseid_element = $mform->getElement('courseid');
        $courseid = $courseid_element->getValue();

		$mform->addElement('textarea', 'whereclause', get_string("advancedrestrictionaddselect", 'block_game_points'));
		$mform->addRule('whereclause', null, 'required', null, 'client');

		$options = array(
			0 => get_string('advancedrestrictionaddtrueifzero', 'block_game_points'),
			1 => get_string('advancedrestrictionaddtrueifnotzero', 'block_game_points'),
			2 => get_string('advancedrestrictionaddtrueifegthan', 'block_game_points')
		);
		$mform->addElement('select', 'trueif',  get_string('advancedrestrictionaddtrueif', 'block_game_points'), $options, null);
		$mform->addRule('trueif', null, 'required', null, 'client');

		$mform->addElement('text', 'count',  get_string('advancedrestrictionaddcount', 'block_game_points'));
		$mform->disabledIf('count', 'trueif', 'neq', 2);

		$this->add_action_buttons(true, get_string('advancedrestrictionaddbutton', 'block_game_points'));
    }
}

?>