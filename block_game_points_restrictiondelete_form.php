<?php

require_once("{$CFG->libdir}/formslib.php");
 
class block_game_points_restrictiondelete_form extends moodleform
{
 
    function definition()
	{
		$mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('restrictiondeleteheading', 'block_game_points'));
		
		$mform->addElement('html', get_string('restrictiondeletemessage', 'block_game_points'));
		
		$mform->addElement('hidden', 'courseid');
		$mform->addElement('hidden', 'restrictionid');
		
		$this->add_action_buttons(true, get_string('restrictiondeletebutton', 'block_game_points'));
    }
	
}

?>