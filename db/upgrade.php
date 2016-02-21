<?php

function xmldb_block_game_points_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2016022015) {

        // Define field groupmode to be added to points_system.
        $table = new xmldb_table('points_system');
        $field = new xmldb_field('groupmode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'blockinstanceid');

        // Conditionally launch add field groupmode.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Game_points savepoint reached.
        upgrade_block_savepoint(true, 2016022015, 'game_points');
    }

	if ($oldversion < 2016022015) {

        // Define field groupingid to be added to points_system.
        $table = new xmldb_table('points_system');
        $field = new xmldb_field('groupingid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'groupmode');

        // Conditionally launch add field groupingid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Game_points savepoint reached.
        upgrade_block_savepoint(true, 2016022015, 'game_points');
    }

	if ($oldversion < 2016022101) {

        // Define table points_group_log to be created.
        $table = new xmldb_table('points_group_log');

        // Adding fields to table points_group_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('pointslogid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table points_group_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for points_group_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Game_points savepoint reached.
        upgrade_block_savepoint(true, 2016022101, 'game_points');
    }
	
    return true;
}

?>