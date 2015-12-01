<?php

defined('MOODLE_INTERNAL') || die();

class block_game_points extends block_base {

    public function init() {
        $this->title = get_string('title', 'block_game_points');
    }

    public function get_content() {
		if ($this->content !== null) {
			return $this->content;
		}

		$this->content = new stdClass;
		$this->content->text = 'Conteúdo';
		$this->content->footer = 'Rodapé';

		return $this->content;
    }

    public function has_config() {
        return true;
    }
}