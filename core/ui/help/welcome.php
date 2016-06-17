<div class="wrap about-wrap">

<?php $this->heading(); ?>

<div class="changelog">
	<h3><?php Shopp::_e( 'Reports' ); ?></h3>

	<div class="feature-section col two-col">
		<div>
			<h4><?php Shopp::_e( 'Keep Score' ); ?></h4>
			<p><?php Shopp::_e( 'Track your sales over time with the all new report system. Gorgeous charts make it easy to visualize performance for better decision making. Exports allow you to take your data and remix it in your own system to uncover hidden opportunities.' ); ?></p>
		</div>
		<div class="last-feature">
			<h4><?php Shopp::_e( 'Global Scale' ); ?></h4>
			<p><?php Shopp::_e( 'The new Locations report shows sales performance across global markets in an easy to digest interactive heat map. Find out where business is on fire, and where things are cooling off so you can adjust marketing strategies.' ); ?></p>
		</div>
	</div>
</div>

<div class="changelog">
	<h3><?php Shopp::_e( 'Simpler Admin Menus' ); ?></h3>

	<div class="feature-section col two-col">
		<div>
			<h4><?php Shopp::_e( 'System & Setup' ); ?></h4>
			<p><?php Shopp::_e( 'The menus have been simplified to organize features across Shopp into System integrations and Setup configurations.' ); ?></p>
		</div>
		<div class="last-feature">
			<h4><?php Shopp::_e( 'Discounts' ); ?></h4>
			<p><?php Shopp::_e( 'The Promotions system has been relabeled as simply &quot;Discounts&quot; for better clarity.' ); ?></p>
		</div>
	</div>
</div>

<div class="changelog">
	<h3><?php Shopp::_e( 'Retina Ready' ); ?></h3>

	<div class="feature-section images-stagger-right">
		<h4><?php Shopp::_e( 'So Sharp You Can&#8217;t See the Pixels' ); ?></h4>
		<p><?php Shopp::_e( 'Following in the footsteps of WordPress, the Shopp admin and storefront features have been painstakingly polished to look beautiful on high-resolution screens like those found on the iPad, Kindle Fire HD, Nexus 10, and MacBook Pro with Retina Display. Icons and other visual elements are crystal clear.' ); ?></p>
	</div>
</div>


<div class="changelog">
	<h3><?php Shopp::_e( 'Customer Editing in Orders' ); ?></h3>

	<div class="feature-section images-stagger-right">
		<h4><?php Shopp::_e( 'Improved Order Management' ); ?></h4>
		<p><?php Shopp::_e( 'More order information is editable with the ability to update billing and shipping address. You can even edit customer details or assign an order to a different customer altogether.' ); ?></p>
	</div>
</div>
<div class="changelog">
	<h3><?php Shopp::_e( 'Under the Hood' ); ?></h3>

	<div class="feature-section col three-col">
		<div>
			<h4><?php Shopp::_e( 'Shopping Cold Storage' ); ?></h4>
			<p><?php Shopp::_e( 'Shopping sessions no longer expire in a couple hours but are instead put into cold storage so that if a shopper returns, they can pick up where they left off.' ); ?></p>
		</div>
		<div>
			<h4><?php Shopp::_e( 'Smart Loading' ); ?></h4>
			<p><?php Shopp::_e( 'Shopp implements the PHP autoload features to vastly reduce the amount of memory used.' ); ?></p>
		</div>
		<div class="last-feature">
			<h4><?php Shopp::_e( 'Order Totals' ); ?></h4>
			<p><?php Shopp::_e( 'Introducing an all new order total calculator to more accurately tally totals and allow for custom fees to be registered and calculated.' ); ?></p>
		</div>
	</div>

	<div class="feature-section col three-col">
		<div>
			<h4><?php Shopp::_e( 'Compound Taxes' ); ?></h4>
			<p><?php Shopp::_e( 'Taxes can be layered and compounded for tax jursidictions that require it.' ); ?></p>
		</div>
		<div>
			<h4><?php Shopp::_e( 'schema.org' ); ?></h4>
			<p><?php printf( __( 'Products and categories now support customizable schema.org microdata markup to improve search engine understanding of your web store.' ) ); ?></p>
		</div>
		<div class="last-feature">
			<h4><?php Shopp::_e( 'API Improvements' ); ?></h4>
			<p><?php Shopp::_e( 'New improvements to the API make it easier than ever to extend the Shopp admin screens, taxes, and discounts.' ); ?></p>
		</div>
	</div>
</div>

<div class="return-to-dashboard">
	<?php if ( current_user_can( 'shopp_settings' ) ) : ?>
	<a href="<?php echo esc_url( add_query_arg('page', 'shopp-setup', admin_url( 'admin.php' )) ); ?>"><?php Shopp::_e('Continue to Shopp Setup'); ?></a> |
	<?php endif; ?>
	<a href="<?php echo esc_url( self_admin_url() ); ?>"><?php
		is_blog_admin() ? Shopp::_e( 'Go to Dashboard &rarr; Home' ) : Shopp::_e( 'Go to Dashboard' ); ?></a>
</div>

</div>
