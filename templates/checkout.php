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

<form action="<?php shopp( 'checkout.url' ); ?>" method="post" class="shopp validate" id="checkout">

	<?php shopp( 'checkout.cart-summary' ); ?>

	<?php if ( shopp( 'cart.hasitems' ) ) : ?>

		<?php shopp( 'checkout.function' ); ?>

		<ul>
			<?php if ( shopp( 'customer.notloggedin' ) ) : ?>
				<li>
					<label for="login"><?php Shopp::_e( 'Login to Your Account' ); ?></label>
					<span><label for="account-login"><?php Shopp::_e( 'Email' ); ?></label><?php shopp( 'customer.account-login', 'size=20&title=' . Shopp::__('Login') ); ?></span>
					<span><label for="password-login"><?php Shopp::_e( 'Password' ); ?></label><?php shopp( 'customer.password-login', 'size=20&title=' . Shopp::__('Password') ); ?></span>
					<span><?php shopp( 'customer.login-button', 'context=checkout&value=Login' ); ?></span>
				</li>
			<?php endif; ?>

			<li>
				<label for="firstname"><?php Shopp::_e( 'Contact Information' ); ?></label>
				<span><label for="firstname"><?php Shopp::_e( 'First' ); ?></label><?php shopp( 'checkout.firstname', 'required=true&minlength=2&size=8&title=' . Shopp::__( 'First Name' ) ); ?></span>
				<span><label for="lastname"><?php Shopp::_e( 'Last' ); ?></label><?php shopp( 'checkout.lastname', 'required=true&minlength=2&size=14&title=' . Shopp::__( 'Last Name' ) ); ?></span>
				<span><label for="company"><?php Shopp::_e( 'Company/Organization' ); ?></label><?php shopp( 'checkout.company', 'size=22&title=' . Shopp::__( 'Company/Organization' ) ); ?></span>
			</li>
			<li>
				<span><label for="phone"><?php Shopp::_e( 'Phone' ); ?></label><?php shopp( 'checkout.phone', 'format=phone&size=15&title=' . Shopp::__( 'Phone' ) ); ?></span>
				<span><label for="email"><?php Shopp::_e( 'Email' ); ?></label><?php shopp( 'checkout.email', 'required=true&format=email&size=30&title=' . Shopp::__( 'Email' ) ); ?></span>
			</li>

			<?php if ( shopp( 'customer.notloggedin' ) ) : ?>
				<li>
					<span><label for="password"><?php Shopp::_e( 'Password' ); ?></label>
					<?php shopp( 'checkout.password', 'required=true&format=passwords&size=16&title=' . Shopp::__( 'Password' ) ); ?></span>

					<span><label for="confirm-password"><?php Shopp::_e( 'Confirm Password' ); ?></label>
					<?php shopp( 'checkout.confirm-password', 'required=true&format=passwords&size=16&title=' . Shopp::__( 'Password Confirmation' ) ); ?></span>
				</li>
			<?php endif; ?>

			<?php if ( shopp( 'cart.needs-shipped' ) ) : ?>
				<li class="half" id="billing-address-fields">
			<?php else: ?>
				<li>
			<?php endif; ?>

					<label for="billing-address"><?php Shopp::_e( 'Billing Address' ); ?></label>
					<div>
						<label for="billing-name"><?php _e( 'Name' ); ?></label>
						<?php shopp( 'checkout.billing-name', 'required=false&title=' . Shopp::__( 'Bill to' ) ); ?>
					</div>
					<div>
						<label for="billing-address"><?php Shopp::_e( 'Street Address' ); ?></label>
						<?php shopp( 'checkout.billing-address', 'required=true&title=' . Shopp::__( 'Billing street address' ) ); ?>
					</div>
					<div>
						<label for="billing-xaddress"><?php Shopp::_e( 'Address Line 2' ); ?></label>
						<?php shopp( 'checkout.billing-xaddress', 'title=' . Shopp::__( 'Billing address line 2' ) ); ?>
					</div>
					<div class="left">
						<label for="billing-city"><?php Shopp::_e( 'City' ); ?></label>
						<?php shopp( 'checkout.billing-city', 'required=true&title=' . Shopp::__( 'City billing address' ) ); ?>
					</div>
					<div class="right">
						<label for="billing-state"><?php Shopp::_e( 'State / Province' ); ?></label>
						<?php shopp( 'checkout.billing-state', 'required=auto&title=' . Shopp::__( 'State/Province/Region billing address' ) ); ?>
					</div>
					<div class="left">
						<label for="billing-postcode"><?php Shopp::_e( 'Postal / Zip Code' ); ?></label>
						<?php shopp( 'checkout.billing-postcode', 'required=true&title=' . Shopp::__( 'Postal/Zip Code billing address' ) ); ?>
					</div>
					<div class="right">
						<label for="billing-country"><?php Shopp::_e( 'Country' ); ?></label>
						<?php shopp( 'checkout.billing-country', 'required=true&title=' . Shopp::__( 'Country billing address' ) ); ?>
					</div>
			<?php if ( shopp( 'cart.needs-shipped' ) ) : ?>
					<div class="inline">
						<?php shopp( 'checkout.same-shipping-address' ); ?>
					</div>
				</li>
				<li class="half right" id="shipping-address-fields">
					<label for="shipping-address"><?php Shopp::_e( 'Shipping Address' ); ?></label>
					<div>
						<label for="shipping-address"><?php _e( 'Name' ); ?></label>
						<?php shopp( 'checkout.shipping-name', 'required=false&title=' . Shopp::__( 'Ship to' ) ); ?>
					</div>
					<div>
						<label for="shipping-address"><?php Shopp::_e( 'Street Address' ); ?></label>
						<?php shopp( 'checkout.shipping-address', 'required=true&title=' . Shopp::__( 'Shipping street address' ) ); ?>
					</div>
					<div>
						<label for="shipping-xaddress"><?php Shopp::_e( 'Address Line 2' ); ?></label>
						<?php shopp( 'checkout.shipping-xaddress', 'title=' . Shopp::__( 'Shipping address line 2' ) ); ?>
					</div>
					<div class="left">
						<label for="shipping-city"><?php Shopp::_e( 'City' ); ?></label>
						<?php shopp( 'checkout.shipping-city', 'required=true&title=' . Shopp::__( 'City shipping address' ) ); ?>
					</div>
					<div class="right">
						<label for="shipping-state"><?php Shopp::_e( 'State / Province' ); ?></label>
						<?php shopp( 'checkout.shipping-state', 'required=auto&title=' . Shopp::__( 'State/Provice/Region shipping address' ) ); ?>
					</div>
					<div class="left">
						<label for="shipping-postcode"><?php Shopp::_e( 'Postal / Zip Code' ); ?></label>
						<?php shopp( 'checkout.shipping-postcode', 'required=true&title=' . Shopp::__( 'Postal/Zip Code shipping address' ) ); ?>
					</div>
					<div class="right">
						<label for="shipping-country"><?php Shopp::_e( 'Country' ); ?></label>
						<?php shopp( 'checkout.shipping-country', 'required=true&title=' . Shopp::__( 'Country shipping address' ) ); ?>
					</div>
				</li>
			<?php else: ?>
				</li>
			<?php endif; ?>

			<?php if ( shopp( 'checkout.billing-localities' ) ) : ?>
				<li class="half locale hidden">
					<div>
						<label for="billing-locale"><?php Shopp::_e( 'Local Jurisdiction' ); ?></label>
						<?php shopp( 'checkout.billing-locale' ); ?>
					</div>
				</li>
			<?php endif; ?>

			<li>
				<?php shopp( 'checkout.payment-options' ); ?>
				<?php shopp( 'checkout.gateway-inputs' ); ?>
			</li>

			<?php if ( shopp( 'checkout.card-required' ) ) : ?>
				<li class="payment">
					<label for="billing-card"><?php Shopp::_e( 'Payment Information' ); ?></label>
					<span>
						<label for="billing-card"><?php Shopp::_e( 'Credit/Debit Card Number' ); ?></label>
						<?php shopp( 'checkout.billing-card', 'required=true&size=30&title=' . Shopp::__( 'Credit/Debit Card Number' ) ); ?>
					</span>
					<span>
						<label for="billing-cvv"><?php Shopp::_e( 'Security ID' ); ?></label>
						<?php shopp( 'checkout.billing-cvv', 'size=7&minlength=3&maxlength=4&title=' . Shopp::__( 'Card\'s security code (3-4 digits on the back of the card)' ) ); ?>
					</span>
				</li>
				<li class="payment">
					<span>
						<label for="billing-cardexpires-mm"><?php Shopp::_e('MM'); ?></label>
						<?php shopp( 'checkout.billing-cardexpires-mm', 'required=true&minlength=2&maxlength=2&title=' . Shopp::__( 'Card\'s 2-digit expiration month' ) ); ?> /
					</span>
					<span>
						<label for="billing-cardexpires-yy"><?php Shopp::_e( 'YY' ); ?></label>
						<?php shopp( 'checkout.billing-cardexpires-yy', 'required=true&minlength=2&maxlength=2&title=' . Shopp::__( 'Card\'s 2-digit expiration year' ) ); ?>
					</span>
					<span>
						<label for="billing-cardtype"><?php Shopp::_e( 'Card Type' ); ?></label>
						<?php shopp( 'checkout.billing-cardtype', 'required=true&title=' . Shopp::__( 'Card Type' ) ); ?>
					</span>
				</li>
				<?php if ( shopp( 'checkout.billing-xcsc-required' ) ) : // Extra billing security fields ?>
					<li class="payment">
						<span>
							<label for="billing-xcsc-start"><?php Shopp::_e( 'Start Date' ); ?></label>
							<?php shopp( 'checkout.billing-xcsc', 'input=start&size=7&minlength=5&maxlength=5&title=' . Shopp::__( 'Card\'s start date (MM/YY)' ) ); ?>
						</span>
						<span>
							<label for="billing-xcsc-issue"><?php Shopp::_e( 'Issue #' ); ?></label>
							<?php shopp( 'checkout.billing-xcsc', 'input=issue&size=7&minlength=3&maxlength=4&title=' . Shopp::__( 'Card\'s issue number' ) ); ?>
						</span>
					</li>
				<?php endif; ?>

			<?php endif; ?>

			<li>
				<div class="inline">
					<label for="marketing"><?php shopp('checkout.marketing'); ?> <?php Shopp::_e( 'Yes, I would like to receive e-mail updates and special offers!' ); ?></label>
				</div>
			</li>
		</ul>
		<p class="submit"><?php shopp( 'checkout.submit', 'value=' . Shopp::__( 'Submit Order' ) ); ?></p>

	<?php endif; ?>
</form>
