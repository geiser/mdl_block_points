<?php

function xmldb_block_game_points_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2016011200) {

        // Define field eventdescription to be added to points_system.
        $table = new xmldb_table('points_system');
        $field = new xmldb_field('eventdescription', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'deleted');

        // Conditionally launch add field eventdescription.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Game_points savepoint reached.
        upgrade_block_savepoint(true, XXXXXXXXXX, 'game_points');
    }

    return true;
}

?>