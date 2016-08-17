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
 * Points system manage restrictions form definition.
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
define("AND_CONNECTIVE", 0);
define("OR_CONNECTIVE", 1);
 
class block_game_points_restrictionmanage_form extends moodleform
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
 
		$pointsystem = $DB->get_record('points_system', array('id' => $this->pointsystemid));
 
		// Restrictions
		$mform->addElement('header', 'availabilityconditionsheader', get_string('restrictaccess', 'availability'));
		$mform->addElement('textarea', 'availabilityconditionsjson', get_string('accessrestrictions', 'availability'));
		$mform->setDefault('availabilityconditionsjson', $pointsystem->restrictions);
		\core_availability\frontend::include_all_javascript($COURSE, null);
 
		$mform->addElement('html', '<hr></hr>');

		$connective = $DB->get_field('points_system', 'connective', array('id' => $this->pointsystemid));
		$connectives_array = array(AND_CONNECTIVE => 'E', OR_CONNECTIVE => 'Ou');
		$select = $mform->addElement('select', 'connective', 'Conectivo', $connectives_array);
		$mform->addRule('connective', null, 'required', null, 'client');
		$select->setSelected($connective);
 
		$operators_array = array(EQUAL => 'iguais a', GREATER => 'maiores que', LESS => 'menores que', EQUALORGREATER => 'maiores ou iguais a', EQUALORLESS => 'menores ou iguais a', BETWEEN => 'entre');
		$html = '<table><tr><th>Descrição</th><th>Remover</th></tr>';
		$restrictions = $DB->get_records('points_system_restriction', array('pointsystemid' => $this->pointsystemid));
		foreach($restrictions as $restriction)
		{
			if($restriction->type == 0) // Restrição por pontos
			{
				$block_id = null;
				if(isset($restriction->prpointsystemid))
				{
					$block_id = $DB->get_field('points_system', 'blockinstanceid', array('id' => $restriction->prpointsystemid));
					$block_info = $DB->get_record('block_instances', array('id' => $block_id));
				
					$points_system_name = $DB->get_field('points_system', 'name', array('id' => $restriction->prpointsystemid));
				}
				else
				{
					$block_info = $DB->get_record('block_instances', array('id' => $restriction->prblockid));
				}
				$instance = block_instance('game_points', $block_info);

				$url = new moodle_url('/blocks/game_points/restrictiondelete.php', array('restrictionid' => $restriction->id, 'courseid' => $COURSE->id));
				$html .= '<tr><td>Os pontos do aluno no' . (isset($restriction->prblockid) ? ' bloco ' . $instance->title  : ' sistema de pontos ' . (empty($points_system_name) ? $restriction->prpointsystemid : $points_system_name . ' (' . $restriction->prpointsystemid . ')') . ' (bloco ' . $instance->title . ')' ) . ' devem ser ' . $operators_array[$restriction->properator] . ' ' . $restriction->prpoints . ($restriction->properator == BETWEEN ? (' e ' . $restriction->prpointsbetween) : '') . ' pontos' . '</td><td>' . html_writer::link($url, 'Remover') . '</td></tr>';
			}
			else if($restriction->type == 1) // Restrição por conteúdo desbloqueado
			{
				$unlock_system = $DB->get_record('content_unlock_system', array('id' => $restriction->urunlocksystemid));
				
				$course = $DB->get_record('course', array('id' => $COURSE->id));
				$info = get_fast_modinfo($course);
				$cm = $info->get_cm($unlock_system->coursemoduleid);
				
				$block_info = $DB->get_record('block_instances', array('id' => $unlock_system->blockinstanceid));
				$instance = block_instance('game_content_unlock', $block_info);
				
				$url = new moodle_url('/blocks/game_points/restrictiondelete.php', array('restrictionid' => $restriction->id, 'courseid' => $COURSE->id));
				$html .= '<tr><td>O aluno ' . ($restriction->urmust ? 'deve' : 'não deve') . ' ter ' . ($unlock_system->coursemodulevisibility ? 'desbloqueado' : 'bloqueado') . ' o recurso/atividade ' . $cm->name . ' (bloco ' . $instance->title . ')' . '</td><td>' . html_writer::link($url, 'Remover') . '</td></tr>';
			}
			else // Restrição por conquista atingida
			{
				$achievement = $DB->get_record('achievements', array('id' => $restriction->arachievementid));
				
				$block_info = $DB->get_record('block_instances', array('id' => $achievement->blockinstanceid));
				$instance = block_instance('game_achievements', $block_info);
				
				$url = new moodle_url('/blocks/game_points/restrictiondelete.php', array('restrictionid' => $restriction->id, 'courseid' => $COURSE->id));
				$html .= '<tr><td>O aluno deve ter atingido a conquista ' . (isset($achievement->name) ? $achievement->name . ' (' . $achievement->id . ')' : $achievement->id)  . ' (bloco ' . $instance->title . ')</td><td>' . html_writer::link($url, 'Remover') . '</td></tr>';
			}
		}
		$url = new moodle_url('/blocks/game_points/restrictionadd.php', array('pointsystemid' => $this->pointsystemid, 'courseid' => $COURSE->id));
		$html .= '</table>' . html_writer::link($url, 'Adicionar restrição');
		
		$mform->addElement('html', $html);
 
		// Advanced restrictions
		$mform->addElement('html', '<hr></hr>');

		$advconnective = $DB->get_field('points_system', 'advconnective', array('id' => $this->pointsystemid));
		$select = $mform->addElement('select', 'advconnective', 'Conectivo de restrições avançadas', $connectives_array);
		$mform->addRule('advconnective', null, 'required', null, 'client');
		$select->setSelected($advconnective);
 
		$html = '<table>
					<tr>
						<th>' . get_string('restrictionmanagesql', 'block_game_points') . '</th>
						<th>' . get_string('restrictionmanagetrueif', 'block_game_points') . '</th>
						<th>' . get_string('restrictionmanagedelete', 'block_game_points') . '</th>
					</tr>';
		$restrictions = $DB->get_records('points_system_advrestriction', array('pointsystemid' => $this->pointsystemid));
		foreach($restrictions as $restriction)
		{
			$url = new moodle_url('/blocks/game_points/advancedrestrictiondelete.php', array('restrictionid' => $restriction->id, 'courseid' => $COURSE->id));
			$html .= '<tr>
					 	<td>' . get_string('advancedrestrictionaddselect', 'block_game_points') . ' ' . $restriction->whereclause . '</td>
						 <td>' . ($restriction->trueif == 0 ? get_string('advancedrestrictionaddtrueifzero', 'block_game_points') : get_string('advancedrestrictionaddtrueifnotzero', 'block_game_points')) . '</td>
						<td>' . html_writer::link($url, get_string('restrictionmanagedelete', 'block_game_points')) . '</td>
					 </tr>';
		}
		$url = new moodle_url('/blocks/game_points/advancedrestrictionadd.php', array('pointsystemid' => $this->pointsystemid, 'courseid' => $COURSE->id));
		$html .= '</table>' . html_writer::link($url, get_string('restrictionmanageadd', 'block_game_points'));

		$mform->addElement('html', $html);
 
		// Hidden elements
        $mform->addElement('hidden', 'pointsystemid');
		$mform->addElement('hidden', 'courseid');
		
		$this->add_action_buttons();
    }
}

?>