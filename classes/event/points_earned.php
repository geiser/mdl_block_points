<?php

namespace block_game_points\event;

defined('MOODLE_INTERNAL') || die();

class points_earned extends \core\event\base
{
    protected function init()
	{
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        //$this->data['objecttable'] = 'points_log';
    }

    public function get_description()
	{
        return "The user with id '$this->userid' has earned points with log id '$this->objectid'.";
    }

    public static function get_name()
	{
        return get_string('eventpointsearned', 'block_game_points');
    }
}
