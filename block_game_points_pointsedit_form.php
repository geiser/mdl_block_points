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
 * Points system edit user points form definition.
 *
 * @package    block_game_points
 * @copyright  2016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");
 
class block_game_points_pointsedit_form extends moodleform
{
    function definition()
	{
		$mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('pointseditheading', 'block_game_points'));
		
		$mform->addElement('hidden', 'pointslogid');
		$mform->setType('pointslogid', PARAM_INT);
		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
    }

	public function definition_after_data()
	{
		global $DB, $USER;
        parent::definition_after_data();
				
        $mform =& $this->_form;
		
        $pointslogid_element = $mform->getElement('pointslogid');
        $pointslogid = $pointslogid_element->getValue();
		$points = $DB->get_field('points_log', 'points', array('id' => $pointslogid));
		
		$mform->addElement('text', 'points', get_string('pointseditpoints', 'block_game_points'));
		$mform->addRule('points', null, 'required', null, 'client');
		$mform->setDefault('points', $points);
		$mform->setType('points', PARAM_INT);

		$this->add_action_buttons(true, get_string('pointseditbutton', 'block_game_points'));
    }	
}

?>