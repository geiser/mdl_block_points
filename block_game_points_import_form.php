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
 * Points system import form definition.
 *
 * @package    block_game_points
 * @copyright  2016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");
 
class block_game_points_import_form extends moodleform
{
    function definition()
	{
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('importheader', 'block_game_points'));

		$mform->addElement('hidden', 'blockinstanceid');
		$mform->setType('blockinstanceid', PARAM_INT);
		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
    }

	public function definition_after_data()
	{
		global $DB, $USER;
        parent::definition_after_data();
				
        $mform =& $this->_form;
		
        $blockinstanceid_element = $mform->getElement('blockinstanceid');
        $blockinstanceid = $blockinstanceid_element->getValue();
		
		$courseid_element = $mform->getElement('courseid');
        $courseid = $courseid_element->getValue();

		$blocks = array();
		$blocks_info = $DB->get_records('block_instances', array('blockname' => 'game_points'));
		foreach($blocks_info as $block_info)
		{
			if($block_info->id == $blockinstanceid)
			{
				continue;
			}

			$instance = block_instance('game_points', $block_info);
			$blocks[$block_info->id] = $instance->title;
		}
		asort($blocks);
		$mform->addElement('select', 'from', get_string('importfrom', 'block_game_points'), $blocks, null);
		$mform->addRule('from', null, 'required', null, 'client');

		$this->add_action_buttons(true, get_string('importsubmit', 'block_game_points'));
    }
}

?>