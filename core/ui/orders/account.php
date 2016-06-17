<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
<ul>
	<li>
		<span><input type="text" name="purchaseid" size="12" /><label><?php Shopp::_e('Order Number'); ?></label></span>
		<span><input type="text" name="email" size="32" /><label><?php Shopp::_e('E-mail Address'); ?></label></span>
		<span><input type="submit" name="vieworder" value="<?php Shopp::_e('View Order'); ?>" /></span>
	</li>
</ul>
<br class="clear" />
</form>