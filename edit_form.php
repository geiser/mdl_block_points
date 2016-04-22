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
 * Points block edit form definition.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/blocks/game_points/lib.php');

class block_game_points_edit_form extends block_edit_form {
 
    protected function specific_definition($mform)
	{
 		global $COURSE, $DB, $USER;
 
		$mform->addElement('header', 'configviewheader', 'Exibição de grupos');
 
		$context = context_course::instance($COURSE->id);
		if(has_capability('block/game_points:addpointsystem', $context))
		{
			$mform->addElement('header', 'configheader', get_string('editpointsystempage', 'block_game_points'));
			
			$mform->addElement('text', 'config_title', 'Título do bloco');
			$mform->setType('config_title', PARAM_TEXT);
			
			$mform->addElement('select', 'config_usedetailedview', 'Exibir visão detalhada de pontuação de grupos', array(0 => 'Não', 1 => 'Sim'), null);
			$mform->setType('config_usedetailedview', PARAM_INT);
			
			$mform->addElement('select', 'config_lastpointsnumber', 'Número de últimas pontuações exibidas', array(0, 1, 2, 3, 4, 5, 6), null);
			$mform->addRule('config_lastpointsnumber', null, 'required', null, 'client');
			$mform->setDefault('config_lastpointsnumber', 1);
			$mform->setType('config_lastpointsnumber', PARAM_TEXT);
			
			$typesarray = array(
				'random' => 'Randômico',
				'fixed' => 'Fixo',
				'unique' => 'Único',
				'scalar' => 'Escalar'
			);
			
			$eventsarray = get_events_list(true);
			
			$sql = "SELECT *
				FROM {points_system} s
					INNER JOIN {points_system_processor} p ON s.id = p.pointsystemid
				WHERE p.processorid = :processorid
					AND s.blockinstanceid = :blockinstanceid
					AND s.deleted = 0";
			$params['processorid'] = $USER->id;
			$params['blockinstanceid'] = $this->block->instance->id;
			$points_systems = $DB->get_records_sql($sql, $params);
			
			$html = '<table><tr><th>ID</th><th>Tipo</th><th>Condições</th><th>Valor</th><th>Descrição</th><th>Limite de pontos</th><th>Gerenciar restrições</th><th>Editar</th><th>Remover</th></tr>';
			foreach($points_systems as $value)
			{
				$urlmanagerestrictions = new moodle_url('/blocks/game_points/restrictionmanage.php', array('courseid' => $COURSE->id, 'pointsystemid' => $value->id));
				$urledit = new moodle_url('/blocks/game_points/editpointsystem.php', array('courseid' => $COURSE->id, 'pointsystemid' => $value->id));
				$urlremove = new moodle_url('/blocks/game_points/deletepointsystem.php', array('courseid' => $COURSE->id, 'pointsystemid' => $value->id));
				$html = $html . '<tr><td>' . $value->id . '</td><td>' . $typesarray[$value->type] . '</td><td>' . $eventsarray[$value->conditionpoints] . '</td><td>' . $value->valuepoints . '</td><td>' . $value->eventdescription . '</td><td>' . $value->pointslimit . '</td><td>' . html_writer::link($urlmanagerestrictions, 'Gerenciar restrições') . '</td><td>' . html_writer::link($urledit, 'Editar') . '</td><td>' . html_writer::link($urlremove, 'Remover') . '</td></tr>';
			}
			$url = new moodle_url('/blocks/game_points/addpointsystem.php', array('blockid' => $this->block->instance->id, 'courseid' => $COURSE->id));
			$html = $html . '</table>' . html_writer::link($url, get_string('addpointsystempage', 'block_game_points'));
			$mform->addElement('html', $html);
			
			$mform->addElement('header', 'linkheader', get_string('linkeditpage', 'block_game_points'));
			
			$sql = "SELECT *
				FROM {points_link} l
					INNER JOIN {points_link_processor} p ON l.id = p.linkid
				WHERE p.processorid = :processorid
					AND l.blockinstanceid = :blockinstanceid";
			$params['processorid'] = $USER->id;
			$params['blockinstanceid'] = $this->block->instance->id;
			$block_links = $DB->get_records_sql($sql, $params);
			
			$blocks_info = $DB->get_records('block_instances', array('blockname' => 'game_points'));
			
			$html = '<table><tr><th>ID</th><th>Acumular pontos de</th><th>Remover</th></tr>';
			foreach($block_links as $value)
			{
				$urlremove = new moodle_url('/blocks/game_points/linkdelete.php', array('courseid' => $COURSE->id, 'linkid' => $value->id));
				$instance = block_instance('game_points', $blocks_info[$value->accfromblockinstanceid]);
				$html = $html . '<tr><td>' . $value->id . '</td><td>' . $instance->title . '</td><td>' . html_writer::link($urlremove, 'Remover') . '</td></tr>';
			}
			$url = new moodle_url('/blocks/game_points/linkadd.php', array('blockid' => $this->block->instance->id, 'courseid' => $COURSE->id));
			$html = $html . '</table>' . html_writer::link($url, get_string('linkaddpage', 'block_game_points'));
			$mform->addElement('html', $html);
		}			
	}
}

?>