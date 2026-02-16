<?php
if (!defined('ABSPATH')) exit;
// --------------------
// Generate Pairings
// --------------------
function swiss_generate_pairings($tournament_id, $round_number) {
    global $wpdb;

    $tournament_id = intval($tournament_id);

    // Active players only (not dropped)
    $players = $wpdb->get_results($wpdb->prepare("
        SELECT id, first_name, last_name, email, dci, points, wins, losses, draws
        FROM {$wpdb->prefix}swiss_players
        WHERE tournament_id=%d AND dropped=0
        ORDER BY points DESC, wins DESC, id ASC
    ", $tournament_id), ARRAY_A);

    if (!$players) return [];

    // ---- Helper: how many BYEs has player had? ----
    $bye_counts = [];
    $bye_rows = $wpdb->get_results($wpdb->prepare("
        SELECT player_a_id, COUNT(*) AS c
        FROM {$wpdb->prefix}swiss_pairings
        WHERE tournament_id=%d AND player_b_id IS NULL
        GROUP BY player_a_id
    ", $tournament_id), ARRAY_A);

    foreach ($bye_rows as $r) {
        $bye_counts[intval($r['player_a_id'])] = intval($r['c']);
    }

    // ---- Helper: set of past opponents per player ----
    // We consider both sides as opponents.
    $opp_map = [];
    $pairs = $wpdb->get_results($wpdb->prepare("
        SELECT player_a_id, player_b_id
        FROM {$wpdb->prefix}swiss_pairings
        WHERE tournament_id=%d AND player_b_id IS NOT NULL
    ", $tournament_id), ARRAY_A);

    foreach ($pairs as $p) {
        $a = intval($p['player_a_id']);
        $b = intval($p['player_b_id']);
        if (!isset($opp_map[$a])) $opp_map[$a] = [];
        if (!isset($opp_map[$b])) $opp_map[$b] = [];
        $opp_map[$a][$b] = true;
        $opp_map[$b][$a] = true;
    }

    // ---- BYE selection (only if odd count) ----
    $bye_player = null;

    if (count($players) % 2 === 1) {
        // Prefer players with 0 byes, and among them the lowest score
        $candidates = $players;

        usort($candidates, function($p1, $p2) use ($bye_counts) {
            $b1 = $bye_counts[intval($p1['id'])] ?? 0;
            $b2 = $bye_counts[intval($p2['id'])] ?? 0;

            // fewer byes first
            if ($b1 !== $b2) return $b1 <=> $b2;

            // lowest points first (so top players don't get bye)
            if (floatval($p1['points']) !== floatval($p2['points'])) {
                return floatval($p1['points']) <=> floatval($p2['points']);
            }

            return intval($p1['id']) <=> intval($p2['id']);
        });

        $bye_player = $candidates[0]; // best bye candidate
        // remove bye player from pool
        $players = array_values(array_filter($players, fn($p) => intval($p['id']) !== intval($bye_player['id'])));
    }

    // ---- Pairing algorithm: greedy, avoids repeats when possible ----
    // Players already sorted high->low by points due to query ordering.
    $pairings = [];
    $used = [];
    $table_no = 1;

    $n = count($players);
    for ($i = 0; $i < $n; $i++) {
        $a = $players[$i];
        $a_id = intval($a['id']);

        if (isset($used[$a_id])) continue;

        // Find best opponent: first someone not used and not previously played if possible
        $best_j = null;

        // Pass 1: avoid repeats
        for ($j = $i + 1; $j < $n; $j++) {
            $b = $players[$j];
            $b_id = intval($b['id']);
            if (isset($used[$b_id])) continue;

            $played_before = isset($opp_map[$a_id][$b_id]);
            if (!$played_before) {
                $best_j = $j;
                break;
            }
        }

        // Pass 2: allow repeats if needed
        if ($best_j === null) {
            for ($j = $i + 1; $j < $n; $j++) {
                $b = $players[$j];
                $b_id = intval($b['id']);
                if (isset($used[$b_id])) continue;
                $best_j = $j;
                break;
            }
        }

        if ($best_j === null) {
            // No opponent found (shouldn't happen unless data oddity)
            break;
        }

        $b = $players[$best_j];

        $pairings[] = [
            'table_no' => $table_no++,
            'player_a' => $a,
            'player_b' => $b,
            'bye'      => false,
        ];

        $used[$a_id] = true;
        $used[intval($b['id'])] = true;
    }

    // Add BYE pairing at the end (Table = last)
    if ($bye_player) {
        $pairings[] = [
            'table_no' => $table_no,
            'player_a' => $bye_player,
            'player_b' => null,
            'bye'      => true,
        ];
    }

    return $pairings;
}

// --------------------
// Lock Round (ADMIN)
// --------------------
function swiss_lock_round($tournament_id, $round_number) {
    global $wpdb;

    // Block duplicate rounds
    $exists = intval($wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}swiss_pairings
        WHERE tournament_id=%d AND round_number=%d
    ", $tournament_id, $round_number)));

    if ($exists > 0) {
        return false;
    }

    $pairings = swiss_generate_pairings($tournament_id, $round_number);

    foreach ($pairings as $p) {
        $wpdb->insert($wpdb->prefix.'swiss_pairings', [
            'tournament_id' => $tournament_id,
            'round_number'  => $round_number,
            'table_no'      => $p['table_no'],
            'player_a_id'   => $p['player_a']['id'],
            'player_b_id'   => $p['player_b']['id'] ?? null,
            'result'        => $p['bye'] ? 'A' : ''
        ]);

    // Send emails
    if (!empty($p['bye'])) {
        swiss_send_round_email($p['player_a'], null, $round_number, $p['table_no'], true, $tournament_id);
    } else {
        swiss_send_round_email($p['player_a'], $p['player_b'], $round_number, $p['table_no'], false, $tournament_id);
        swiss_send_round_email($p['player_b'], $p['player_a'], $round_number, $p['table_no'], false, $tournament_id);
    }
    }

    swiss_recalculate_standings($tournament_id);

    return true;
}

// --------------------
// Recalculate Standings (from swiss_pairings results)
// --------------------
function swiss_recalculate_standings($tournament_id) {
    global $wpdb;

    $tournament_id = intval($tournament_id);

    // Reset stats for all players in this tournament
    $wpdb->query($wpdb->prepare("
        UPDATE {$wpdb->prefix}swiss_players
        SET wins=0, losses=0, draws=0, points=0
        WHERE tournament_id=%d
    ", $tournament_id));

    // Pull all pairings (all rounds) for this tournament
    $matches = $wpdb->get_results($wpdb->prepare("
        SELECT player_a_id, player_b_id, result
        FROM {$wpdb->prefix}swiss_pairings
        WHERE tournament_id=%d
    ", $tournament_id), ARRAY_A);

    foreach ($matches as $m) {
        $a = intval($m['player_a_id']);
        $b = $m['player_b_id'] !== null ? intval($m['player_b_id']) : null;
        $r = $m['result'];

        // BYE = A win (+1 point)
        if ($b === null) {
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->prefix}swiss_players
                SET wins=wins+1, points=points+1
                WHERE id=%d
            ", $a));
            continue;
        }

        // Skip unreported matches
        if (!in_array($r, ['A','B','D'], true)) {
            continue;
        }

        if ($r === 'A') {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}swiss_players SET wins=wins+1, points=points+1 WHERE id=%d", $a));
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}swiss_players SET losses=losses+1 WHERE id=%d", $b));
        } elseif ($r === 'B') {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}swiss_players SET wins=wins+1, points=points+1 WHERE id=%d", $b));
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}swiss_players SET losses=losses+1 WHERE id=%d", $a));
        } elseif ($r === 'D') {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}swiss_players SET draws=draws+1, points=points+0.5 WHERE id=%d", $a));
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}swiss_players SET draws=draws+1, points=points+0.5 WHERE id=%d", $b));
        }
    }
}


// --------------------
// Frontend Pairings Shortcode
// --------------------
add_shortcode('swiss_pairings', function ($atts) {
    global $wpdb;

    if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
    nocache_headers();

    $atts = shortcode_atts(['tournament' => 0], $atts);

    $tid = intval($atts['tournament']);
    if (!$tid) $tid = intval(get_option('swiss_tourney_active_tournament_id'));
    if (!$tid) $tid = intval(get_option('swiss_tourney_active_tid'));
    if (!$tid) $tid = intval(get_option('swiss_tourney_active_tournament'));

    if (!$tid) $tid = intval($wpdb->get_var("SELECT id FROM {$wpdb->prefix}swiss_tournaments ORDER BY id DESC LIMIT 1"));

    

    if (!$tid) return '<p>No tournament found.</p>';

    // Current round
    $round = intval($wpdb->get_var($wpdb->prepare("
        SELECT MAX(round_number)
        FROM {$wpdb->prefix}swiss_pairings
        WHERE tournament_id=%d
    ", $tid)));

    $pairings = [];
    if ($round > 0) {
        $pairings = $wpdb->get_results($wpdb->prepare("
            SELECT p.table_no,
                   a.first_name AS a_first, a.last_name AS a_last, a.dci AS a_dci,
                   b.first_name AS b_first, b.last_name AS b_last, b.dci AS b_dci
            FROM {$wpdb->prefix}swiss_pairings p
            LEFT JOIN {$wpdb->prefix}swiss_players a ON p.player_a_id=a.id
            LEFT JOIN {$wpdb->prefix}swiss_players b ON p.player_b_id=b.id
            WHERE p.tournament_id=%d AND p.round_number=%d
            ORDER BY p.table_no ASC
        ", $tid, $round), ARRAY_A);
    }

    wp_enqueue_script('swiss-tourney-pairings');
    wp_localize_script('swiss-tourney-pairings', 'swissPairingsData', [
        'round'    => $round,
        'pairings' => $pairings ?: []
    ]);

    ob_start();
    include plugin_dir_path(dirname(__FILE__)) . 'templates/pairings.php';
    return ob_get_clean();
});
