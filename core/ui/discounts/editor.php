	<div class="wrap shopp">

		<div class="icon32"></div>
		<h2><?php _e('Discount Editor','Shopp'); ?> <a href="<?php echo esc_url(add_query_arg(array_merge($_GET,array('page'=>'shopp-discounts','id'=>'new')),admin_url('admin.php'))); ?>" class="add-new-h2"><?php _e('Add New','Shopp'); ?></a> </h2>

		<?php do_action('shopp_admin_notices'); ?>

		<div id="ajax-response"></div>

		<form name="promotion" id="promotion" action="<?php echo esc_url($this->url); ?>" method="post">
			<?php wp_nonce_field('shopp-save-discount'); ?>

			<div class="hidden"><input type="hidden" name="id" value="<?php echo $Promotion->id; ?>" /></div>

			<div id="poststuff" class="metabox-holder has-right-sidebar">

				<div id="side-info-column" class="inner-sidebar">
				<?php
				do_action('submitpage_box');
				$side_meta_boxes = do_meta_boxes("shopp_page_$this->page", 'side', $Promotion);
				?>
				</div>

				<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : 'has-sidebar'; ?>">
				<div id="post-body-content" class="has-sidebar-content">

					<div id="titlediv">
						<div id="titlewrap">
							<label class="hide-if-no-js<?php if (!empty($Promotion->name)) echo ' hidden'; ?>" id="title-prompt-text" for="title"><?php _e('Enter discount name','Shopp'); ?></label>

							<input name="name" id="title" type="text" value="<?php echo esc_attr($Promotion->name); ?>" size="30" tabindex="1" autocomplete="off" />
						</div>
					</div>

				<?php

				do_meta_boxes("shopp_page_$this->page", 'normal', $Promotion);
				do_meta_boxes("shopp_page_$this->page", 'advanced', $Promotion);
				wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);

				?>

				</div>
				</div>

			</div> <!-- #poststuff -->
		</form>
	</div>

<script type="text/javascript">

