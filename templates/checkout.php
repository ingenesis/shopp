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
					<label for="login"><?php _e('Login to Your Account','Shopp'); ?></label>
					<span><label for="account-login"><?php _e('Email','Shopp'); ?></label><?php shopp('customer','account-login','size=20&title='.__('Login','Shopp')); ?></span>
					<span><label for="password-login"><?php _e('Password','Shopp'); ?></label><?php shopp('customer','password-login','size=20&title='.__('Password','Shopp')); ?></span>
					<span><?php shopp('customer','login-button','context=checkout&value=Login'); ?></span>
				</li>
			<?php endif; ?>
			
			<li>
				<label for="firstname"><?php _e('Contact Information','Shopp'); ?></label>
				<span><label for="firstname"><?php _e('First','Shopp'); ?></label><?php shopp('checkout','firstname','required=true&minlength=2&size=8&title='.__('First Name','Shopp')); ?></span>
				<span><label for="lastname"><?php _e('Last','Shopp'); ?></label><?php shopp('checkout','lastname','required=true&minlength=3&size=14&title='.__('Last Name','Shopp')); ?></span>
				<span><label for="company"><?php _e('Company/Organization','Shopp'); ?></label><?php shopp('checkout','company','size=22&title='.__('Company/Organization','Shopp')); ?></span>
			</li>
			<li>
				<span><label for="phone"><?php _e('Phone','Shopp'); ?></label><?php shopp('checkout','phone','format=phone&size=15&title='.__('Phone','Shopp')); ?></span>
				<span><label for="email"><?php _e('Email','Shopp'); ?></label><?php shopp('checkout','email','required=true&format=email&size=30&title='.__('Email','Shopp')); ?></span>
			</li>
			
			<?php if ( shopp( 'customer.notloggedin' ) ) : ?>
				<li>
					<span><label for="password"><?php _e('Password','Shopp'); ?></label>
					<?php shopp('checkout','password','required=true&format=passwords&size=16&title='.__('Password','Shopp')); ?></span>

					<span><label for="confirm-password"><?php _e('Confirm Password','Shopp'); ?></label>
					<?php shopp('checkout','confirm-password','required=true&format=passwords&size=16&title='.__('Password Confirmation','Shopp')); ?></span>
				</li>
			<?php endif; ?>
			
			<?php if ( shopp( 'cart.needs-shipped' ) ) : ?>
				<li class="half" id="billing-address-fields">
			<?php else: ?>
				<li>
			<?php endif; ?>
					
					<label for="billing-address"><?php _e( 'Billing Address', 'Shopp' ); ?></label>
					<div>
						<label for="billing-name"><?php _e( 'Name', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-name', 'required=false&title=' . __( 'Bill to', 'Shopp' ) ); ?>
					</div>
					<div>
						<label for="billing-address"><?php _e( 'Street Address', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-address', 'required=true&title=' . __( 'Billing street address', 'Shopp' ) ); ?>
					</div>
					<div>
						<label for="billing-xaddress"><?php _e( 'Address Line 2', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-xaddress', 'title=' . __( 'Billing address line 2', 'Shopp' ) ); ?>
					</div>
					<div class="left">
						<label for="billing-city"><?php _e( 'City', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-city', 'required=true&title=' . __( 'City billing address', 'Shopp' ) ); ?>
					</div>
					<div class="right">
						<label for="billing-state"><?php _e( 'State / Province', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-state', 'required=true&title=' . __( 'State/Provice/Region billing address', 'Shopp' ) ); ?>
					</div>
					<div class="left">
						<label for="billing-postcode"><?php _e( 'Postal / Zip Code', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-postcode', 'required=true&title=' . __( 'Postal/Zip Code billing address', 'Shopp' ) ); ?>
					</div>
					<div class="right">
						<label for="billing-country"><?php _e( 'Country', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-country', 'required=true&title=' . __( 'Country billing address', 'Shopp' ) ); ?>
					</div>
			<?php if ( shopp( 'cart.needs-shipped' ) ) : ?>
					<div class="inline">
						<?php shopp( 'checkout.same-shipping-address' ); ?>
					</div>
				</li>
				<li class="half right" id="shipping-address-fields">
					<label for="shipping-address"><?php _e( 'Shipping Address', 'Shopp' ); ?></label>
					<div>
						<label for="shipping-address"><?php _e( 'Name', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.shipping-name', 'required=false&title=' . __( 'Ship to', 'Shopp' ) ); ?>
					</div>
					<div>
						<label for="shipping-address"><?php _e( 'Street Address', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.shipping-address', 'required=true&title=' . __( 'Shipping street address', 'Shopp' ) ); ?>
					</div>
					<div>
						<label for="shipping-xaddress"><?php _e( 'Address Line 2', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.shipping-xaddress', 'title=' . __( 'Shipping address line 2', 'Shopp' ) ); ?>
					</div>
					<div class="left">
						<label for="shipping-city"><?php _e( 'City', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.shipping-city', 'required=true&title=' . __( 'City shipping address', 'Shopp' ) ); ?>
					</div>
					<div class="right">
						<label for="shipping-state"><?php _e( 'State / Province', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.shipping-state', 'required=true&title=' . __( 'State/Provice/Region shipping address', 'Shopp' ) ); ?>
					</div>
					<div class="left">
						<label for="shipping-postcode"><?php _e( 'Postal / Zip Code', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.shipping-postcode', 'required=true&title=' . __( 'Postal/Zip Code shipping address', 'Shopp' ) ); ?>
					</div>
					<div class="right">
						<label for="shipping-country"><?php _e( 'Country', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.shipping-country', 'required=true&title=' . __( 'Country shipping address', 'Shopp' ) ); ?>
					</div>
				</li>
			<?php else: ?>
				</li>
			<?php endif; ?>
			
			<?php if ( shopp( 'checkout.billing-localities' ) ) : ?>
				<li class="half locale hidden">
					<div>
						<label for="billing-locale"><?php _e( 'Local Jurisdiction', 'Shopp' ); ?></label>
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
					<label for="billing-card"><?php _e( 'Payment Information', 'Shopp' ); ?></label>
					<span>
						<label for="billing-card"><?php _e( 'Credit/Debit Card Number', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-card', 'required=true&size=30&title=' . __( 'Credit/Debit Card Number', 'Shopp' ) ); ?>
					</span>
					<span>
						<label for="billing-cardexpires-mm"><?php _e('MM','Shopp'); ?></label>
						<?php shopp( 'checkout.billing-cardexpires-mm', 'size=4&required=true&minlength=2&maxlength=2&title=' . __( 'Card\'s 2-digit expiration month', 'Shopp' ) ); ?> /
					</span>
					<span>
						<label for="billing-cardexpires-yy"><?php _e( 'YY', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-cardexpires-yy', 'size=4&required=true&minlength=2&maxlength=2&title=' . __( 'Card\'s 2-digit expiration year', 'Shopp' ) ); ?>
					</span>
					<span>
						<label for="billing-cardtype"><?php _e( 'Card Type', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-cardtype', 'required=true&title=' . __( 'Card Type', 'Shopp' ) ); ?>
					</span>
				</li>
				<li class="payment">
					<span>
						<label for="billing-cardholder"><?php _e( 'Name on Card', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-cardholder', 'required=true&size=30&title=' . __( 'Card Holder\'s Name', 'Shopp' ) ); ?>
					</span>
					<span>
						<label for="billing-cvv"><?php _e( 'Security ID', 'Shopp' ); ?></label>
						<?php shopp( 'checkout.billing-cvv', 'size=7&minlength=3&maxlength=4&title=' . __( 'Card\'s security code (3-4 digits on the back of the card)', 'Shopp' ) ); ?>
					</span>
				</li>
				<?php if ( shopp( 'checkout.billing-xcsc-required' ) ) : // Extra billing security fields ?>
					<li class="payment">
						<span>
							<label for="billing-xcsc-start"><?php _e( 'Start Date', 'Shopp' ); ?></label>
							<?php shopp( 'checkout.billing-xcsc', 'input=start&size=7&minlength=5&maxlength=5&title=' . __( 'Card\'s start date (MM/YY)', 'Shopp' ) ); ?>
						</span>
						<span>
							<label for="billing-xcsc-issue"><?php _e( 'Issue #', 'Shopp' ); ?></label>
							<?php shopp( 'checkout.billing-xcsc', 'input=issue&size=7&minlength=3&maxlength=4&title=' . __( 'Card\'s issue number', 'Shopp' ) ); ?>
						</span>
					</li>
				<?php endif; ?>
			<?php endif; ?>
			
			<li>
				<div class="inline">
					<label for="marketing"><?php shopp('checkout','marketing'); ?> <?php _e( 'Yes, I would like to receive e-mail updates and special offers!', 'Shopp' ); ?></label>
				</div>
			</li>
		</ul>
		<p class="submit"><?php shopp( 'checkout.submit', 'value=' . __( 'Submit Order', 'Shopp' ) ); ?></p>
		
	<?php endif; ?>
</form>
