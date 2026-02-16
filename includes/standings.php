<?php
if (!defined('ABSPATH')) exit;

add_shortcode('swiss_standings', function($atts){
    global $wpdb;

    if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
    nocache_headers();

    $atts = shortcode_atts(['tournament' => 0], $atts);

    // Resolve tournament id: explicit > active option > latest
    $tid = intval($atts['tournament']);
    if (!$tid) $tid = intval(get_option('swiss_tourney_active_tournament_id'));
    if (!$tid) $tid = intval($wpdb->get_var("SELECT id FROM {$wpdb->prefix}swiss_tournaments ORDER BY id DESC LIMIT 1"));
    if (!$tid) return '<p>No active tournament.</p>';

    // IMPORTANT: do NOT filter dropped players out here; show them with a status
    $players = $wpdb->get_results($wpdb->prepare("
        SELECT
            first_name,
            last_name,
            dci,
            wins,
            losses,
            draws,
            points,
            dropped
        FROM {$wpdb->prefix}swiss_players
        WHERE tournament_id = %d
        ORDER BY points DESC, wins DESC, dropped ASC, id ASC
    ", $tid), ARRAY_A);

    // Ensure standings script is enqueued (safe even if already enqueued globally)
    wp_enqueue_script('swiss-tourney-standings');

    // Provide data to JS
    wp_localize_script('swiss-tourney-standings', 'swissStandingsData', [
        'tid' => $tid,
        'players' => $players ?: [],
    ]);

    // Output container (your JS fills this)
    ob_start();
    include plugin_dir_path(dirname(__FILE__)) . 'templates/standings.php';
    return ob_get_clean();
});
