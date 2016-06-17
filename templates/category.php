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

<?php shopp('collection.description') ?>

<?php if ( shopp( 'collection.hasproducts', 'load=coverimages' ) ) : ?>
	<div class="category">
		<section class="navigation controls">
			<?php shopp( 'storefront.breadcrumb', array( 'separator' => '&nbsp;/ ' ) ); ?>
			<?php shopp( 'collection.subcategory-list',
					array(	'dropdown' => true,
						 	'hierarchy' => true,
						 	'showall' => true,
						 	'class' => 'subcategories',
						 	'before' => '&nbsp;/ '	)
			); ?>

			<div class="alignright">
				<?php shopp( 'storefront.orderby-list', 'dropdown=on' ); ?>
			</div>
		</section>

		<section class="view controls">
			<?php shopp( 'storefront.views', 'label=' . __( 'Views: ', 'Shopp' ) ); ?>
			<?php shopp( 'collection.pagination', 'show=10&before=<div class="alignright">' ); ?>
		</section>

		<ul class="products">
			<?php while( shopp( 'collection.products' ) ) : ?>
				<li class="product<?php if ( shopp('collection.row') ) echo ' first'; ?>" itemscope itemtype="http://schema.org/Product">
				<div class="frame">
					<a href="<?php shopp( 'product.url' ); ?>" itemprop="url"><?php shopp( 'product.coverimage', 'setting=thumbnails&itemprop=image' ); ?></a>
					<div class="details">
						<h4 class="name">
							<a href="<?php shopp( 'product.url' ); ?>"><span itemprop="name"><?php shopp( 'product.name' ); ?></span></a>
						</h4>
						<p class="price" itemscope itemtype="http://schema.org/Offer"><span itemprop="price"><?php shopp( 'product.saleprice', 'starting=' . __( 'from', 'Shopp' ) ); ?></span></p>
						<?php if ( shopp( 'product.has-savings' ) ) : ?>
							<p class="savings"><?php _e( 'Save ', 'Shopp' ); ?><?php shopp( 'product.savings', 'show=percent' ); ?></p>
						<?php endif; ?>

						<div class="listview">
							<p><span itemprop="description"><?php shopp( 'product.summary' ); ?></span></p>
							<form action="<?php shopp( 'cart.url' ); ?>" method="post" class="shopp product">
								<?php shopp( 'product.addtocart' ); ?>
							</form>
						</div>
					</div>
				</div>
			</li>
			<?php endwhile; ?>
		</ul>

		<div class="alignright">
			<?php shopp( 'collection.pagination', 'show=10' ); ?>
		</div>
	</div>

<?php else : ?>
	<?php if ( ! shopp('storefront.is-landing') ) shopp( 'storefront.breadcrumb' ); ?>
	<p class="notice"><?php _e( 'No products were found.', 'Shopp' ); ?></p>
<?php endif; ?>
