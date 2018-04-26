	<div class="wrap shopp">

		<div class="icon32"></div>
		<h2><?php _e('Customer Editor','Shopp'); ?></h2>

		<?php do_action('shopp_admin_notices'); ?>

		<div id="ajax-response"></div>
		<form name="customer" id="customer" action="<?php echo $this->url(); ?>" method="post">
			<?php wp_nonce_field('shopp-save-customer'); ?>

			<div class="hidden"><input type="hidden" name="id" value="<?php echo $Customer->id; ?>" /></div>

			<div id="poststuff" class="metabox-holder has-right-sidebar">

				<div id="side-info-column" class="inner-sidebar">
				<?php
				do_action('submitpage_box');
				$side_meta_boxes = do_meta_boxes('shopp_page_shopp-customers', 'side', $Customer);
				?>
				</div>

				<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : 'has-sidebar'; ?>">
				<div id="post-body-content" class="has-sidebar-content">
				<?php
				do_meta_boxes('shopp_page_shopp-customers', 'normal', $Customer);
				do_meta_boxes('shopp_page_shopp-customers', 'advanced', $Customer);
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				?>

				</div>
				</div>

			</div> <!-- #poststuff -->
		</form>
	</div>

<script type="text/javascript">
/* <![CDATA[ */
	var address = [],
		regions = <?php echo json_encode($regions); ?>;

jQuery(document).ready( function() {

var $=jQuery,
	suggurl = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'),'wp_ajax_shopp_suggestions'); ?>',
	userlogin = $('#userlogin').unbind('keydown').unbind('keypress').suggest(
		suggurl+'&action=shopp_suggestions&s=wp_users',
		{ delay:500, minchars:2, format:'json' }
	);

postboxes.add_postbox_toggles('shopp_page_shopp-customers');

// close postboxes that should be closed
$('.if-js-closed').removeClass('if-js-closed').addClass('closed');

$('.postbox a.help').click(function () {
	$(this).colorbox({iframe:true,open:true,innerWidth:768,innerHeight:480,scrolling:false});
	return false;
});


// $('#username').click(function () {
// 	var url = $(this).attr('rel');
// 	if (url) document.location.href = url;
// });


// Derived from the WP password strength meter
// Copyright by WordPress.org
$('#new-password').val('').keyup( check_pass_strength );
$('#confirm-password').val('').keyup( check_pass_strength );
$('#pass-strength-result').show();

function check_pass_strength() {
	var pass1 = $('#new-password').val(), user = $('#email').val(), pass2 = $('#confirm-password').val(), strength;

	$('#pass-strength-result').removeClass('short bad good strong');
	if ( ! pass1 ) {
		$('#pass-strength-result').html( pwsL10n.empty );
		return;
	}

	strength = passwordStrength(pass1, user, pass2);

	switch ( strength ) {
		case 2:
			$('#pass-strength-result').addClass('bad').html( pwsL10n['bad'] );
			break;
		case 3:
			$('#pass-strength-result').addClass('good').html( pwsL10n['good'] );
			break;
		case 4:
			$('#pass-strength-result').addClass('strong').html( pwsL10n['strong'] );
			break;
		case 5:
			$('#pass-strength-result').addClass('short').html( pwsL10n['mismatch'] );
			break;
		default:
			$('#pass-strength-result').addClass('short').html( pwsL10n['short'] );
	}
}

function copyAddress () {
	var copiedData = {},
		loadBilling = this.id.indexOf('billing') !== -1,
		from = loadBilling ? 'shipping' : 'billing',
		to = loadBilling ? 'billing' : 'shipping',
		fromId = '#customer-' + from;
		toId = '#customer-' + to;

	$(fromId + ' input[type=text]:enabled, ' + fromId + ' select').each(function() {
		if ( ! this.name ) return;
		var field = this.name.replace(from, '');
		copiedData[field] = $(this).val();
	});

	$.fn.reverseEach = [].reverse;
	$(toId + ' input[type=text], ' + toId + ' select').reverseEach().each(function() {
		if ( ! this.name) return;
		var field = this.name.replace(to, '');
		if ( copiedData[field] !== undefined )
			$(this).val(copiedData[field]);

		if ( $(this)[0].selectize ) {
			var selectized = $(this)[0].selectize;
			selectized.setValue(copiedData[field]);
		}
	});
}

$('#load-billing-address').click(copyAddress);
$('#load-shipping-address').click(copyAddress);

});
/* ]]> */
</script>