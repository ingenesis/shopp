<?php
function save_meta_box ($Customer) {
?>
<div id="misc-publishing-actions">
<?php if ($Customer->id > 0): ?>
<p><strong><a href="<?php echo esc_url(add_query_arg(array('page'=>'shopp-orders','customer'=>$Customer->id),admin_url('admin.php'))); ?>"><?php _e('Orders','Shopp'); ?></a>: </strong><?php echo $Customer->orders; ?> &mdash; <strong><?php echo Shopp::money($Customer->total); ?></strong></p>
<p><strong><a href="<?php echo esc_url( add_query_arg(array('page'=>'shopp-customers','range'=>'custom','start'=>date('n/j/Y',$Customer->created),'end'=>date('n/j/Y',$Customer->created)),admin_url('admin.php'))); ?>"><?php _e('Joined','Shopp'); ?></a>: </strong><?php echo date(get_option('date_format'),$Customer->created); ?></p>
<?php endif; ?>
<?php do_action('shopp_customer_editor_info',$Customer); ?>
</div>
<div id="major-publishing-actions">
	<input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" />
</div>
<?php
}
ShoppUI::addmetabox('save-customer', __('Save','Shopp').$Admin->boxhelp('customer-editor-save'), 'save_meta_box', 'shopp_page_shopp-customers', 'side', 'core');

function settings_meta_box ($Customer) {
?>
	<p>
		<span>
		<input type="hidden" name="marketing" value="no" />
		<input type="checkbox" id="marketing" name="marketing" value="yes"<?php echo $Customer->marketing == 'yes'?' checked="checked"':''; ?>/>
		<label for="marketing" class="inline">&nbsp;<?php _e('Subscribes to marketing','Shopp'); ?></label>
		</span>
	</p>
	<br class="clear" />
	<p>
		<span>
		<select name="type"><?php echo Shopp::menuoptions(Lookup::customer_types(),$Customer->type); ?></select>
		<label for="type"><?php _e('Customer Type','Shopp'); ?></label>
		</span>
	</p>
	<br class="clear" />
	<?php do_action('shopp_customer_editor_settings',$Customer); ?>
<?php
}
ShoppUI::addmetabox('customer-settings', __('Settings','Shopp').$Admin->boxhelp('customer-editor-settings'), 'settings_meta_box', 'shopp_page_shopp-customers', 'side', 'core');

function login_meta_box ($Customer) {
	$wp_user = get_userdata($Customer->wpuser);
	$avatar = get_avatar( $Customer->wpuser, 48 );
	$userlink = add_query_arg('user_id',$Customer->wpuser,admin_url('user-edit.php'));


	if ('wordpress' == shopp_setting('account_system')):
?>
<div class="alignleft avatar">
	<?php if ($Customer->wpuser > 0): ?><a href="<?php echo esc_url($userlink); ?>"><?php endif; ?>
	<?php echo $avatar; ?><?php if ($Customer->wpuser > 0):?></a><?php endif; ?>
</div>
<p>
	<span>
	<input type="hidden" name="userid" id="userid" value="<?php echo esc_attr($Customer->wpuser); ?>" />
	<input type="text" name="userlogin" id="userlogin" value="<?php echo esc_attr($wp_user->user_login); ?>" size="20" class="selectall" /><br />
	<label for="userlogin"><?php _e('WordPress Login','Shopp'); ?></label>
	</span>
<?php endif; ?>
<h4><?php _e('New Password','Shopp'); ?></h4>
<p>
	<input type="password" name="new-password" id="new-password" value="" size="20" class="selectall" /><br />
	<label for="new-password"><?php _e('Enter a new password to change it.','Shopp'); ?></label>
</p>
<p>
	<input type="password" name="confirm-password" id="confirm-password" value="" size="20" class="selectall" /><br />
	<label for="confirm-password"><?php _e('Confirm the new password.','Shopp'); ?></label>
</p>
<br class="clear" />
<div id="pass-strength-result"><?php _e('Strength indicator'); ?></div>
<br class="clear" />
<?php
}
ShoppUI::addmetabox('customer-login', __('Login &amp; Password','Shopp').$Admin->boxhelp('customer-editor-password'), 'login_meta_box', 'shopp_page_shopp-customers', 'side', 'core');


function profile_meta_box ($Customer) {
?>
<p>
	<span>
	<input type="text" name="firstname" id="firstname" value="<?php echo esc_attr($Customer->firstname); ?>" size="14" /><br />
	<label for="firstname"><?php _e('First Name','Shopp'); ?></label>
	</span>
	<span>
	<input type="text" name="lastname" id="lastname" value="<?php echo esc_attr($Customer->lastname); ?>" size="30" /><br />
	<label for="lastname"><?php _e('Last Name','Shopp'); ?></label>
	</span>
</p>
<p>
	<input type="text" name="company" id="company" value="<?php echo esc_attr($Customer->company); ?>" /><br />
	<label for="company"><?php _e('Company','Shopp'); ?></label>
</p>
<p>
	<span>
	<input type="text" name="email" id="email" value="<?php echo esc_attr($Customer->email); ?>" size="24" /><br />
	<label for="email"><?php _e('Email','Shopp'); ?> <em><?php _e('(required)','Shopp')?></em></label>
	</span>
	<span>
	<input type="text" name="phone" id="phone" value="<?php echo esc_attr($Customer->phone); ?>" size="20" /><br />
	<label for="phone"><?php _e('Phone','Shopp'); ?></label>
	</span>
</p>

<br class="clear" />

<?php
}
ShoppUI::addmetabox('customer-profile', __('Profile','Shopp').$Admin->boxhelp('customer-editor-profile'), 'profile_meta_box', 'shopp_page_shopp-customers', 'normal', 'core');

