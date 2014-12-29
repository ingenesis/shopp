<div id="welcome" class="wrap about-wrap">
	<div class="icon32"></div>
	<h1><?php Shopp::_emi('Shopp Database **Update Required**'); ?></h1>

	<?php Shopp::_em(
'Shopp has been updated! Now the database needs updated to keep things humming along nice and smooth.

To protect your database, the storefront has been switched to maintenance mode until the database update is completed.'); ?>

	<?php if ( current_user_can('activate_plugins') ): ?>

		<div class="warning"><p><?php Shopp::_em('**IMPORTANT**

			Be sure to backup your database to prevent a loss of data! Backup now and come back later when you are ready to update. [How do I backup?](%s)', ShoppSupport::DOCS . 'getting-started/upgrading/'); ?></p></div>

		<?php Shopp::_em(
'The update process should only take a moment. Keep calm and don\'t panic. You did a backup right?'); ?>
		<?php
			$upgrade = wp_nonce_url(add_query_arg(array('action' => 'shopp-upgrade'), admin_url('admin.php')), 'shopp-upgrade');
		?>
		<p><a href="<?php echo $upgrade; ?>" class="button-primary"><?php Shopp::_e('Update Shopp Database'); ?></a></p>

	<?php else: ?>
		<?php if ( isset($_GET['_shopp_upgrade_notice']) ): ?>
			<?php

				check_admin_referer('shopp_upgrade_notice');
				$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
				$homeurl = wp_specialchars_decode(get_option('home'), ENT_QUOTES);
				$admin = get_bloginfo('admin_email');
				$site = parse_url($homeurl);

				$_ = array();
				$_[] = 'From: "' . $blogname . '" <' . shopp_setting('merchant_email') . '>';
				$_[] = 'To: ' . $admin;
				$_[] = sprintf('Subject: Shopp Upgraded on %s', $site['host']);
				$_[] = '';
				$_[] = sprintf(__('The Shopp installation on %1$s has been upgraded to %2$s and requires a database upgrade. Please login to %1$s and perform the upgrade by deactivating and reactivating the Shopp plugin.', 'Shopp'), $homeurl, ShoppVersion::release());

				$message = apply_filters('shopp_upgrade_notice_message', join("\n", $_));

				if ( Shopp::email($message) )
					shopp_debug('A Shopp upgrade notification was sent.');

				Shopp::_em(
'### Upgrade Notice Sent

An upgrade notice has been sent to the site administrator.'); ?>
		<?php else: ?>

			<div class="error"><?php Shopp::_em(
'### Contact Your Site Administrator

You will need to notify a site administrator to perform the upgrade.'); ?>
			</div>
			<div class="alignright">
			<a href="<?php echo wp_nonce_url(add_query_arg('_shopp_upgrade_notice', true),'shopp_upgrade_notice'); ?>" class="button-primary"><span class="shoppui-envelope-alt">&nbsp;</span> <?php Shopp::_e('Send Upgrade Notice'); ?></a>
			</div>

	<?php endif; endif; ?>
</div>