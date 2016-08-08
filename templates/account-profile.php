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

<form action="<?php shopp( 'customer.action' ); ?>" method="post" class="shopp validate" autocomplete="off">
	
	<?php if ( shopp( 'customer.password-changed' ) ) : ?>
		<p class="success"><?php _e( 'Your password has been changed successfully.', 'Shopp' ); ?></p>
	<?php endif; ?>
	
	<?php if ( shopp( 'customer.profile-saved' ) && shopp( 'customer.password-change-fail' ) ) : ?>
		<p class="success"><?php _e( 'Your account has been updated.', 'Shopp' ); ?></p>
	<?php endif; ?>
	
	<p>
		<a href="<?php shopp( 'customer.url' ); ?>">&laquo; <?php _e( 'Return to Account Management', 'Shopp' ); ?></a>
	</p>
	
	<ul>
		<li>
			<label for="firstname"><?php _e( 'Your Account', 'Shopp' ); ?></label>
			<span>
				<label for="firstname"><?php _e( 'First', 'Shopp' ); ?></label>
				<?php shopp( 'customer.firstname', 'required=true&minlength=2&size=8&title=' . __( 'First Name', 'Shopp' ) ); ?>
			</span>
			<span>
				<label for="lastname"><?php _e('Last','Shopp'); ?></label>
				<?php shopp( 'customer.lastname', 'required=true&minlength=3&size=14&title=' . __( 'Last Name', 'Shopp' ) ); ?>
			</span>
		</li>
		<li>
			<span>
				<label for="company"><?php _e( 'Company', 'Shopp' ); ?></label>
				<?php shopp( 'customer.company', 'size=20&title=' . __( 'Company', 'Shopp' ) ); ?>
			</span>
		</li>
		<li>
			<span>
				<label for="phone"><?php _e( 'Phone', 'Shopp' ); ?></label>
				<?php shopp( 'customer.phone', 'format=phone&size=15&title=' . __( 'Phone', 'Shopp' ) ); ?>
			</span>
		</li>
		<li>
			<span>
				<label for="email"><?php _e( 'Email', 'Shopp' ); ?></label>
				<?php shopp( 'customer.email', 'required=true&format=email&size=30&title=' . __( 'Email', 'Shopp' ) ); ?>
			</span>
		</li>
		<li>
			<div class="inline">
				<label for="marketing"><?php shopp( 'customer.marketing', 'title=' . __( 'I would like to continue receiving e-mail updates and special offers!', 'Shopp' ) ); ?> <?php _e('I would like to continue receiving e-mail updates and special offers!','Shopp'); ?></label>
			</div>
		</li>
		<?php while ( shopp( 'customer.hasinfo' ) ) : ?>
			<li>
				<span>
					<?php shopp( 'customer.info' ); ?>
					<label><?php shopp( 'customer.info', 'mode=name' ); ?></label>
				</span>
			</li>
		<?php endwhile; ?>
		<li>
			<label for="password"><?php _e( 'Change Your Password', 'Shopp' ); ?></label>
			<span>
				<label for="password"><?php _e( 'New Password', 'Shopp' ); ?></label>
				<?php shopp( 'customer.password', 'size=14&title=' . __( 'New Password', 'Shopp' ) ); ?>
			</span>
			<span>
				<label for="confirm-password"><?php _e('Confirm Password','Shopp'); ?></label>
				<?php shopp( 'customer.confirm-password', '&size=14&title=' . __( 'Confirm Password', 'Shopp' ) ); ?>
			</span>
		</li>
		<li id="billing-address-fields">
			<label for="billing-address"><?php _e( 'Billing Address', 'Shopp' ); ?></label>
			<div>
				<label for="billing-address"><?php _e( 'Street Address', 'Shopp' ); ?></label>
				<?php shopp( 'customer.billing-address', 'title=' . __( 'Billing street address', 'Shopp' ) ); ?>
			</div>
			<div>
				<label for="billing-xaddress"><?php _e( 'Address Line 2', 'Shopp' ); ?></label>
				<?php shopp( 'customer.billing-xaddress', 'title=' . __( 'Billing address line 2', 'Shopp' ) ); ?>
			</div>
			<div class="left">
				<label for="billing-city"><?php _e( 'City', 'Shopp' ); ?></label>
				<?php shopp( 'customer.billing-city', 'title=' . __( 'City billing address', 'Shopp' ) ); ?>
			</div>
			<div class="right">
				<label for="billing-state"><?php _e( 'State / Province', 'Shopp' ); ?></label>
				<?php shopp( 'customer.billing-state', 'title=' . __( 'State/Province/Region billing address', 'Shopp' ) ); ?>
			</div>
			<div class="left">
				<label for="billing-postcode"><?php _e( 'Postal / Zip Code', 'Shopp' ); ?></label>
				<?php shopp( 'customer.billing-postcode', 'title=' . __( 'Postal/Zip Code billing address', 'Shopp' ) ); ?>
			</div>
			<div class="right">
				<label for="billing-country"><?php _e( 'Country', 'Shopp' ); ?></label>
				<?php shopp( 'customer.billing-country', 'title=' . __( 'Country billing address', 'Shopp' ) ); ?>
			</div>
		</li>
		<li id="shipping-address-fields">
			<label for="shipping-address"><?php _e( 'Shipping Address', 'Shopp' ); ?></label>
			<div>
				<label for="shipping-address"><?php _e( 'Street Address', 'Shopp' ); ?></label>
				<?php shopp( 'customer.shipping-address', 'title=' . __( 'Shipping street address', 'Shopp' ) ); ?>
			</div>
			<div>
				<label for="shipping-xaddress"><?php _e('Address Line 2','Shopp'); ?></label>
				<?php shopp( 'customer.shipping-xaddress', 'title=' . __( 'Shipping address line 2', 'Shopp' ) ); ?>
			</div>
			<div class="left">
				<label for="shipping-city"><?php _e( 'City', 'Shopp' ); ?></label>
				<?php shopp( 'customer.shipping-city', 'title=' . __( 'City shipping address', 'Shopp' ) ); ?>
			</div>
			<div class="right">
				<label for="shipping-state"><?php _e( 'State / Province', 'Shopp' ); ?></label>
				<?php shopp( 'customer.shipping-state', 'title=' . __( 'State/Province/Region shipping address', 'Shopp' ) ); ?>
			</div>
			<div class="left">
				<label for="shipping-postcode"><?php _e( 'Postal / Zip Code', 'Shopp' ); ?></label>
				<?php shopp( 'customer.shipping-postcode', 'title=' . __( 'Postal/Zip Code shipping address', 'Shopp' ) ); ?>
			</div>
			<div class="right">
				<label for="shipping-country"><?php _e( 'Country', 'Shopp' ); ?></label>
				<?php shopp( 'customer.shipping-country', 'title=' . __( 'Country shipping address', 'Shopp' ) ); ?>
			</div>
		</li>
	</ul>
	
	<p><?php shopp( 'customer.save-button', 'label=' . __( 'Save', 'Shopp' ) ); ?></p>
	
	<p><a href="<?php shopp( 'customer.url' ); ?>">&laquo; <?php _e( 'Return to Account Management', 'Shopp' ); ?></a></p>
	
</form>