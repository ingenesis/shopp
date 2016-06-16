/*!
 * payments.js - Payment method settings UI behaviors
 * Copyright © 2011 by Ingenesis Limited. All rights reserved.
 * Licensed under the GPLv3 {@see license.txt}
 */

jQuery(document).ready( function($) {
	$.each(gateways,function (index,gateway) {
		$.template(gateway+'-editor',$('#'+gateway+'-editor'));
	});

	var editing = false,
		menu = $('#payment-option-menu'),
		notice = $('#no-payment-settings'),

		AddPayment = function (e) {
			if (e) e.preventDefault();
			if (editing) editing.cancel(false);
			notice.hide();
			var $this = $(this),
				row = $this.parents('tr').hide(),
				selected = menu.val().toLowerCase(),
				id = $this.attr('href')?$this.attr('href').split('&')[1].split('=')[1].toLowerCase().split('-'):false,
				gateway = id?id[0]:selected,
				instance = id?id[1]:0,
				settings = !id && $ps[gateway]?$.each($ps[gateway],function (i,d) { if (!isNaN(i)) instance++; }):false,
				data = $ps[gateway] && $ps[gateway][instance]? $.extend($ps[gateway][instance],{instance:instance}):$.extend($ps[gateway],{instance:instance}),
				ui = $.tmpl(gateway+'-editor',data),
				cancel = ui.find('a.cancel'),
				selectall = ui.find('input.selectall-toggle').change(function (e) {
					var $this = $(this),
						options = $this.parents('ul').find('input');
					options.attr('checked',$this.attr('checked'));
				});

			if (row.size() == 0) row = $('#payment-setting-'+id).hide();
			menu.get(0).selectedIndex = 0;

			$this.cancel = function (e) {
				if (e) e.preventDefault();
				editing = false;
				ui.remove();
				row.fadeIn('fast');
				if (notice.size() > 0) notice.show();
			};
			cancel.click($this.cancel);

			if (row.size() > 0) ui.insertAfter(row);
			else ui.prependTo('#payments-settings-table');

			$(document).trigger(gateway+'Settings',[ui]);
			quickSelects(ui);

			editing = $this;

		};

	$('#payments a.edit').click(AddPayment);
	$('#payment-option-menu').change(AddPayment);

	$('#payments a.delete').click(function() {
		if (confirm($ps.confirm)) return true;
		else return false;
	});


});