<?php

require_once("{$CFG->libdir}/formslib.php");
 
class block_game_points_linkdelete_form extends moodleform
{
 
    function definition()
	{
		$mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('linkdeleteheading', 'block_game_points'));
		
		$mform->addElement('html', get_string('linkdeletemessage', 'block_game_points'));
		
		$mform->addElement('hidden', 'courseid');
		$mform->addElement('hidden', 'linkid');
		
		$this->add_action_buttons(true, get_string('linkdeletebutton', 'block_game_points'));
    }
	
}

?>