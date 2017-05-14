<?php shopp_admin_screen_tabs(); ?>
<?php
	$logurl = wp_nonce_url(add_query_arg('action', 'shopp_debuglog', admin_url('admin-ajax.php')), 'wp_ajax_shopp_debuglog') . '#bottom';
	wp_nonce_field('shopp-system-log');
?>
<?php if ( ShoppErrorLogging()->size() > 1 ): ?>
	<iframe id="logviewer" src="<?php echo esc_url($logurl); ?>">
	<p>Loading log file...</p>
	</iframe>

	<p class="alignright"><a href="<?php echo esc_url($logurl); ?>" class="button" target="_blank"><?php Shopp::_e('Open in New Window'); ?></a> <button name="resetlog" id="resetlog" value="resetlog" class="button"><?php Shopp::_e('Clear Log'); ?></button></p>
<?php endif; ?>
