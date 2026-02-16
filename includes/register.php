<?php
if (!defined('ABSPATH')) {
    exit;
}

// --------------------
// Generate unique Player ID (GLOBAL FUNCTION)
// --------------------
if (!function_exists('swiss_generate_player_id')) {
    function swiss_generate_player_id($tid, $wpdb) {
        for ($i = 0; $i < 25; $i++) {
            $id = (string) random_int(10000000, 99999999);
            $exists = intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}swiss_players
                 WHERE tournament_id=%d AND dci=%s",
                $tid, $id
            )));
            if ($exists === 0) return $id;
        }
        return '';
    }
}

add_shortcode('swiss_register', function ($atts) {
    global $wpdb;

    $atts = shortcode_atts(['tournament' => 0], $atts);

    // Resolve tournament (explicit > active option > latest)
    $tid = intval($atts['tournament']);
    if (!$tid) $tid = intval(get_option('swiss_tourney_active_tournament_id'));
    if (!$tid) $tid = intval($wpdb->get_var("SELECT id FROM {$wpdb->prefix}swiss_tournaments ORDER BY id DESC LIMIT 1"));
    if (!$tid) return '<p>No active tournament.</p>';

    $reg_open = intval(get_option("swiss_reg_open_{$tid}", 1)); // 1=open, 0=locked
    $message = '';

    // Success message after redirect (prevents duplicate insert on refresh)
    if (isset($_GET['swiss_registered']) && $_GET['swiss_registered'] === '1') {
        $pid = sanitize_text_field($_GET['pid'] ?? '');
        $message = '<p class="swiss-success">Registered successfully!'
            . ($pid ? ' Your Player ID: <strong>' . esc_html($pid) . '</strong>' : '')
            . '</p>';

     // ✅ Clean the URL in the address bar (keeps message on page)
    $message .= '<script>
      (function(){
        if (window.history && window.history.replaceState) {
          var clean = window.location.href.split("?")[0];
          window.history.replaceState({}, document.title, clean);
        }
      })();
    </script>';
            
    }

    // Handle form submit
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['swiss_register_submit'])
        && isset($_POST['swiss_register_nonce'])
        && wp_verify_nonce($_POST['swiss_register_nonce'], 'swiss_register')

    ) {
          $post = wp_unslash($_POST);
          
        if (!$reg_open) {
            $message = '<p class="swiss-error">Registration is locked (tournament in progress).</p>';
        } else {
            $post  = wp_unslash($_POST);

            $first = sanitize_text_field($_POST['first_name'] ?? '');
            $last  = sanitize_text_field($_POST['last_name'] ?? '');
            $dci   = sanitize_text_field($_POST['dci'] ?? '');

            $email = sanitize_email($_POST['email'] ?? '');
            if ($email && !is_email($email)) {
                $email = '';
            }

            if (!$first) {
                $message = '<p class="swiss-error">First Name is required.</p>';
            } else {

                // Auto-generate Player ID if blank
                if (!$dci) {
                    $dci = swiss_generate_player_id($tid, $wpdb);
                    if (!$dci) {
                        $message = '<p class="swiss-error">Could not generate Player ID.</p>';
                    }
                }

                if (!$message) {
                    // Prevent duplicates within tournament
                    $exists = intval($wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}swiss_players
                         WHERE tournament_id=%d AND dci=%s",
                        $tid, $dci
                    )));

                    if ($exists > 0) {
                        $message = '<p class="swiss-error">Player ID already used in this tournament.</p>';
                    } else {
                        $ok = $wpdb->insert($wpdb->prefix . 'swiss_players', [
                            'tournament_id' => $tid,
                            'first_name'    => $first,
                            'last_name'     => $last,
                            'email'         => $email,
                            'dci'           => $dci,
                            'dropped'       => 0,
                        ]);

                        if ($ok) {
                            // ✅ Redirect to SAME PAGE (no referer dependency)
                            $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));
                            $current_url = remove_query_arg(['swiss_registered','pid'], $current_url);
                            $current_url = add_query_arg([
                                'swiss_registered' => '1',
                                'pid' => $dci,
                            ], $current_url);

                            wp_safe_redirect($current_url);
                            exit;
                        } else {
                            $message = '<p class="swiss-error">Registration failed. Please try again.</p>';
                        }
                    }
                }
            }
        }
    }

    ob_start();
    echo $message;

    // Show form only if registration open
    if ($reg_open) {
        include plugin_dir_path(dirname(__FILE__)) . 'templates/register-form.php';
    } else {
        echo '<p><strong>Registration is closed for this tournament.</strong></p>';
    }

    // Always show current players list
    $players = $wpdb->get_results($wpdb->prepare(
        "SELECT first_name, last_name, dci, dropped
         FROM {$wpdb->prefix}swiss_players
         WHERE tournament_id = %d
         ORDER BY id ASC",
        $tid
    ));

    echo '<hr>';
    echo '<h3>Current Players (' . intval(count($players)) . ')</h3>';

    if (empty($players)) {
        echo '<p><em>No players registered yet.</em></p>';
    } else {
        echo '<ul class="swiss-player-list">';
        foreach ($players as $p) {
            $name = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
            if ($name === '') $name = '(No name)';
            $line = esc_html($name) . ' — ID: <strong>' . esc_html($p->dci) . '</strong>';
            if (intval($p->dropped) === 1) $line .= ' <strong>(Dropped)</strong>';
            echo '<li>' . $line . '</li>';
        }
        echo '</ul>';
    }

    return ob_get_clean();
});

