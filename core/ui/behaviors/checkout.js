/*!
 * checkout.js - Shopp catalog behaviors library
 * Copyright © 2008-2010 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */

jQuery(document).ready(function () {
	var $ = jqnc(),login=false,
		sameaddr = $('.sameaddress'),
		submitLogin = $('#submit-login-checkout'),
		accountLogin = $('#account-login-checkout'),
		passwordLogin = $('#password-login-checkout'),
		guest = $('#guest-checkout'),
		checkoutForm = $('#checkout.shopp'),
		paymethods = $('#checkout.shopp [name=paymethod]'),
		localeMenu = $('#billing-locale'),
		billCard = $('#billing-card'),
		billCardtype = $('#billing-cardtype'),
		checkoutButtons = $('.payoption-button'),
		checkoutButton = $('.payoption-' + decodeURIComponent(d_pm)),
		checkoutProcess = $('#shopp-checkout-function'),
		localeFields = $('#checkout.shopp li.locale');

	// No payment option selectors found, use default when on checkout page only
	if (checkoutForm.find('input[name=checkout]').val() == "process") {
		checkoutButtons.hide();
		if (checkoutButton.length == 0) checkoutButton = $('.payoption-0');
		checkoutButton.show();
		paymethods.change(paymethod_select).change();
	}

	// Validate paycard number before submit
	checkoutForm.bind('shopp_validate',function () {
		if (!validcard()) this.shopp_validation = ["Not a valid card number.",billCard.get(0)];
	});

	// Validate paycard number on entry
	billCard.change(validcard);

	// Enable/disable the extra card security fields when needed
	billCardtype.change(function () {

		var cardtype = new String( billCardtype.val() ).toLowerCase(),
			card = paycards[cardtype];

		$('.paycard.xcsc').attr('disabled',true).addClass('disabled');
		if (!card || !card['inputs']) return;

		$.each(card['inputs'],function (input,inputlen) {
			$('#billing-xcsc-'+input).attr('disabled',false).removeClass('disabled');
		});

	}).change();

	if (localeMenu.children().size() == 0) localeFields.hide();

	submitLogin.click(function (e) {
		checkoutForm.unbind('submit.validate').bind('submit.validlogin',function (e) {
			var error = false;
			if ('' == passwordLogin.val()) error = [$co.loginpwd,passwordLogin];
			if ('' == accountLogin.val()) error = [$co.loginname,accountLogin];
			if (error) {
				e.preventDefault();
				checkoutForm.unbind('submit.validlogin').bind('submit.validate',function (e) {
					return validate(this);
				});
				alert(error[0]);
				error[1].focus().addClass('error');
				return false;
			}
			checkoutProcess.val('login');
		});
 	});

	// Locale Menu
	$('#billing-country, .billing-state, #shipping-country, .shipping-state').bind('change.localemenu',function (e, init) {
		var	sameaddress = sameaddr.is(':checked') ? sameaddr.val() : false,
			country = 'shipping' == sameaddress ? $('#billing-country').val() : $('#shipping-country').val(),
			state = 'shipping' == sameaddress ? $('.billing-state[disabled!="true"]').val() : $('.shipping-state[disabled!="true"]').val(),
			id = country+state,
			options,
			locale;
		if ( 	init ||
				! localeMenu.get(0) ||
			( 	! sameaddress && ( $(this).is('#billing-country') || $(this).is('.billing-state') ) )
			) return;
		localeMenu.empty().attr('disabled',true);
		if ( locales && (locale = locales[id]) || (locale = locales[country]) ) {
			options += '<option></option>';
			$.each(locale, function (index,label) {
				options += '<option value="'+label+'">'+label+'</option>';
			});
			$(options).appendTo(localeMenu);
			localeMenu.removeAttr('disabled');
			localeFields.show();
		}
	});

	/*$('#firstname,#lastname').change(function () {
		$('#billing-name,#shipping-name').val(new String($('#firstname').val()+" "+$('#lastname').val()).trim());
	});

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
			.click(function () { $(this).change(); }); // For IE compatibility;*/

	guest.change(function(e) {
		var passwords = checkoutForm.find('input.passwords'),labels = [];
		$.each(passwords,function () { labels.push('label[for='+$(this).attr('id')+']'); });
		labels = checkoutForm.find(labels.join(','));

		if (guest.is(':checked')) {
			passwords.setDisabled(true).hide();
			labels.hide();
		} else {
			passwords.setDisabled(false).show();
			labels.show();
		}

	}).trigger('change');

	$('.shopp .shipmethod').change(function () {
		if ( $.inArray($('#checkout #shopp-checkout-function').val(), ['process','confirmed']) != -1 ) {
			var prefix = '.shopp-cart.cart-',
				spans = 'span'+prefix,
				inputs = 'input'+prefix,
				fields = ['shipping','tax','total'],
				selectors = [];

			$.each(fields,function (i,name) { selectors.push(spans+name); });
			if (!c_upd) c_upd = '?';
			$(selectors.join(',')).html(c_upd);
			$.getJSON($co.ajaxurl+"?action=shopp_ship_costs&method="+$(this).val(),
				function (r) {

					$.each(fields,function (i,name) {
						$(spans+name).html(asMoney(new Number(r[name])));
						$(inputs+name).val(new Number(r[name]));
					});

				}
			);
		} else $(this).parents('form').submit();
	});

	$(window).load(function () {
		$(document).trigger('shopp_paymethod',[paymethods.val()]);
	});

	function paymethod_select (e) {
		var $this = $(this),paymethod = decodeURIComponent($(this).val()),checkoutButton = $('.payoption-'+paymethod),options='',pc = false;

		if (this != window && $this.attr && 'radio' == $this.attr('type') && !$this.is(':checked')) return;
		$(document).trigger('shopp_paymethod',[paymethod]);

		checkoutButtons.hide();
		if (checkoutButton.length == 0) checkoutButton = $('.payoption-0');

		if (pm_cards[paymethod] && pm_cards[paymethod].length > 0) {
			checkoutForm.find('.payment,.paycard').show();
			checkoutForm.find('.paycard.disabled').attr('disabled',false).removeClass('disabled');
			if (typeof(paycards) !== 'undefined') {
				$.each(pm_cards[paymethod], function (a,s) {
					if (!paycards[s]) return;
					pc = paycards[s];
					options += '<option value="'+pc.symbol+'">'+pc.name+'</option>';
				});
				billCardtype.html(options).change();
			}

		} else {
			checkoutForm.find('.payment,.paycard').hide();
			checkoutForm.find('.paycard').attr('disabled',true).addClass('disabled');
		}
		checkoutButton.show();
	}

	function validcard () {
		if (billCard.length == 0) return true;
		if (billCard.attr('disabled')) return true;
		var v = billCard.val().replace(/\D/g,''),
			paymethod = paymethods.filter(':checked').val()?paymethods.filter(':checked').val():paymethods.val(),
			card = false;
		if (!paymethod) paymethod = decodeURIComponent(d_pm);
		if (billCard.val().match(/(X)+\d{4}/)) return true; // If card is masked, skip validation
		if (!pm_cards[paymethod]) return true; // The selected payment method does not have cards
		$.each(pm_cards[paymethod], function (a,s) {
			var pc = paycards[s],pattern = new RegExp(pc.pattern.substr(1,pc.pattern.length-2));
			if (v.match(pattern)) {
				card = pc.symbol;
				return billCardtype.val(card).change();
			}
		});
		if (!luhn(v)) return false;
		return card;
	}

	function luhn (n) {
		n = n.toString().replace(/\D/g, '').split('').reverse();
		if (!n.length) return false;

		var total = 0;
		for (i = 0; i < n.length; i++) {
			n[i] = parseInt(n[i],10);
			total += i % 2 ? 2 * n[i] - (n[i] > 4 ? 9 : 0) : n[i];
		}
		return (total % 10) == 0;
	}

});

if (!locales) var locales = false;