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

<form action="<?php shopp( 'customer.url' ); ?>" method="post" class="shopp" id="login">
	<h3><?php Shopp::_e( 'Recover your password' ); ?></h3>
	<ul>
		<li>
			<span><?php shopp( 'customer.account-login', 'size=20&title=' . Shopp::__( 'Login' ) ); ?><label for="login"><?php shopp( 'customer.login-label' ); ?></label></span>
			<span><?php shopp( 'customer.recover-button' ); ?></span>
		</li>
	</ul>
</form>
