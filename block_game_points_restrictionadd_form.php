<?php

require_once("{$CFG->libdir}/formslib.php");

define("EQUAL", 0);
define("GREATER", 1);
define("LESS", 2);
define("EQUALORGREATER", 3);
define("EQUALORLESS", 4);
define("BETWEEN", 5);

class block_game_points_restrictionadd_form extends moodleform
{
 
	function __construct($pointsystemid)
	{
		$this->pointsystemid = $pointsystemid;
		parent::__construct();
	}
 
    function definition()
	{
		global $DB, $COURSE;
 
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('restrictionaddheading', 'block_game_points'));

		$mform->addElement('select', 'restriction_type', 'Tipo de restrição', array(0 => 'Por pontos', 1 => 'Por conteúdo desbloqueado'), null);
		$mform->addRule('restriction_type', null, 'required', null, 'client');
		
		$blockid = $DB->get_field('points_system', 'blockinstanceid', array('id' => $this->pointsystemid));
		$block_instances = array();
		$blocks_info = $DB->get_records('block_instances', array('blockname' => 'game_points'));
		$block_context_level = context::instance_by_id($blocks_info[$blockid]->parentcontextid)->contextlevel;
		foreach($blocks_info as $info)
		{
			$instance = block_instance('game_points', $info);
			
			$instance_context_level = context::instance_by_id($instance->instance->parentcontextid)->contextlevel;
			if($block_context_level > $instance_context_level || $instance->instance->id == $blockid)
			{
				continue;
			}
			
			$block_instances[$instance->instance->id] = $instance->title;
		}
		$mform->addElement('select', 'points_restriction_blockid', 'Os pontos do bloco', $block_instances, null);
		$mform->disabledIf('points_restriction_blockid', 'restriction_type', 'eq', 1);
		
		$operators_array = array(EQUAL => 'Iguais a', GREATER => 'Maiores que', LESS => 'Menores que', EQUALORGREATER => 'Maiores ou iguais a', EQUALORLESS => 'Menores ou iguais a', BETWEEN => 'Entre');
		$mform->addElement('select', 'points_restriction_operator', 'Devem ser', $operators_array, null);
		$mform->disabledIf('points_restriction_operator', 'restriction_type', 'eq', 1);
		
		$mform->addElement('text', 'points_restriction_points', 'Pontos');
		$mform->disabledIf('points_restriction_points', 'restriction_type', 'eq', 1);
		
		$mform->addElement('text', 'points_restriction_points_between', 'E');
		$mform->disabledIf('points_restriction_points_between', 'restriction_type', 'eq', 1);
		$mform->disabledIf('points_restriction_points_between', 'points_restriction_operator', 'neq', BETWEEN);
		
		$unlock_systems = array();
		$blocks_info = $DB->get_records('block_instances', array('blockname' => 'game_content_unlock'));
		foreach($blocks_info as $info)
		{
			$instance = block_instance('game_content_unlock', $info);
			
			$instance_context_level = context::instance_by_id($instance->instance->parentcontextid)->contextlevel;
			if($block_context_level > $instance_context_level || $instance->instance->id == $blockid)
			{
				continue;
			}
			
			$sql = "SELECT *
					FROM
						{content_unlock_system} u
					WHERE u.deleted = 0
						AND u.blockinstanceid = :blockinstanceid";
			$params['blockinstanceid'] = $instance->instance->id;
			
			$us = $DB->get_records_sql($sql, $params);
			
			foreach($us as $unlock_system)
			{
				$course = $DB->get_record('course', array('id' => $COURSE->id));
				$info = get_fast_modinfo($course);
				$cm = $info->get_cm($unlock_system->coursemoduleid);
				
				$unlock_systems[$unlock_system->id] =  ($unlock_system->coursemodulevisibility ? 'Desbloqueado' : 'Bloqueado') . ' o recurso/atividade ' . $cm->name . ' (bloco ' . $instance->title . ')';
			}
		}
		
		$mform->addElement('select', 'unlock_restriction_must', 'O aluno', array(0 => 'Não deve', 1 => 'Deve'), null);
		$mform->setDefault('unlock_restriction_must', 1);
		$mform->disabledIf('unlock_restriction_must', 'restriction_type', 'eq', 0);
		
		$mform->addElement('select', 'unlock_restriction_unlocksystemid', 'Ter', $unlock_systems, null);
		$mform->disabledIf('unlock_restriction_unlocksystemid', 'restriction_type', 'eq', 0);
		
		$mform->addElement('hidden', 'pointsystemid');
		$mform->addElement('hidden', 'courseid');
		
		$this->add_action_buttons();
    }
}

?>