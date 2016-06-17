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

<div id="shopp-cart-ajax">
	<?php if ( shopp( 'cart.hasitems' ) ) : ?>
		<p class="status">
			<span id="shopp-sidecart-items"><?php shopp( 'cart.totalitems' ); ?></span> <strong><?php _e( 'Items', 'Shopp' ); ?></strong><br />
			<span id="shopp-sidecart-total" class="money"><?php shopp( 'cart.total' ); ?></span> <strong><?php _e( 'Total', 'Shopp' ); ?></strong>
		</p>
		<ul>
			<li><a href="<?php shopp( 'cart.url' ); ?>"><?php _e( 'Edit shopping cart', 'Shopp' ); ?></a></li>
			<li><a href="<?php shopp( 'checkout.url' ); ?>"><?php _e( 'Proceed to Checkout', 'Shopp' ); ?></a></li>
		</ul>
	<?php else : ?>
		<p class="status notice"><?php _e( 'Your cart is empty.', 'Shopp' ); ?></p>
	<?php endif; ?>
</div>
