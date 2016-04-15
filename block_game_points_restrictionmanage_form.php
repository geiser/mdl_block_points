<?php

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
				}
				else
				{
					$block_info = $DB->get_record('block_instances', array('id' => $restriction->prblockid));
				}
				$instance = block_instance('game_points', $block_info);
				
				
				$url = new moodle_url('/blocks/game_points/restrictiondelete.php', array('restrictionid' => $restriction->id, 'courseid' => $COURSE->id));
				$html .= '<tr><td>Os pontos do aluno no' . (isset($restriction->prblockid) ? ' bloco ' . $instance->title  : ' sistema de pontos ' . $restriction->prpointsystemid . ' (bloco ' . $instance->title . ')' ) . ' devem ser ' . $operators_array[$restriction->properator] . ' ' . $restriction->prpoints . ($restriction->properator == BETWEEN ? (' e ' . $restriction->prpointsbetween) : '') . ' pontos' . '</td><td>' . html_writer::link($url, 'Remover') . '</td></tr>';
			}
			else // Restrição por conteúdo desbloqueado
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
		}
		$url = new moodle_url('/blocks/game_points/restrictionadd.php', array('pointsystemid' => $this->pointsystemid, 'courseid' => $COURSE->id));
		$html .= '</table>' . html_writer::link($url, 'Adicionar restrição');
		
		$mform->addElement('html', $html);
 
        $mform->addElement('hidden', 'pointsystemid');
		$mform->addElement('hidden', 'courseid');
		
		$this->add_action_buttons();
    }
}

?>