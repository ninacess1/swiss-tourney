<?php
if (!defined('ABSPATH')) exit;
// Expecting: $player, $opponent, $round_number, $table_no, $bye
// Optional extras if provided by emails.php:
$player_name   = trim(($player['first_name'] ?? '') . ' ' . ($player['last_name'] ?? ''));
$player_id     = $player['dci'] ?? '';

$opp_name      = $opponent ? trim(($opponent['first_name'] ?? '') . ' ' . ($opponent['last_name'] ?? '')) : '';
$opp_id        = $opponent['dci'] ?? '';

$tournament_title = $tournament_title ?? 'Swiss Tournament';
$site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

// Public URL (you can override this with a filter in your theme or plugin config)
$public_url = apply_filters('swiss_tourney_public_url', home_url('/'));
?>

<html>
  <body style="font-family: Arial, sans-serif; color:#222; line-height:1.4;">
    <div style="max-width:640px;margin:0 auto;padding:16px;">
      <h2 style="margin:0 0 8px;color:#2c3e50;">
        <?php echo esc_html($tournament_title); ?> — Round <?php echo intval($round_number); ?>
      </h2>

      <p style="margin:0 0 12px;color:#555;">
        Hello <strong><?php echo esc_html($player_name ?: 'Player'); ?></strong>
        <?php if ($player_id): ?>
          <span style="color:#888;">(Player ID: <?php echo esc_html($player_id); ?>)</span>
        <?php endif; ?>
      </p>

      <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:#fafafa;margin:0 0 14px;">
        <p style="margin:0 0 6px;">
            <strong>Round:</strong> <?php echo (int) $round_number; ?>
        </p>

        <?php if (!empty($bye)): ?>
          <p style="margin:0;">
            ✅ You have a <strong>BYE</strong> this round and automatically earn <strong>1 point</strong>.
            You do not need to play a match this round.
          </p>
        <?php else: ?>
          <p style="margin:0;">
            <strong>Opponent:</strong> <?php echo esc_html($opp_name ?: 'TBD'); ?>
            <?php if ($opp_id): ?>
              <span style="color:#666;">(Player ID: <?php echo esc_html($opp_id); ?>)</span>
            <?php endif; ?>
          </p>
        <?php endif; ?>
      </div>

      <p style="margin:0 0 10px;">
        Please check the tournament page for live pairings and standings:
        <a href="<?php echo esc_url($public_url); ?>"><?php echo esc_html($site_name); ?></a>
      </p>

      <p style="margin:0 0 14px;color:#555;">
        Good luck and have fun!
      </p>

      <hr style="border:none;border-top:1px solid #eee;margin:16px 0;">
      <small style="color:#888;">
        This email was sent automatically by Swiss Tourney.
      </small>
    </div>
  </body>
</html>
