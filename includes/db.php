<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('swiss_tourney_create_tables')) {
    function swiss_tourney_create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // dbDelta safely creates *and updates* tables (adds missing columns/indexes).
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tournaments = $wpdb->prefix . 'swiss_tournaments';
        $players     = $wpdb->prefix . 'swiss_players';
        $pairings    = $wpdb->prefix . 'swiss_pairings';

        $sql_tournaments = "CREATE TABLE $tournaments (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(191) NOT NULL,
            total_rounds INT(11) NOT NULL DEFAULT 4,
            total_players INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;";

        $sql_players = "CREATE TABLE $players (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT(20) UNSIGNED NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NULL,
            email VARCHAR(191) NULL,
            dci VARCHAR(50) NULL,
            points FLOAT NOT NULL DEFAULT 0,
            wins INT(11) NOT NULL DEFAULT 0,
            losses INT(11) NOT NULL DEFAULT 0,
            draws INT(11) NOT NULL DEFAULT 0,
            opponents LONGTEXT NOT NULL DEFAULT '',
            dropped TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY tournament_id (tournament_id),
            KEY dropped (dropped),
            UNIQUE KEY tournament_dci (tournament_id, dci)
        ) $charset;";

        $sql_pairings = "CREATE TABLE $pairings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT(20) UNSIGNED NOT NULL,
            round_number INT(11) NOT NULL,
            table_no INT(11) NOT NULL,
            player_a_id BIGINT(20) UNSIGNED NOT NULL,
            player_b_id BIGINT(20) UNSIGNED NULL,
            result VARCHAR(2) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY tournament_round (tournament_id, round_number),
            KEY table_no (table_no),
            UNIQUE KEY uniq_round_table (tournament_id, round_number, table_no)
        ) $charset;";

        dbDelta($sql_tournaments);
        dbDelta($sql_players);
        dbDelta($sql_pairings);
    }
}
