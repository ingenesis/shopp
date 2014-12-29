<div class="wrap shopp">

	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

		$logurl = wp_nonce_url(add_query_arg('action', 'shopp_debuglog', admin_url('admin-ajax.php')), 'wp_ajax_shopp_debuglog') . '#bottom';
	?>

	<form name="settings" id="system" action="<?php echo esc_url($this->url); ?>" method="post">
		<?php wp_nonce_field('shopp-system-log'); ?>

		<?php if (count(ShoppErrorLogging()->tail(2)) > 1): ?>
			<iframe id="logviewer" src="<?php echo esc_url($logurl); ?>">
			<p>Loading log file...</p>
			</iframe>

			<p class="alignright"><a href="<?php echo esc_url($logurl); ?>" class="button" target="_blank"><?php Shopp::_e('Open in New Window'); ?></a> <button name="resetlog" id="resetlog" value="resetlog" class="button"><?php Shopp::_e('Clear Log'); ?></button></p>
		<?php endif; ?>

	</form>
</div>
