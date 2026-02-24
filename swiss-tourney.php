<?php
/*
Plugin Name: Swiss Tourney
Plugin URI:  https://expandmore.xyz/swiss-tourney-wordpress-plugin/
Description: Swiss-style tournament manager with registration, pairings, standings, countdown, and email reminders.
Version:     0.3.5
Author:      Ninacess
Author URI:  https://expandmore.xyz/
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


// Security check
if (!defined('ABSPATH')) exit;

// --------------------
// DB: load + install/upgrade
// --------------------
require_once plugin_dir_path(__FILE__) . 'includes/db.php';

// Bump this when you change table schemas.
define('SWISS_TOURNEY_DB_VERSION', '0.3.5');

// Activation Hook (ONLY ONCE)
register_activation_hook(__FILE__, 'swiss_tourney_install');

function swiss_tourney_install() {
    swiss_tourney_create_tables();
    update_option('swiss_tourney_db_version', SWISS_TOURNEY_DB_VERSION);
}

// Run DB upgrades automatically if schema version changes.
add_action('plugins_loaded', function () {
    $installed = get_option('swiss_tourney_db_version');
    if ($installed !== SWISS_TOURNEY_DB_VERSION) {
        swiss_tourney_create_tables();
        update_option('swiss_tourney_db_version', SWISS_TOURNEY_DB_VERSION);
    }
});

// --------------------
// Load Includes
// --------------------
require_once plugin_dir_path(__FILE__) . 'includes/pairing.php';
require_once plugin_dir_path(__FILE__) . 'includes/countdown.php';
require_once plugin_dir_path(__FILE__) . 'includes/emails.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/standings.php';
require_once plugin_dir_path(__FILE__) . 'includes/register.php';


// --------------------
// Enqueue CSS & JS
// --------------------
add_action('wp_enqueue_scripts', function(){

    // Styles
    wp_enqueue_style(
        'swiss-tourney-style',
        plugin_dir_url(__FILE__) . 'assets/css/style.css',
        [],
        '0.3.0'
    );

    // Countdown
    wp_enqueue_script(
        'swiss-tourney-countdown',
        plugin_dir_url(__FILE__) . 'assets/js/countdown.js',
        [],
        '0.3.0',
        true
    );

    // Standings
    wp_enqueue_script(
        'swiss-tourney-standings',
        plugin_dir_url(__FILE__) . 'assets/js/standings.js',
        [],
        '0.3.0',
        true
    );

    // Pairings
    wp_enqueue_script(
        'swiss-tourney-pairings',
        plugin_dir_url(__FILE__) . 'assets/js/pairings.js',
        [],
        '0.3.0',
        true
    );
});
