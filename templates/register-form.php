<?php if (!defined('ABSPATH')) exit; ?>
<form method="post" class="swiss-register"
      action="<?php echo esc_url( remove_query_arg(['swiss_registered','pid']) ); ?>">
  <?php wp_nonce_field('swiss_register','swiss_register_nonce'); ?>
  <input type="hidden" name="tournament_id" value="<?php echo esc_attr($tid); ?>">
  
  <p><label>First Name*<br><input type="text" name="first_name" required></label></p>
  <p><label>Last Name<br><input type="text" name="last_name"></label></p>
  <p><label>Email (optional)<br><input type="email" name="email"></label></p>

  <p>
    <label>Player ID*<br>
      <input type="text" name="dci" placeholder="Enter your Player ID (leave blank to generate one)">
    </label><br>
    <small>Player ID is required to participate. If you don’t have one, leave it blank and the system will generate one.</small>
  </p>

  <p><button type="submit" name="swiss_register_submit" value="1">Register</button></p>

</form>
