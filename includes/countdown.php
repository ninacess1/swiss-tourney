<?php
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('swiss_countdown', function ($atts) {
    global $wpdb;

    $atts = shortcode_atts(['tournament' => 0], $atts);

    // Resolve tournament
    $tid = intval($atts['tournament']);
    if (!$tid) $tid = intval(get_option('swiss_tourney_active_tournament_id'));
    if (!$tid) $tid = intval($wpdb->get_var("SELECT id FROM {$wpdb->prefix}swiss_tournaments ORDER BY id DESC LIMIT 1"));
    if (!$tid) return '<p>No active tournament.</p>';

    $end = intval(get_option("swiss_tourney_round_end_{$tid}", 0));

    wp_enqueue_script('swiss-tourney-countdown');

    wp_localize_script('swiss-tourney-countdown', 'swissCountdownData', [
        
         'end' => $end,
    ]);

    ob_start();
    include plugin_dir_path(dirname(__FILE__)) . 'templates/countdown.php';
    return ob_get_clean();
});
