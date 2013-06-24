<div id="welcome" class="wrap">
	<h2><img src="<?php echo SHOPP_ICONS_URI; ?>/shopp32.png" alt="Shopp logo" width="32" height="32"/> <?php _e('Welcome to Shopp','Shopp'); ?></h2>

	<h3><?php _e('Congratulations on choosing Shopp and WordPress for your e-commerce solution!','Shopp'); ?></h3>

	<?php if(Shopp::suhosin_warning()): ?>
		<div class="error"><p><strong><?php _e('Warning!','Shopp'); ?></strong> <?php printf(__('You have the PHP Extension Suhosin Installed. You might need to change some configuration to work with Shopp. For more information, see %s"Suhosin PHP Extension Issues"%s.','Shopp'),'<a href="'.SHOPP_DOCS.'Suhosin_PHP_Extension_Issues">','</a>'); ?></p></div>
	<?php endif; ?>

	<p><?php _e('Before you dive in to setup, here are a few things to keep in mind:','Shopp'); ?></p>

	<ul>
		<li><strong><?php _e('Shopp has lots of easy to find help built-in.','Shopp'); ?></strong><br />
			<?php printf(__('Click the %sHelp menu%s to access help articles about the screen you are using, directly from the %sofficial documentation%s.','Shopp'),'<strong>','</strong>','<a href="http://docs.shopplugin.net" target="_blank">','</a>'); ?>
			<ul>
				<li><?php printf(__('You can also get community help from the community %sSupport Forums%s','Shopp'),'<a href="http://forums.shopplugin.net">','</a>'); ?></li>
				<li><?php printf(__('Or, get official interactive support from the Shopp %sHelp Desk%s','Shopp'),'<a href="http://forums.shopplugin.net/forum/help-desk">','</a>'); ?></li>
				<li><?php printf(__('For guaranteed fast response from the Shopp Support Team, %spurchase a priority support credit%s.','Shopp'),'<a href="https://shopplugin.net/store/category/priority-support/" target="_blank">','</a>'); ?></li>
				<li><?php _e('Find qualified Shopp professionals you can hire as consultant contractors for customization work.','Shopp'); ?></li>
			</ul>
			</li>
		<li><strong><?php _e('Easy setup in just a few steps.','Shopp'); ?></strong><br /><?php _e('Setup is simple and takes about 10-15 minutes.  Just jump through each of the settings screens to configure your store.','Shopp'); ?></li>
		<li><strong><?php _e('Don\'t forget to activate your key!','Shopp'); ?></strong><br /><?php printf(__('Be sure to activate your key on the %sShopp%s &rarr; %sSettings%s screen so you can get trouble-free, automated updates.','Shopp'),'<strong>','</strong>','<strong>','</strong>'); ?></li>
		<li><strong><?php _e('Show It Off','Shopp')?></strong><br /><?php printf(__('Once you\'re up and running, drop by the Shopp website and %ssubmit your site%s to be included in the showcase of Shopp-powered websites.','Shopp'),'<a href="http://shopplugin.net/showcase">','</a>'); ?></li>
	</ul>
	<br />

	<form action="<?php echo add_query_arg(array('page'=>'shopp-settings'),admin_url('admin.php')); ?>" method="post">
	<div class="alignright"><input type="submit" name="setup" value="<?php _e('Continue to Shopp Setup','Shopp'); ?>&hellip;" class="button-primary" /></div>

	<p><input type="hidden" name="settings[show_welcome]" value="off" /><input type="checkbox" name="settings[show_welcome]" id="welcome-toggle" value="on" <?php echo (shopp_setting('show_welcome') == "on")?' checked="checked"':''; ?> /><label for="welcome-toggle"> <small><?php _e('Show this screen every time after activating Shopp','Shopp'); ?></small></label></p>
	</form>
</div>