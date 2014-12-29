	<div class="wrap shopp">

		<div class="icon32"></div>
		<h2><?php _e('Customer Editor','Shopp'); ?></h2>

		<?php do_action('shopp_admin_notice'); ?>

		<div id="ajax-response"></div>
		<form name="customer" id="customer" action="<?php echo add_query_arg('page',$this->Admin->pagename('customers'),admin_url('admin.php')); ?>" method="post">
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

jQuery(document).ready( function() {

var $=jQuery,
	regions = <?php echo json_encode($regions); ?>,
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

updateStates('#billing-country','#billing-state-inputs');
updateStates('#shipping-country','#shipping-state-inputs');

function updateStates (country,state)  {
	var selector = $(state).find('select');
	var text = $(state).find('input');
	var label = $(state).find('label');

	function toggleStateInputs () {
		if ($(selector).children().length > 1) {
			$(selector).show().attr('disabled',false);
			$(text).hide().attr('disabled',true);
			$(label).attr('for',$(selector).attr('id'))
		} else {
			$(selector).hide().attr('disabled',true);
			$(text).show().attr('disabled',false);
			$(label).attr('for',$(text).attr('id'))
		}

	}

	$(country).change(function() {
		if ($(selector).children().length > 1) $(text).val('');
		if ($(selector).attr('type') == "text") return true;
		$(selector).empty().attr('disabled',true);
		$('<option><\/option>').val('').html('').appendTo(selector);
		if (regions[this.value]) {
			$.each(regions[this.value], function (value,label) {
				option = $('<option><\/option>').val(value).html(label).appendTo(selector);
			});
			$(selector).attr('disabled',false);
		}
		toggleStateInputs();
	});

	toggleStateInputs();

}

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

});
/* ]]> */
</script>