jQuery(document).ready( function($) {

var suggurl = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'wp_ajax_shopp_suggestions'); ?>',
	rules = <?php echo json_encode($Promotion->rules); ?>,
	promotion = <?php echo ( ! empty($Promotion->id) ) ? $Promotion->id : 'false'; ?>,
	ruleidx = 1,
	itemidx = 1,
	loading = true,
	titlePrompt = $('#title-prompt-text'),

	// Give the product name initial focus
	title = $('#title').bind('focus keydown',function () {
		titlePrompt.hide();
	}).blur(function () {
		if (title.val() == '') titlePrompt.show();
		else titlePrompt.hide();
	}),

	SCOPEPROP_LANG = <?php ShoppAdminDiscounter::scopes(); ?>,
	TARGET_LANG = <?php ShoppAdminDiscounter::targets(); ?>,
	RULES_LANG = <?php ShoppAdminDiscounter::rules(); ?>,
	conditions = <?php ShoppAdminDiscounter::conditions(); ?>,
	logic = <?php ShoppAdminDiscounter::logic(); ?>,

	Conditional = function (type,settings,location) {
		var target = $('#promotion-target').val(),
			row = false, i = false;

		if (!type) type = 'condition';

		if (type == "cartitem") {
			i = itemidx;
			if (!location) row = $('<tr />').appendTo('#cartitem');
			else row = $('<tr></tr>').insertAfter(location);
		} else {
			i = ruleidx;
			if (!location) row = $('<tr />').appendTo('#rules');
			else row = $('<tr></tr>').insertAfter(location);
		}

		var cell = $('<td></td>').appendTo(row),
			deleteButton = $('<?php echo ShoppUI::button('delete', 'delete', array('type' => 'button')); ?>').appendTo(cell).click(function () { if (i > 1) $(row).remove(); }).attr('opacity',0),

			properties_name = (type=='cartitem')?'rules[item]['+i+'][property]':'rules['+i+'][property]',
			properties = $('<select name="'+properties_name+'" class="ruleprops"></select>').appendTo(cell);

		if (type == "cartitem") target = "Cart Item Target";
		if (conditions[target])
			for (var label in conditions[target])
				$('<option></option>').html(RULES_LANG[label]).val(label).attr('rel',target).appendTo(properties);

		var operation_name = (type=='cartitem')?'rules[item]['+i+'][logic]':'rules['+i+'][logic]',
			operation = $('<select name="'+operation_name+'" ></select>').appendTo(cell),
			value = $('<span></span>').appendTo(cell),

			addspan = $('<span></span>').appendTo(cell);

		$('<?php echo ShoppUI::button('add', 'add', array('type' => 'button')); ?>').appendTo(addspan).click(function () { new Conditional(type,false,row); });

		cell.hover(function () {
			if (i > 1) deleteButton.css({'opacity':100,'visibility':'visible'});
		},function () {
			deleteButton.animate({'opacity':0});
		});

		var valuefield = function (fieldtype) {
			value.empty();
			var name = (type=='cartitem')?'rules[item]['+i+'][value]':'rules['+i+'][value]';
			if (fieldtype == "number") field = $('<input type="number" name="'+name+'" class="selectall" size="5" />').appendTo(value);
			else field = $('<input type="text" name="'+name+'" class="selectall" />').appendTo(value);
			if (fieldtype == "price") field.change(function () { this.value = asMoney(this.value); });
			return field;
		}

		// Generate logic operation menu
		properties.change(function () {
			operation.empty();
			if (!$(this).val()) this.selectedIndex = 0;
			var property = $(this).val();
			var c = false;
			if (conditions[$(this).find(':selected').attr('rel')]);
				c = conditions[$(this).find(':selected').attr('rel')][property];

			if (c['logic'].length > 0) {
				operation.show();
				for (var l = 0; l < c['logic'].length; l++) {
					var lop = c['logic'][l];
					if (!lop) break;
					for (var op = 0; op < logic[lop].length; op++)
						$('<option></option>').html(RULES_LANG[logic[lop][op]]).val(logic[lop][op]).appendTo(operation);
				}
			} else operation.hide();

			if (!c['suggest']) c['suggest'] = 'text';
			valuefield(c['value']).unbind('keydown').unbind('keypress').suggest(
				suggurl+'&action=shopp_suggestions&s='+c['source'],
				{ delay:500, minchars:2, format:'json', value:c['suggest'] }
			);

		}).change();

		// Load up existing conditional rule
		if (settings) {
			properties.val(settings.property).change();
			operation.val(settings.logic);
			if (field) field.val(settings.value);
		}

		if (type == "cartitem") itemidx++;
		else ruleidx++;
	};

$('.postbox a.help').click(function () {
	$(this).colorbox({iframe:true,open:true,innerWidth:768,innerHeight:480,scrolling:false});
	return false;
});


$('#discount-type').change(function () {
	$('#discount-row').hide();
	$('#bogof-row').hide();
	var type = $(this).val();

	if (type == "Percentage Off" || type == "Amount Off") $('#discount-row').show();
	if (type == "Buy X Get Y Free") {
		$('#bogof-row').show();
		$('#promotion-target').val('Cart Item').change();
		$('#promotion-target option:lt(2)').attr('disabled',true);
	} else {
		$('#promotion-target option:lt(2)').attr('disabled',false);
	}

	$('#discount-amount').unbind('change').change(function () {
		var value = this.value;
		if (loading) {
			value = new Number(this.value);
			loading = !loading;
		}
		if (type == "Percentage Off") this.value = asPercent(value);
		if (type == "Amount Off") this.value = asMoney(value);
	}).change();

}).change();

$('#promotion-target').change(function () {
	var target = $(this).val(),
		menus = $('#rules select.ruleprops');
	$('#target-property').html(SCOPEPROP_LANG[target]);
	$('#rule-target').html(TARGET_LANG[target]);
	$(menus).empty().each(function (id,menu) {
		for (var label in conditions[target])
			$('<option></option>').html(RULES_LANG[label]).val(label).attr('rel',target).appendTo($(menu));
	}).change();
	if (target == "Cart Item") {
		if (rules['item']) for (var r in rules['item']) new Conditional('cartitem',rules['item'][r]);
		else new Conditional('cartitem');
	} else $('#cartitem').empty();

}).change();


if (rules) {
	for (var r in rules) if (r != 'item') new Conditional('condition',rules[r]);
} else new Conditional();

$('<div id="starts-calendar" class="calendar"></div>').appendTo('#wpwrap').PopupCalendar({
	m_input:$('#starts-month'),
	d_input:$('#starts-date'),
	y_input:$('#starts-year')
}).bind('show',function () {
	$('#ends-calendar').hide();
});

$('<div id="ends-calendar" class="calendar"></div>').appendTo('#wpwrap').PopupCalendar({
	m_input:$('#ends-month'),
	d_input:$('#ends-date'),
	y_input:$('#ends-year')
}).bind('show',function () {
	$('#starts-calendar').hide();
});

postboxes.add_postbox_toggles('shopp_page_shopp-promotions');

if ( ! promotion ) $('#title').focus();

});

</script>