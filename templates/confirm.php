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

<?php shopp( 'checkout.cart-summary' ); ?>

<form action="<?php shopp( 'checkout.url' ); ?>" method="post" class="shopp" id="checkout">
	<?php shopp( 'checkout.function', 'value=confirmed' ); ?>
	<p class="submit"><?php shopp( 'checkout.confirm-button', 'value=' . Shopp::__( 'Confirm Order' ) ); ?></p>
</form>
