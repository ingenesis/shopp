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
		<p class="success"><?php Shopp::_e( 'Your password has been changed successfully.' ); ?></p>
	<?php endif; ?>
	
	<?php if ( shopp( 'customer.profile-saved' ) && shopp( 'customer.password-change-fail' ) ) : ?>
		<p class="success"><?php Shopp::_e( 'Your account has been updated.' ); ?></p>
	<?php endif; ?>
	
	<p>
		<a href="<?php shopp( 'customer.url' ); ?>">&laquo; <?php Shopp::_e( 'Return to Account Management' ); ?></a>
	</p>
	
	<ul>
		<li>
			<label for="firstname"><?php Shopp::_e( 'Your Account' ); ?></label>
			<span>
				<?php shopp( 'customer.firstname', 'required=true&minlength=2&size=8&title=' . Shopp::__( 'First Name' ) ); ?>
				<label for="firstname"><?php Shopp::_e( 'First' ); ?></label>
			</span>
			<span>
				<?php shopp( 'customer.lastname', 'required=true&minlength=3&size=14&title=' . Shopp::__( 'Last Name' ) ); ?>
				<label for="lastname"><?php Shopp::_e('Last'); ?></label>
			</span>
		</li>
		<li>
			<span>
				<?php shopp( 'customer.company', 'size=20&title=' . Shopp::__( 'Company' ) ); ?>
				<label for="company"><?php Shopp::_e( 'Company' ); ?></label>
			</span>
		</li>
		<li>
			<span>
				<?php shopp( 'customer.phone', 'format=phone&size=15&title=' . Shopp::__( 'Phone' ) ); ?>
				<label for="phone"><?php Shopp::_e( 'Phone' ); ?></label>
			</span>
		</li>
		<li>
			<span>
				<?php shopp( 'customer.email', 'required=true&format=email&size=30&title=' . Shopp::__( 'Email' ) ); ?>
				<label for="email"><?php Shopp::_e( 'Email' ); ?></label>
			</span>
		</li>
		<li>
			<div class="inline">
				<label for="marketing"><?php shopp( 'customer.marketing', 'title=' . Shopp::__( 'I would like to continue receiving e-mail updates and special offers!' ) ); ?> <?php Shopp::_e('I would like to continue receiving e-mail updates and special offers!'); ?></label>
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
			<label for="password"><?php Shopp::_e( 'Change Your Password' ); ?></label>
			<span>
				<?php shopp( 'customer.password', 'size=14&title=' . Shopp::__( 'New Password' ) ); ?>
				<label for="password"><?php Shopp::_e( 'New Password' ); ?></label>
			</span>
			<span>
				<?php shopp( 'customer.confirm-password', '&size=14&title=' . Shopp::__( 'Confirm Password' ) ); ?>
				<label for="confirm-password"><?php Shopp::_e('Confirm Password'); ?></label>
			</span>
		</li>
		<li id="billing-address-fields">
			<label for="billing-address"><?php Shopp::_e( 'Billing Address' ); ?></label>
			<div>
				<?php shopp( 'customer.billing-address', 'title=' . Shopp::__( 'Billing street address' ) ); ?>
				<label for="billing-address"><?php Shopp::_e( 'Street Address' ); ?></label>
			</div>
			<div>
				<?php shopp( 'customer.billing-xaddress', 'title=' . Shopp::__( 'Billing address line 2' ) ); ?>
				<label for="billing-xaddress"><?php Shopp::_e( 'Address Line 2' ); ?></label>
			</div>
			<div class="left">
				<?php shopp( 'customer.billing-city', 'title=' . Shopp::__( 'City billing address' ) ); ?>
				<label for="billing-city"><?php Shopp::_e( 'City' ); ?></label>
			</div>
			<div class="right">
				<?php shopp( 'customer.billing-state', 'title=' . Shopp::__( 'State/Province/Region billing address' ) ); ?>
				<label for="billing-state"><?php Shopp::_e( 'State / Province' ); ?></label>
			</div>
			<div class="left">
				<?php shopp( 'customer.billing-postcode', 'title=' . Shopp::__( 'Postal/Zip Code billing address' ) ); ?>
				<label for="billing-postcode"><?php Shopp::_e( 'Postal / Zip Code' ); ?></label>
			</div>
			<div class="right">
				<?php shopp( 'customer.billing-country', 'title=' . Shopp::__( 'Country billing address' ) ); ?>
				<label for="billing-country"><?php Shopp::_e( 'Country' ); ?></label>
			</div>
		</li>
		<li id="shipping-address-fields">
			<label for="shipping-address"><?php Shopp::_e( 'Shipping Address' ); ?></label>
			<div>
				<?php shopp( 'customer.shipping-address', 'title=' . Shopp::__( 'Shipping street address' ) ); ?>
				<label for="shipping-address"><?php Shopp::_e( 'Street Address' ); ?></label>
			</div>
			<div>
				<?php shopp( 'customer.shipping-xaddress', 'title=' . Shopp::__( 'Shipping address line 2' ) ); ?>
				<label for="shipping-xaddress"><?php Shopp::_e('Address Line 2'); ?></label>
			</div>
			<div class="left">
				<?php shopp( 'customer.shipping-city', 'title=' . Shopp::__( 'City shipping address' ) ); ?>
				<label for="shipping-city"><?php Shopp::_e( 'City' ); ?></label>
			</div>
			<div class="right">
				<?php shopp( 'customer.shipping-state', 'title=' . Shopp::__( 'State/Provice/Region shipping address' ) ); ?>
				<label for="shipping-state"><?php Shopp::_e( 'State / Province' ); ?></label>
			</div>
			<div class="left">
				<?php shopp( 'customer.shipping-postcode', 'title=' . Shopp::__( 'Postal/Zip Code shipping address' ) ); ?>
				<label for="shipping-postcode"><?php Shopp::_e( 'Postal / Zip Code' ); ?></label>
			</div>
			<div class="right">
				<?php shopp( 'customer.shipping-country', 'title=' . Shopp::__( 'Country shipping address' ) ); ?>
				<label for="shipping-country"><?php Shopp::_e( 'Country' ); ?></label>
			</div>
		</li>
	</ul>
	
	<p><?php shopp( 'customer.save-button', 'label=' . Shopp::__( 'Save' ) ); ?></p>
	
	<p><a href="<?php shopp( 'customer.url' ); ?>">&laquo; <?php Shopp::_e( 'Return to Account Management' ); ?></a></p>
	
</form>