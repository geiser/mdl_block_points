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
 * Points earned event definition.
 *
 * @package    block_game_points
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
