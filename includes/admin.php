<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function () {
    add_menu_page(
        'Swiss Tourney',
        'Swiss Tourney',
        'manage_options',
        'swiss-tourney',
        'swiss_tourney_admin_page',
        'dashicons-awards'
    );
});

function swiss_tourney_admin_page() {
    global $wpdb;

    $logo_url = plugins_url('assets/images/logo.png', dirname(__FILE__));
        echo '<div class="wrap">';
        echo '<img src="' . esc_url($logo_url) . '" alt="Swiss Tourney" style="max-width:220px;height:auto;margin:10px 0;" />';
        echo '<h1>Swiss Tourney</h1>';

    // Handle Set Active
    if (isset($_GET['set_active']) && check_admin_referer('swiss_set_active')) {
        $set_active = absint( wp_unslash($_GET['set_active']) );
        update_option('swiss_tourney_active_tournament_id', $set_active);
        echo '<div class="updated"><p>Active tournament set.</p></div>';
    }

    // Handle Delete
    if (isset($_GET['delete_tournament']) &&
        current_user_can('manage_options') && check_admin_referer('swiss_delete_tournament')) {



        $del = absint( wp_unslash($_GET['delete_tournament']) );

        $wpdb->delete($wpdb->prefix.'swiss_pairings', ['tournament_id'=>$del]);
        $wpdb->delete($wpdb->prefix.'swiss_players',  ['tournament_id'=>$del]);
        $wpdb->delete($wpdb->prefix.'swiss_tournaments', ['id'=>$del]);

        // Delete tournament-scoped options 
        delete_option("swiss_reg_open_{$del}");
        delete_option("swiss_tourney_round_end_{$del}");
        delete_option("swiss_round_minutes_{$del}"); 

        // If the deleted tournament was active, clear the global active tournament option
        if (intval(get_option('swiss_tourney_active_tournament_id')) === $del) {
            delete_option('swiss_tourney_active_tournament_id');
        }

        echo '<div class="updated"><p>Tournament deleted.</p></div>';
    }

    // Create Tournament
    if (isset($_POST['create_tournament']) && check_admin_referer('swiss_create_tournament')) {

        $tournament_name = isset($_POST['tournament_name'])
            ? sanitize_text_field( wp_unslash($_POST['tournament_name']) )
            : '';

        $total_rounds = isset($_POST['total_rounds'])
            ? absint( wp_unslash($_POST['total_rounds']) )
            : 0;

        $wpdb->insert($wpdb->prefix.'swiss_tournaments', [
            'title'        => $tournament_name,
            'total_rounds' => $total_rounds,
            'created_at'   => current_time('mysql')
        ]);

        update_option('swiss_tourney_active_tournament_id', $wpdb->insert_id);
        echo '<div class="updated"><p>Tournament created and set active.</p></div>';
    }

    echo '<div class="wrap"><h1>Swiss Tournament Manager</h1>';

    // Create form
    echo '<h2>Create Tournament</h2><form method="post">';
    wp_nonce_field('swiss_create_tournament');
    echo '<p>Name <input name="tournament_name" required></p>';
    echo '<p>Total Rounds <input type="number" name="total_rounds" value="4"></p>';
    echo '<p><button name="create_tournament" class="button-primary">Create</button></p></form>';

    // List tournaments
    $active = intval(get_option('swiss_tourney_active_tournament_id'));
    $tournaments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}swiss_tournaments ORDER BY id DESC", ARRAY_A);

    echo '<h2>Existing Tournaments</h2><table class="widefat"><tr><th>ID</th><th>Name</th><th>Rounds</th><th>Actions</th></tr>';
    foreach ($tournaments as $t) {
        echo '<tr>';
        echo '<td>'.esc_html($t['id']).'</td>';
        echo '<td>'.esc_html($t['title']).($active===$t['id']?' <strong>(Active)</strong>':'').'</td>';
        echo '<td>'.esc_html($t['total_rounds']).'</td>';
        echo '<td>
            <a class="button" href="' . esc_url(wp_nonce_url('?page=swiss-tourney&set_active=' . intval($t['id']),'swiss_set_active')) . '">Set Active</a>
            <a class="button button-danger" href="'.esc_url(wp_nonce_url('?page=swiss-tourney&delete_tournament='.intval($t['id']),'swiss_delete_tournament')).'">Delete</a>
            <a class="button" href="?page=swiss-tourney&manage='.intval($t['id']).'">Manage Rounds</a>
        </td>';
        echo '</tr>';
    }
    echo '</table>';

    // Manage rounds
    if (isset($_GET['manage'])) {
        $tid = absint( wp_unslash($_GET['manage']) );

    // Handle Drop/Undrop actions
    if (isset($_POST['toggle_drop_player']) && isset($_POST['player_id']) && 
        check_admin_referer('swiss_manage_players')
    ) {
        $post = wp_unslash($_POST);

        $pid  = isset($post['player_id']) ? absint($post['player_id']) : 0;
        $drop = isset($post['drop']) ? absint($post['drop']) : 0;
        $drop = ($drop === 1) ? 1 : 0;

        $wpdb->update(
            $wpdb->prefix.'swiss_players',
            ['dropped' => $drop],
            ['id' => $pid, 'tournament_id' => $tid]
        );

        echo '<div class="updated"><p>' . esc_html__('Player status updated.', 'swiss-tourney') . '</p></div>';
    }

        // then current/next round Logic continues...
        $current = intval($wpdb->get_var($wpdb->prepare(
            "SELECT MAX(round_number) FROM {$wpdb->prefix}swiss_pairings WHERE tournament_id=%d", $tid
        )));
        $next = $current + 1;

        echo '<h2>Manage Tournament #' . esc_html( intval( $tid ) ) . '</h2>';
        echo '<p><strong>Current Round:</strong> ' . esc_html( $current ? intval($current) : 'None' ) . '</p>';
        echo '<p><strong>Next Round:</strong> ' . esc_html( intval($next) ) . '</p>';

        // ====================
        // Countdown Controls
        // ====================
        if (isset($_POST['timer_action']) && check_admin_referer('swiss_timer_controls')) {

            if ( ! current_user_can('manage_options') ) {
                wp_die(esc_html__('You do not have permission to do that.', 'swiss-tourney'));
            }

            $post = wp_unslash($_POST);

            $minutes = isset($post['round_minutes']) ? absint($post['round_minutes']) : 50;
            $minutes = max(1, $minutes);
            update_option("swiss_round_minutes_{$tid}", $minutes);

            $action = isset($post['timer_action']) ? sanitize_text_field($post['timer_action']) : '';
            
            $allowed_actions = array('start', 'stop', 'reset');
            $action = in_array($action, $allowed_actions, true) ? $action : '';
        
        if ($action === 'start' || $action === 'reset') {

            $end_time = time() + ($minutes * 60);
            update_option("swiss_tourney_round_end_{$tid}", $end_time);

            $status = ($action === 'reset') ? 'reset' : 'started';

            echo '<div class="updated"><p>' .
                esc_html( 'Countdown ' . $status . '.' ) .
                '</p></div>';

        } elseif ($action === 'stop') {

            update_option("swiss_tourney_round_end_{$tid}", 0);

            echo '<div class="updated"><p>' .
                esc_html__('Countdown stopped.', 'swiss-tourney') .
                '</p></div>';
        }
    }

        $current_end    = intval(get_option("swiss_tourney_round_end_{$tid}", 0));
        $stored_minutes = intval(get_option("swiss_round_minutes_{$tid}", 50));

        echo '<h3>Countdown Timer</h3>';
        echo '<p><strong>Status:</strong> ' . ($current_end ? 'Running' : 'Stopped') . '</p>';

        echo '<form method="post" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px;">';
        wp_nonce_field('swiss_timer_controls');
        echo '<label>Round Minutes <input type="number" min="1" name="round_minutes" value="'.esc_attr($stored_minutes).'" style="width:90px;"></label>';
        echo '<button class="button" name="timer_action" value="start">Start</button>';
        echo '<button class="button" name="timer_action" value="stop">Stop</button>';
        echo '<button class="button" name="timer_action" value="reset">Reset</button>';
        echo '</form>';



        /* ✅ PLAYERS TABLE UI GOES HERE (PUT THIS HERE) */
        echo '<h3>Players</h3>';

        $roster = $wpdb->get_results($wpdb->prepare("
            SELECT id, first_name, last_name, dci, dropped, points, wins, losses, draws
            FROM {$wpdb->prefix}swiss_players
            WHERE tournament_id=%d
            ORDER BY dropped ASC, last_name ASC, first_name ASC
        ", $tid), ARRAY_A);

        if (!$roster) {
            echo '<p>No players registered for this tournament yet.</p>';
        } else {
            echo '<table class="widefat"><thead><tr>
                <th>Name</th><th>Player ID</th><th>Status</th><th>W-L-D</th><th>Points</th><th>Action</th>
            </tr></thead><tbody>';

        foreach ($roster as $p) {
            $name = esc_html(trim($p['first_name'].' '.$p['last_name']));
            $dci  = esc_html($p['dci']);
            $status = intval($p['dropped'])
                ? '<span style="color:#b32d2e;font-weight:600;">Dropped</span>'
                : '<span style="color:#1d7a1d;font-weight:600;">Active</span>';

            $wld = intval($p['wins']).'-'.intval($p['losses']).'-'.intval($p['draws']);
            $pts = esc_html($p['points']);

            $next_drop = intval($p['dropped']) ? 0 : 1;
            $btn_label = intval($p['dropped']) ? 'Undrop' : 'Drop';

            echo '<tr>';
            echo '<td>' . esc_html($name) . '</td>';
            echo '<td>' . esc_html( $dci ) . '</td>';
            echo '<td>' . wp_kses_post( $status ) . '</td>';
            echo '<td>' . esc_html( $wld ) . '</td>';
            echo '<td>' . esc_html( $pts ) . '</td>';
            echo '<td>
                <form method="post" style="margin:0;">';
            wp_nonce_field('swiss_manage_players');
            echo '<input type="hidden" name="player_id" value="'.intval($p['id']).'">';
            echo '<input type="hidden" name="drop" value="' . esc_attr( $next_drop ) . '">';
            echo '<button class="button" name="toggle_drop_player" value="1">'
                . esc_html( $btn_label )
                . '</button>
                </form>
            </td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
    /* ✅ END PLAYERS TABLE UI */

    // --------------------
// Save match results (Win/Loss/Draw)
// --------------------
if (isset($_POST['save_results']) && check_admin_referer('swiss_save_results')) {

    if ( ! current_user_can('manage_options') ) {
        wp_die(esc_html__('You do not have permission to do that.', 'swiss-tourney'));
    }

    $post    = wp_unslash($_POST);
    $results = (isset($post['result']) && is_array($post['result'])) ? $post['result'] : array();

    foreach ($results as $pairing_id => $res) {
        $pairing_id = absint($pairing_id);

        $res = sanitize_text_field($res);
        $res = in_array($res, array('A','B','D',''), true) ? $res : '';

        $wpdb->update(
            $wpdb->prefix.'swiss_pairings',
            array('result' => $res),
            array('id' => $pairing_id, 'tournament_id' => $tid)
        );
    }

    swiss_recalculate_standings($tid);

    echo '<div class="updated"><p>' . esc_html__('Results saved. Standings updated.', 'swiss-tourney') . '</p></div>';
}

// --------------------
// Pairings display (current round) + result entry
// --------------------
$current_round_for_results = intval($wpdb->get_var($wpdb->prepare("
    SELECT MAX(round_number)
    FROM {$wpdb->prefix}swiss_pairings
    WHERE tournament_id=%d
", $tid)));

echo '<h3>Round Pairings & Results</h3>';

if ($current_round_for_results <= 0) {
    echo '<p>No rounds have been locked yet.</p>';
} else {
    echo '<p><strong>Editing results for Round:</strong> '.intval($current_round_for_results).'</p>';

    $matches = $wpdb->get_results($wpdb->prepare("
        SELECT p.id, p.table_no, p.result, p.player_b_id,
               a.first_name a_first, a.last_name a_last, a.dci a_dci,
               b.first_name b_first, b.last_name b_last, b.dci b_dci
        FROM {$wpdb->prefix}swiss_pairings p
        LEFT JOIN {$wpdb->prefix}swiss_players a ON p.player_a_id=a.id
        LEFT JOIN {$wpdb->prefix}swiss_players b ON p.player_b_id=b.id
        WHERE p.tournament_id=%d AND p.round_number=%d
        ORDER BY p.table_no ASC
    ", $tid, $current_round_for_results), ARRAY_A);

    echo '<form method="post">';
    wp_nonce_field('swiss_save_results');

    echo '<table class="widefat"><thead><tr>
        <th>Table</th>
        <th>Player A</th>
        <th>Player B</th>
        <th>Result</th>
    </tr></thead><tbody>';

    foreach ($matches as $m) {
        $a = esc_html(trim($m['a_first'].' '.$m['a_last']).' ('.$m['a_dci'].')');
        $b = $m['player_b_id']
            ? esc_html(trim($m['b_first'].' '.$m['b_last']).' ('.$m['b_dci'].')')
            : 'BYE';

        echo '<tr>';
        echo '<td>'.intval($m['table_no']).'</td>';
        echo '<td>' . esc_html( $a ) . '</td>';
        echo '<td>' . esc_html( $b ) . '</td>';


        if (!$m['player_b_id']) {
            echo '<td>BYE (auto win for A)</td>';
        } else {
            $cur = $m['result'] ?? '';
            echo '<td>
                <select name="result['.intval($m['id']).']">
                    <option value="" '.selected($cur,'',false).'>— Not reported —</option>
                    <option value="A" '.selected($cur,'A',false).'>A Wins</option>
                    <option value="B" '.selected($cur,'B',false).'>B Wins</option>
                    <option value="D" '.selected($cur,'D',false).'>Draw</option>
                </select>
            </td>';
        }

        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<p><button type="submit" class="button-primary" name="save_results" value="1">Save Results</button></p>';
    echo '</form>';
}


    // existing round Lock handler + form //
    if (isset($_POST['lock_round']) && check_admin_referer('swiss_manage_round')) {

    if ( ! current_user_can('manage_options') ) {
        wp_die(esc_html__('You do not have permission to do that.', 'swiss-tourney'));
    }

    $post = wp_unslash($_POST);
    $round_number = isset($post['round_number']) ? absint($post['round_number']) : 0;

    // ✅ Ensure frontend uses the same tournament you’re managing
    update_option('swiss_tourney_active_tournament_id', $tid);

    // ✅ Lock registration once a round is started
    update_option("swiss_reg_open_{$tid}", 0);

    if (!swiss_lock_round($tid, $round_number)) {
        echo '<div class="error"><p>' . esc_html__('Round already exists.', 'swiss-tourney') . '</p></div>';
    } else {
        echo '<div class="updated"><p>' . esc_html__('Round locked. Pairings generated.', 'swiss-tourney') . '</p></div>';
    }
}


        echo '<form method="post">';
        wp_nonce_field('swiss_manage_round');
        echo '<input type="number" name="round_number" value="' 
            . esc_attr( intval( $next ) ) 
            . '" required>';
        echo '<button type="submit" name="lock_round" class="button-primary">Lock & Generate Pairings</button>';
        echo '</form>';
    }

    echo '</div>';
}
