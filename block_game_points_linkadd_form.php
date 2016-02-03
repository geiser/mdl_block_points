<?php

require_once("{$CFG->libdir}/formslib.php");
 
class block_game_points_linkadd_form extends moodleform
{
 
	function __construct($blockid)
	{
		$this->blockid = $blockid;
		parent::__construct();
	}
 
    function definition()
	{
		global $DB;
 
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('linkaddheading', 'block_game_points'));

		$block_instances = array();
		$blocks_info = $DB->get_records('block_instances', array('blockname' => 'game_points'));
		$block_context_level = context::instance_by_id($blocks_info[$this->blockid]->parentcontextid)->contextlevel;
		foreach($blocks_info as $info)
		{
			$instance = block_instance('game_points', $info);
			
			$instance_context_level = context::instance_by_id($instance->instance->parentcontextid)->contextlevel;
			if($block_context_level >= $instance_context_level)
			{
				continue;
			}
			if($DB->count_records('points_link', array('blockinstanceid' => $this->blockid, 'accfromblockinstanceid' => $instance->instance->id)) > 0)
			{
				continue;
			}
			
			$block_instances[$instance->instance->id] = $instance->title;
		}
		$mform->addElement('select', 'accfromblockinstanceid', 'Acumular pontos de', $block_instances, null);
		$mform->addRule('accfromblockinstanceid', null, 'required', null, 'client');
		
		$mform->addElement('hidden', 'blockid');
		$mform->addElement('hidden', 'courseid');
		
		$this->add_action_buttons();
    }
}

?>