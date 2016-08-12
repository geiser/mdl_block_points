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
 * Points system delete user points form definition.
 *
 * @package    block_game_points
 * @copyright  2016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");
 
class block_game_points_pointsdelete_form extends moodleform
{
 
    function definition()
	{
		$mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('pointsdeleteheading', 'block_game_points'));
		
		$mform->addElement('html', get_string('pointsdeletemessage', 'block_game_points'));
		
		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
		$mform->addElement('hidden', 'pointslogid');
		$mform->setType('pointslogid', PARAM_INT);
		
		$this->add_action_buttons(true, get_string('pointsdeletebutton', 'block_game_points'));
    }
	
}

?>