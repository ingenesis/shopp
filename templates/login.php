<?php
/**
 ** WARNING! DO NOT EDIT!
 **
 ** These templates are part of the core Shopp files
 ** and will be overwritten when upgrading Shopp.
 **
 ** For editable templates, setup Shopp theme templates:
 ** http://shopplugin.com/docs/the-catalog/theme-templates/
 **
 **/
?>

<?php if ( shopp( 'customer.notloggedin' ) ) : ?>
	<form action="<?php shopp( 'customer.url' ); ?>" method="post" class="shopp shopp_page" id="login">
		<ul>
			<li>
				<label for="login"><?php _e( 'Account Login', 'Shopp' ); ?></label>
				<span>
					<label for="login"><?php shopp( 'customer.login-label' ); ?></label>
					<?php shopp( 'customer.account-login', 'size=20&title=' . __( 'Login', 'Shopp' ) ); ?>
				</span>
				<span>
					<label for="password"><?php _e( 'Password', 'Shopp' ); ?></label>
					<?php shopp( 'customer.password-login', 'size=20&title=' . __( 'Password', 'Shopp' ) ); ?>
				</span>
				<span>
					<label>&nbsp;</label>
					<?php shopp( 'customer.login-button' ); ?>
				</span>
			</li>
			<li>
				<a href="<?php shopp( 'customer.recover-url' ); ?>"><?php _e( 'Lost your password?', 'Shopp' ); ?></a>
			</li>
		</ul>
	</form>
<?php endif; ?>