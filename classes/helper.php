<?php

defined('MOODLE_INTERNAL') || die();

class block_game_points_helper {

    public static function observer(\core\event\base $event) {
        global $DB;

        if (!self::is_student($event->relateduserid)) {
            return;
        }
		
    }

    protected static function is_student($userid) {
        return user_has_role_assignment($userid, 5);
    }

}
