<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
<ul>
	<li>
		<span><input type="text" name="email" size="32" /><label><?php Shopp::_e('E-mail Address'); ?></label></span>
		<span><input type="submit" name="vieworder" value="<?php Shopp::_e('Request Data'); ?>" /></span>
	</li>
</ul>
<br class="clear" />
</form>