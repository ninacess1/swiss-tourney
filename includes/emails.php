<?php
if (!defined('ABSPATH')) exit;
// --------------------
// Round Reminder Email
// --------------------
function swiss_send_round_email($player, $opponent, $round_number, $table_no, $bye = false, $tournament_id = null) {
    if (empty($player['email'])) return;

    global $wpdb;

    // Try to resolve tournament title (optional)
    $tournament_title = 'Swiss Tournament';
    if ($tournament_id) {
        $row = $wpdb->get_row($wpdb->prepare("
            SELECT title FROM {$wpdb->prefix}swiss_tournaments WHERE id=%d
        ", intval($tournament_id)), ARRAY_A);

        if (!empty($row['title'])) $tournament_title = $row['title'];
    }

    $subject = "{$tournament_title} — Round {$round_number} Pairings";
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    ob_start();
    include plugin_dir_path(__FILE__) . '../templates/email-round.php';
    $body = ob_get_clean();

    wp_mail($player['email'], $subject, $body, $headers);
}

// --------------------
// Final Standings Email
// --------------------
function swiss_send_final_standings_email($tournament_id) {
    global $wpdb;

    $standings = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}swiss_players
        WHERE tournament_id=%d AND dropped=0
        ORDER BY points DESC, wins DESC
    ", $tournament_id), ARRAY_A);

    foreach ($standings as $player) {
        if (empty($player['email'])) continue;

        $subject = "Final Standings – Swiss Tournament";
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/email-standings.php';
        $body = ob_get_clean();

        wp_mail($player['email'], $subject, $body, $headers);
    }
}
