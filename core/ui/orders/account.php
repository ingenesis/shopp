<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
<ul>
	<li>
		<span><input type="text" name="purchaseid" size="12" /><label><?php _e('Order Number','Shopp'); ?></label></span>
		<span><input type="text" name="email" size="32" /><label><?php _e('E-mail Address','Shopp'); ?></label></span>
		<span><input type="submit" name="vieworder" value="<?php _e('View Order','Shopp'); ?>" /></span>
	</li>
</ul>
<br class="clear" />
</form>