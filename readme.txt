=== Swiss Tourney ===
Contributors: ninacess
Tags: tournament, swiss, pairings, standings, countdown, chess, mtg, yugioh, pokemon
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 0.3.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Swiss-system tournament manager for WordPress. Supports player registration, automatic round pairings, standings, countdown timers, and email reminders.

== Description ==

Swiss Tourney is a lightweight plugin to run Swiss-style tournaments directly on your WordPress site.  
Perfect for chess, Magic: The Gathering, Pokémon TCG, Yu-Gi-Oh, Hearthstone, or any competitive event that uses Swiss pairing.

Features:
* Player registration with name, optional email, and player ID
* Automatic Swiss-style round pairings (with BYE handling)
* Live standings with win/loss/draw and points
* Countdown timer for active rounds (admin controlled)
* Optional email reminders each round (if player provides email)
* Automatic Final Standings email at tournament end
* Shortcodes to embed tournament components on any page

The plugin does not connect to any external APIs or third-party services.

== Data & Privacy ==

Swiss Tourney stores tournament data locally in the WordPress database.

The plugin stores:
* Player first and last name
* Optional email address (if provided by the player)
* Player ID (if provided)
* Match results and tournament standings

Emails are stored only for tournament communication and are not displayed publicly.

When a tournament is deleted:
* All associated players are deleted
* All pairings are deleted
* Related tournament settings are removed

When the plugin is uninstalled (deleted from WordPress):
* All plugin database tables are removed
* All tournament data is permanently deleted
* All plugin-related options are removed from the database

The plugin does not:
* Send data to external servers
* Track users
* Use cookies beyond standard WordPress behavior
* Collect analytics

Site owners are responsible for ensuring compliance with local data protection laws (such as GDPR) when collecting player information.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/swiss-tourney/` directory, or install via the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Create a page and insert the desired shortcodes.

== Shortcodes ==

* `[swiss_register tournament="1"]` — Player registration form  
* `[swiss_standings tournament="1"]` — Show standings (auto-detects latest round)  
* `[swiss_pairings tournament="1"]` — Show pairings (auto-detects latest round)  
* `[swiss_countdown tournament="1"]` — Show countdown timer for current round  

If no tournament ID is specified, the active tournament will be used.

== Frequently Asked Questions ==

= Does this handle BYEs? =
Yes. If a tournament has an odd number of players, the lowest-ranked unpaired player is given a BYE (+1 point, +1 win).

= Are player emails displayed publicly? =
No. Email addresses are stored only for sending round notifications and are never displayed on the front end.

= Can I style the tables? =
Yes. The plugin outputs simple HTML tables with CSS classes (`swiss-standings`, `swiss-pairings`, etc.). You can style them via your theme CSS or the plugin’s stylesheet.

= Does the countdown start automatically? =
No. The admin controls the countdown timer using Start, Stop, and Reset buttons in the tournament management screen.

== Changelog ==

= 0.3.4 =
* Initial release with registration, pairings, standings, countdown, and email notifications.
* Added admin control for countdown (Start / Stop / Reset).
* Improved data cleanup when deleting tournaments.
== Upgrade Notice ==

= 0.3.4 =
First public release of Swiss Tourney.
