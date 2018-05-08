<h1><?php echo Shopp::_x('Shopp Activation Error', 'Shopp activation error'); ?></h1>

<p><?php echo Shopp::_x('Sorry! Shopp cannot be activated for this WordPress install.', 'Shopp activation error'); ?></p>

<ul>
	<?php foreach ( (array) $errors as $error ): ?>
	<li><?php echo esc_html($this->notice($error)); ?></li>
	<?php endforeach;?>
</ul>

<p><?php printf(Shopp::_x(
		'Try contacting your web hosting provider or server administrator to upgrade your server. For more information about the requirements for running Shopp, see the %sShopp Documentation%s',
		'Shopp activation error'
	),
	'<a href="' . ShoppSupport::DOCS . 'system-requirements">',
	'</a>'
); ?></p>

<p><a class="button" href="<?php esc_attr_e(admin_url('plugins.php')); ?>">&larr; <?php echo Shopp::_x('Return to Plugins page', 'Shopp activation error'); ?></a></p>