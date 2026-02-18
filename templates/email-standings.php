<?php if (!defined('ABSPATH')) exit; ?>
<html>
  <body style="font-family: Arial, sans-serif; color: #333;">
    <h2 style="color: #2c3e50;">Final Standings – Swiss Tournament</h2>

    <p>Hello <strong><?php echo esc_html($player['first_name']); ?></strong>,</p>
    <p>The tournament has concluded. Here are the final results:</p>

    <table style="border-collapse: collapse; width: 100%;">
      <thead>
        <tr style="background: #f0f0f0;">
          <th style="border: 1px solid #ccc; padding: 5px;">Rank</th>
          <th style="border: 1px solid #ccc; padding: 5px;">Player</th>
          <th style="border: 1px solid #ccc; padding: 5px;">W</th>
          <th style="border: 1px solid #ccc; padding: 5px;">L</th>
          <th style="border: 1px solid #ccc; padding: 5px;">D</th>
          <th style="border: 1px solid #ccc; padding: 5px;">Points</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($standings as $rank => $p): ?>
        <tr>
          <td style="border: 1px solid #ccc; padding: 5px;">
            <?php echo esc_html( intval( $rank ) + 1 ); ?>
          </td>

          <td style="border: 1px solid #ccc; padding: 5px;">
            <?php echo esc_html( $p['first_name'] . ' ' . $p['last_name'] ); ?>
          </td>

          <td style="border: 1px solid #ccc; padding: 5px;">
            <?php echo esc_html( intval( $p['wins'] ) ); ?>
          </td>

          <td style="border: 1px solid #ccc; padding: 5px;">
            <?php echo esc_html( intval( $p['losses'] ) ); ?>
          </td>

          <td style="border: 1px solid #ccc; padding: 5px;">
            <?php echo esc_html( intval( $p['draws'] ) ); ?>
          </td>

          <td style="border: 1px solid #ccc; padding: 5px;">
            <?php echo esc_html( intval( $p['points'] ) ); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <p>Thanks for participating, and we hope to see you in the next tournament!</p>
    <hr>
    <small style="color:#888;">This email was sent automatically by Swiss Tourney.</small>
  </body>
</html>
