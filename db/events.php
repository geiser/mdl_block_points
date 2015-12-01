<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\base',
        'callback' => 'block_game_points_helper::observer',
        'internal' => false
    ),
);