function info_meta_box ($Customer) {
?>
<?php if (is_array($Customer->info->meta)):
		foreach($Customer->info->meta as $id => $meta): ?>
		<p>
			<?php echo apply_filters('shopp_customer_info_input','<input type="text" name="info['.$meta->id.']" id="info-'.$meta->id.'" value="'.esc_attr($meta->value).'" />',$meta); ?>
			<br />
			<label for="info-<?php echo $meta->id; ?>"><?php echo esc_html($meta->name); ?></label>
		</p>
<?php endforeach; endif;?>

<?php
}
ShoppUI::addmetabox('customer-info', __('Details','Shopp').$Admin->boxhelp('customer-editor-details'), 'info_meta_box', 'shopp_page_shopp-customers', 'normal', 'core');


function billing_meta_box ($Customer) {
?>
<p>
	<input type="text" name="billing[address]" id="billing-address" value="<?php echo esc_attr($Customer->Billing->address); ?>" /><br />
	<input type="text" name="billing[xaddress]" id="billing-xaddress" value="<?php echo esc_attr($Customer->Billing->xaddress); ?>" /><br />
	<label for="billing-address"><?php _e('Street Address','Shopp'); ?></label>
</p>
<p>
	<span>
	<input type="text" name="billing[city]" id="billing-city" value="<?php echo esc_attr($Customer->Billing->city); ?>" size="14" /><br />
	<label for="billing-city"><?php _e('City','Shopp'); ?></label>
	</span>
	<span id="billing-state-inputs">
		<select name="billing[state]" id="billing-state">
			<?php echo menuoptions($Customer->billing_states,$Customer->Billing->state,true); ?>
		</select>
		<input type="text" name="billing[state]" id="billing-state-text" value="<?php echo esc_attr($Customer->Billing->state); ?>" size="12" disabled="disabled"  class="hidden" />
	<label for="billing-state"><?php _e('State / Province','Shopp'); ?></label>
	</span>
	<span>
	<input type="text" name="billing[postcode]" id="billing-postcode" value="<?php echo esc_attr($Customer->Billing->postcode); ?>" size="10" /><br />
	<label for="billing-postcode"><?php _e('Postal Code','Shopp'); ?></label>
	</span>
</p>
<p>
	<span>
		<select name="billing[country]" id="billing-country">
			<?php echo menuoptions($Customer->countries,$Customer->Billing->country,true); ?>
		</select>
	<label for="billing-country"><?php _e('Country','Shopp'); ?></label>
	</span>
</p>

<br class="clear" />
<?php
}
ShoppUI::addmetabox('customer-billing', __('Billing Address','Shopp').$Admin->boxhelp('customer-editor-billing'), 'billing_meta_box', 'shopp_page_shopp-customers', 'normal', 'core');

function shipping_meta_box ($Customer) {
?>
<p>
	<input type="text" name="shipping[address]" id="shipping-address" value="<?php echo esc_attr($Customer->Shipping->address); ?>" /><br />
	<input type="text" name="shipping[xaddress]" id="shipping-xaddress" value="<?php echo esc_attr($Customer->Shipping->xaddress); ?>" /><br />
	<label for="shipping-address"><?php _e('Street Address','Shopp'); ?></label>
</p>
<p>
	<span>
	<input type="text" name="shipping[city]" id="shipping-city" value="<?php echo esc_attr($Customer->Shipping->city); ?>" size="14" /><br />
	<label for="shipping-city"><?php _e('City','Shopp'); ?></label>
	</span>
	<span id="shipping-state-inputs">
		<select name="shipping[state]" id="shipping-state">
			<?php echo menuoptions($Customer->shipping_states,$Customer->Shipping->state,true); ?>
		</select>
		<input type="text" name="shipping[state]" id="shipping-state-text" value="<?php echo esc_attr($Customer->Shipping->state); ?>" size="12" disabled="disabled"  class="hidden" />
	<label for="shipping-state"><?php _e('State / Province','Shopp'); ?></label>
	</span>
	<span>
	<input type="text" name="shipping[postcode]" id="shipping-postcode" value="<?php echo esc_attr($Customer->Shipping->postcode); ?>" size="10" /><br />
	<label for="shipping-postcode"><?php _e('Postal Code','Shopp'); ?></label>
	</span>
</p>
<p>
	<span>
		<select name="shipping[country]" id="shipping-country">
			<?php echo menuoptions($Customer->countries,$Customer->Shipping->country,true); ?>
		</select>
	<label for="shipping-country"><?php _e('Country','Shopp'); ?></label>
	</span>
</p>

<br class="clear" />
<?php
}
ShoppUI::addmetabox('customer-shipping', __('Shipping Address','Shopp').$Admin->boxhelp('customer-editor-shipping'), 'shipping_meta_box', 'shopp_page_shopp-customers', 'normal', 'core');

?>