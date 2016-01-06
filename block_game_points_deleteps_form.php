<?php

require_once("{$CFG->libdir}/formslib.php");
 
class block_game_points_deleteps_form extends moodleform
{
 
    function definition()
	{
		$mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('deletepointsystemheading', 'block_game_points'));
		
		$mform->addElement('html', get_string('deletepointsystemmessage', 'block_game_points'));
		
		$mform->addElement('hidden', 'courseid');
		$mform->addElement('hidden', 'pointsystemid');
		
		$this->add_action_buttons(true, get_string('deletepointsystembutton', 'block_game_points'));
    }
	
}

?>