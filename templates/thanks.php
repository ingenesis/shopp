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

<h3><?php Shopp::_e( 'Thank you for your order!' ); ?></h3>

<?php if ( shopp( 'checkout.completed' ) ) : ?>

	<?php if ( shopp( 'purchase.notpaid' ) ) : ?>
		<p><?php Shopp::_e( 'Your order has been received but the payment has not yet completed processing.' ); ?></p>

		<?php if ( shopp( 'checkout.get-offline-instructions' ) ) : ?>
			<?php shopp( 'checkout.offline-instructions' ); ?>
		<?php endif; ?>

		<?php if ( shopp( 'purchase.hasdownloads' ) ) : ?>
			<p><?php Shopp::_e( 'The download links on your order receipt will not work until the payment is received.' ); ?></p>
		<?php endif; ?>

		<?php if ( shopp( 'purchase.hasfreight' ) ) : ?>
			<p><?php Shopp::_e( 'Your items will not ship out until the payment is received.' ); ?></p>
		<?php endif; ?>

	<?php endif; ?>

	<?php shopp( 'checkout.receipt' ); ?>

	<?php if ( shopp( 'customer.wpuser-created' ) ) : ?>
		<p><?php Shopp::_e( 'An email was sent with account login information to the email address provided for your order.' ); ?>  <a href="<?php shopp( 'customer.url' ); ?>"><?php Shopp::_e( 'Login to your account' ); ?></a> <?php Shopp::_e( 'to access your orders, change your password and manage your checkout information.' ); ?></p>
	<?php endif; ?>

<?php else : ?>
	<p><?php Shopp::_e( 'Your order is still in progress. Payment for your order has not yet been received from the payment processor. You will receive an email notification when your payment has been verified and the order has been completed.' ); ?></p>
<?php endif; ?>
