<div itemscope itemtype="http://schema.org/Product">
	
	<meta itemprop="name" content="<?php echo str_replace('"', '&quot;', shopp('product.get-name')); ?>" />
	<meta itemprop="description" content="<?php echo htmlspecialchars(strip_tags(shopp('product.get-summary'))); ?>" />
	<meta itemprop="image" content="<?php shopp('product.coverimage', 'property=url&size=original'); ?>" />
	<?php if ( shopp('product.has-variations') ): ?>
		<?php while( shopp('product.variations') ): ?>
			<div itemprop="model" itemscope itemtype="http://schema.org/ProductModel">
				<meta itemprop="name" content="<?php echo str_replace('"', '&quot;', shopp('product.get-variation', 'label')); ?>" />
				<meta itemprop="sku" content="<?php echo htmlspecialchars(shopp('product.get-variation', 'sku')); ?>" />
				<div itemscope itemprop="offers" itemtype="http://schema.org/Offer">
					<?php while( shopp('product','categories') ): ?>
						<meta itemprop="category" content="<?php echo str_replace('"', '&quot;', shopp('product','get-category')); ?>" />
					<?php endwhile; ?>
					<meta itemprop="price" content="<?php shopp('product.variation', 'saleprice'); ?>" />
					<meta itemprop="priceCurrency" content="<?php shopp('storefront.currency'); ?>" />
				</div>
			</div>
		<?php endwhile; ?>
	<?php else: ?>
		
		<meta itemprop="sku" content="<?php echo htmlspecialchars(shopp('product.get-sku')); ?>" />

		<div itemprop="offers" itemscope itemtype="http://schema.org/Offer">
			<?php while( shopp('product','categories') ): ?>
				<meta itemprop="category" content="<?php echo str_replace('"', '&quot;', shopp('product','get-category')); ?>" />
			<?php endwhile; ?>
			<meta itemprop="price" content="<?php shopp('product.saleprice'); ?>" />
			<meta itemprop="priceCurrency" content="<?php shopp('storefront.currency'); ?>" />
			<?php if ( shopp('product.get-outofstock') ): ?>
				<link itemprop="availability" href="http://schema.org/OutOfStock" />
			<?php else: ?>
				<link itemprop="availability" href="http://schema.org/InStock" />
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( shopp('product.has-specs') ): ?>
		<?php while( shopp('product.specs') ): ?>
			<?php if ( 'Brand' == shopp('product.get-spec', 'name') ): ?>
			    <div itemprop="brand" itemscope itemtype="http://schema.org/Organization">
					<meta itemprop="name" content="<?php shopp('product.spec'); ?>" />
				</div>
			<?php endif; ?>

			<?php if ( in_array(shopp('product.get-spec', 'name'), array('ISBN-10', 'ISBN')) ): ?>
				<meta itemprop="productID" content="isbn:<?php shopp('product.spec'); ?>" />
			<?php endif; ?>

			<?php if ( in_array(shopp('product.get-spec', 'name'), array('UPC', 'EAN', 'ISBN-13')) ): ?>
				<meta itemprop="gtin13" content="<?php shopp('product.spec'); ?>" />
			<?php endif; ?>

			<?php if ( 'GTIN14' == shopp('product.get-spec', 'name') ): ?>
				<meta itemprop="gtin14" content="<?php shopp('product.spec'); ?>" />
			<?php endif; ?>

			<?php if ( 'MPN' == shopp('product.get-spec', 'name') ): ?>
				<meta itemprop="mpn" content="<?php shopp('product.spec'); ?>" />
			<?php endif; ?>

		<?php endwhile; ?>
	<?php endif; ?>
</div>