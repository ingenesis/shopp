/*!
 * address.js - Description
 * Copyright © 2012 by Ingenesis Limited. All rights reserved.
 * Licensed under the GPLv3 {@see license.txt}
 */

(function($) {
	jQuery.fn.upstate = function () {

		if ( typeof regions === 'undefined' ) return;

		$(this).change(function (e,init) {
			var $this = $(this),
				prefix = $this.attr('id').split('-')[0],
				country = $this.val(),
				state = $this.parents().find('#' + prefix + '-state'),
				menu = $this.parents().find('#' + prefix + '-state-menu'),
				options = '<option value=""></option>';

			if (menu.length == 0) return true;
			if (menu.hasClass('hidden')) menu.removeClass('hidden').hide();

			if (regions[country] || (init && menu.find('option').length > 1)) {
				state.setDisabled(true).addClass('_important').hide();
				if (regions[country]) {
					$.each(regions[country], function (value,label) {
						options += '<option value="'+value+'">'+label+'</option>';
					});
					if (!init) menu.empty().append(options).setDisabled(false).show().focus();
					if (menu.hasClass('auto-required')) menu.addClass('required');
				} else {
					if (menu.hasClass('auto-required')) menu.removeClass('required');
				}
				menu.setDisabled(false).show();
				$('label[for='+state.attr('id')+']').attr('for',menu.attr('id'));
			} else {
				menu.empty().setDisabled(true).hide();
				state.setDisabled(false).show().removeClass('_important');

				$('label[for='+menu.attr('id')+']').attr('for',state.attr('id'));
				if (!init) state.val('').focus();
			}
		}).trigger('change',[true]);

		return $(this);

	};

})(jQuery);

jQuery(document).ready(function($) {
	var sameaddr = $('.sameaddress'),
		shipFields = $('#shipping-address-fields'),
		billFields = $('#billing-address-fields'),
		keepLastValue = function () { // Save the current value of the field
			$(this).attr('data-last', $(this).val());
		};
	
	// When first and last name are changed, pre-fill billing name and shipping name if empty
	$('#firstname,#lastname').change( function() {
		// If other name field is empty, bail
		var other_field_id = $(this).attr('id') == 'firstname' ? 'lastname' : 'firstname';
		if ( ! $('#' + other_field_id).val().trim() || ! $(this).val().trim() ) return;
		
		// Otherwise, proceed with setting billing and shipping name 
		$('#billing-name,#shipping-name').filter(function() {
	            return ( this.value === '' );
		}).val(new String($('#firstname').val()+" "+$('#lastname').val()).trim());
	} );

	// Update state/province
	$('#billing-country,#shipping-country').upstate();

	// Toggle same shipping address
	sameaddr.change(function (e,init) {
		var refocus = false,
			bc = $('#billing-country'),
			sc = $('#shipping-country'),
			prime = 'billing' == sameaddr.val() ? shipFields : billFields,
			alt   = 'shipping' == sameaddr.val() ? shipFields : billFields;

		if (sameaddr.is(':checked')) {
			prime.removeClass('half');
			alt.hide().find('.required').setDisabled(true);
		} else {
			prime.addClass('half');
			alt.show().find('.disabled:not(._important)').setDisabled(false);
			if (!init) refocus = true;
		}
		if (bc.is(':visible')) bc.trigger('change.localemenu',[init]);
		if (sc.is(':visible')) sc.trigger('change.localemenu',[init]);
		if (refocus) alt.find('input:first').focus();
	}).trigger('change',[true])
		.click(function () { $(this).change(); }); // For IE compatibility
});