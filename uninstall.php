<?php
/**
 * Swiss Tourney - uninstall cleanup
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Deletes plugin tables and options.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 1) Drop plugin tables
$tables = [
    $wpdb->prefix . 'swiss_tournaments',
    $wpdb->prefix . 'swiss_players',
    $wpdb->prefix . 'swiss_pairings',
];

foreach ($tables as $table) {
    // Table names are constructed from $wpdb->prefix + known suffixes (safe)
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

// 2) Delete plugin options (global + per-tournament)
$option_patterns = [
    'swiss_tourney_active_tournament_id',
    'swiss_tourney_active_tid',
    'swiss_tourney_active_tournament',

    // tournament-scoped options you use (or might use)
    'swiss_reg_open_%',
    'swiss_tourney_round_end_%',
    'swiss_round_minutes_%',
    'swiss_tourney_current_round_%',
];

foreach ($option_patterns as $pattern) {
    if (str_contains($pattern, '%')) {
        // Delete by LIKE pattern
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $pattern
            )
        );
    } else {
        // Delete exact key
        delete_option($pattern);
    }
}