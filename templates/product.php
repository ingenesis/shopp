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

<?php shopp( 'storefront.breadcrumb' ); ?>

<?php if ( shopp( 'product.found' ) ) : ?>
	<?php shopp( 'product.gallery', 'p_setting=gallery-previews&thumbsetting=gallery-thumbnails' ); ?>

	<h3><?php shopp( 'product.name' ); ?></h3>
	<p class="headline"><?php shopp( 'product.summary' ); ?></p>

	<?php shopp('product.schema'); ?>

	<?php if ( shopp( 'product.onsale' ) ) : ?>
		<h3 class="original price" ><?php shopp( 'product.price' ); ?></h3>
		<h3 class="sale price"><?php shopp( 'product.saleprice' ); ?></h3>
		<?php if ( shopp( 'product.has-savings' ) ): ?>
			<p class="savings"><?php _e( 'You save', 'Shopp' ); ?> <?php shopp( 'product.savings' ); ?> (<?php shopp( 'product.savings', 'show=%' ); ?>)!</p>
		<?php endif; ?>
	<?php else: ?>
		<h3 class="price"><?php shopp( 'product.price' ); ?></h3>
	<?php endif; ?>

	<?php if ( shopp( 'product.freeshipping' ) ) : ?>
		<p class="freeshipping"><?php _e( 'Free Shipping!', 'Shopp' ); ?></p>
	<?php endif; ?>

	<form action="<?php shopp( 'cart.url' ); ?>" method="post" class="shopp validate validation-alerts">
		<?php if ( shopp( 'product.has-variants' ) ) : ?>
			<ul class="variations">
				<?php shopp( 'product.variants', 'mode=multiple&label=true&defaults=' . __( 'Select an option', 'Shopp') . '&before_menu=<li>&after_menu=</li>' ); ?>
			</ul>
		<?php endif; ?>

		<?php if ( shopp( 'product.has-addons' ) ) : ?>
			<ul class="addons">
				<?php shopp( 'product.addons', 'mode=menu&label=true&defaults=' . __( 'Select an add-on', 'Shopp') . '&before_menu=<li>&after_menu=</li>' ); ?>
			</ul>
		<?php endif; ?>

		<p>
			<?php shopp( 'product.quantity', 'class=selectall&input=menu' ); ?>
			<?php shopp( 'product.addtocart' ); ?>
		</p>

	</form>

	<?php shopp( 'product.description' ); ?>

	<?php if ( shopp( 'product.has-specs' ) ) : ?>
		<dl class="details">
			<?php while ( shopp( 'product.specs' ) ) : ?>
				<dt><?php shopp( 'product.spec', 'name' ); ?>:</dt>
				<dd><?php shopp( 'product.spec', 'content' ); ?></dd>
			<?php endwhile; ?>
		</dl>
	<?php endif; ?>

<?php else : ?>
	<h3><?php _e( 'Product Not Found', 'Shopp' ); ?></h3>
	<p><?php _e( 'Sorry! The product you requested is not found in our catalog!', 'Shopp' ); ?></p>
<?php endif; ?>