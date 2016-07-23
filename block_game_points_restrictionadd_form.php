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
 * Points system add restriction form definition.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

		$condition_types = array();
		$game_content_unlock_installed = $DB->record_exists('block', array('name' => 'game_content_unlock'));
		if($game_content_unlock_installed)
		{
			$condition_types[1] = 'Por conteúdo desbloqueado';
		}
		$game_achievements_installed = $DB->record_exists('block', array('name' => 'game_achievements'));
		if($game_achievements_installed)
		{
			$condition_types[2] = 'Por conquista';
		}
		$condition_types[0] = 'Por pontos';
		
		$mform->addElement('select', 'restriction_type', 'Tipo de restrição', $condition_types, null);
		$mform->addRule('restriction_type', null, 'required', null, 'client');
		
		$mform->addElement('html', '<hr></hr>');
		
		$blockid = $DB->get_field('points_system', 'blockinstanceid', array('id' => $this->pointsystemid));
		$options = array();
		$blocks_info = $DB->get_records('block_instances', array('blockname' => 'game_points'));
		foreach($blocks_info as $info)
		{
			$instance = block_instance('game_points', $info);
			
			$options['block::' . $instance->instance->id] = '- Bloco ' . $instance->title;
			
			$sql = 'SELECT id
						FROM {points_system}
						WHERE blockinstanceid = :blockinstanceid
							AND deleted = :deleted';
							
			$params['blockinstanceid'] = $instance->instance->id;
			$params['deleted'] = 0;
		
			$point_system_ids = $DB->get_fieldset_sql($sql, $params);
			foreach($point_system_ids as $point_system_id)
			{
				if($point_system_id != $this->pointsystemid)
				{
					$options['pointsystem::' . $point_system_id] = '&nbsp;&nbsp;&nbsp;&nbsp;Sistema de pontos ' . $point_system_id;
				}
			}
		}
		$mform->addElement('select', 'points_restriction_blockorpointsystemid', 'Os pontos do bloco', $options, null);
		$mform->disabledIf('points_restriction_blockorpointsystemid', 'restriction_type', 'neq', 0);
		
		$operators_array = array(EQUAL => 'Iguais a', GREATER => 'Maiores que', LESS => 'Menores que', EQUALORGREATER => 'Maiores ou iguais a', EQUALORLESS => 'Menores ou iguais a', BETWEEN => 'Entre');
		$mform->addElement('select', 'points_restriction_operator', 'Devem ser', $operators_array, null);
		$mform->disabledIf('points_restriction_operator', 'restriction_type', 'neq', 0);
		
		$mform->addElement('text', 'points_restriction_points', 'Pontos');
		$mform->disabledIf('points_restriction_points', 'restriction_type', 'neq', 0);
		
		$mform->addElement('text', 'points_restriction_points_between', 'E');
		$mform->disabledIf('points_restriction_points_between', 'restriction_type', 'neq', 0);
		$mform->disabledIf('points_restriction_points_between', 'points_restriction_operator', 'neq', BETWEEN);
		
		if($game_content_unlock_installed)
		{
			$mform->addElement('html', '<hr></hr>');
			
			$unlock_systems = array();
			$blocks_info = $DB->get_records('block_instances', array('blockname' => 'game_content_unlock'));
			foreach($blocks_info as $info)
			{
				$instance = block_instance('game_content_unlock', $info);
				
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
			$mform->disabledIf('unlock_restriction_must', 'restriction_type', 'neq', 1);
			
			$mform->addElement('select', 'unlock_restriction_unlocksystemid', 'Ter', $unlock_systems, null);
			$mform->disabledIf('unlock_restriction_unlocksystemid', 'restriction_type', 'neq', 1);
		}
		
		if($game_achievements_installed)
		{
			$mform->addElement('html', '<hr></hr>');
			
			$achievements = array();
			$blocks_info = $DB->get_records('block_instances', array('blockname' => 'game_achievements'));
			foreach($blocks_info as $info)
			{
				$instance = block_instance('game_achievements', $info);
				
				$sql = 'SELECT id
							FROM {achievements}
							WHERE blockinstanceid = :blockinstanceid
								AND deleted = :deleted';
								
				$params['blockinstanceid'] = $instance->instance->id;
				$params['deleted'] = 0;
			
				$achievement_ids = $DB->get_fieldset_sql($sql, $params);
				foreach($achievement_ids as $achievement_id)
				{
					$achievement = $DB->get_record('achievements', array('id' => $achievement_id));
					$achievements[$achievement_id] = 'Conquista ' . (isset($achievement->name) ? $achievement->name . ' (' . $achievement_id . ')' : $achievement_id) . ' (bloco ' . $instance->title . ')';
				}
			}
			$mform->addElement('select', 'achievements_restriction_achievementid', 'Ter alcançado a', $achievements, null);
			$mform->disabledIf('achievements_restriction_achievementid', 'restriction_type', 'neq', 2);
		}
		
		$mform->addElement('hidden', 'pointsystemid');
		$mform->addElement('hidden', 'courseid');
		
		$this->add_action_buttons();
    }
}

?>