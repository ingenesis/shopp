/*!
 * shiprates.js - Shipping rates UI behaviors
 * Copyright © 2011 by Ingenesis Limited. All rights reserved.
 * Licensed under the GPLv3 {@see license.txt}
 */

jQuery(document).ready( function($) {
	if ($('#flatrates-editor').size() > 0) {
		$.each(shipping,function (index,shipping) {
			$.template(shipping+'-editor',$('#'+shipping+'-editor'));
		});

		$.template('delivery-menu',$('#delivery-menu'));
		$.template('flatrates-editor',$('#flatrates-editor'));
		$.template('flatrate-row',$('#flatrate-row'));
		$.template('tablerates-editor',$('#tablerates-editor'));
		$.template('tablerate-row',$('#tablerate-row'));
		$.template('tablerate-row-tier',$('#tablerate-row-tier'));
		$.template('location-fields',$('#location-fields'));
	}

	var editing = false,
		menu = $('#shipping-option-menu'),
		notice = $('#no-shiprate-settings'),

		LocationFields = function (setting) {
			if (!setting.postcode) setting.postcode = '*';
			var _ = this,
				ui = $.tmpl('location-fields',setting),
				menu = ui.find('select'),
				postcode = ui.find('input.postcode'),

				option = function (value,label) {
					return '<option value="'+value+'">'+label+'</option>';
				},
				update = function (e,setting) {

					var selection = menu.val().split(','),
						ui = '',value = [],regionals = [],
						menuarrow = ' &#x25be;',
						tab = '&sdot;&sdot;&sdot;&nbsp;',
						selected = {
							region:'*',
							country:'',
							area:'',
							zone:''
						},index = 0,haspostcode = false;

					if (setting) selection = setting.split(',');

					for (key in selected) {
						if (selection[index])
							selected[key] = selection[index];
						index++;
					}

					for (index in lookup.regions) { // World regions

						if (index == selected.region && lookup.regionmap[index]) { // Selected region's countries

							ui += option(index,lookup.regions[index]+menuarrow);
							regionals = lookup.regionmap[index];
							for (r in regionals) {
								country = regionals[r];
								countryname = lookup.countries[country];

								if (country == selected.country) { // Selected country
									haspostcode = (lookup.postcodes[country]);

									if (lookup.areas[country]) { // Country areas
										ui += option(index+','+country,countryname+menuarrow);

										areas = lookup.areas[country];
										for (area in areas) {
											if (area == selected.area) {
												zones = lookup.areas[country][area];
												ui += option(index+','+country+','+area,area+menuarrow);
												ui += '<optgroup label="'+area+'">';

												for (zoneid in zones) {
													zone = zones[zoneid];
													ui += option(index+','+country+','+area+','+zone,lookup.zones[country][zone]+', '+country.substr(0,2));
												}

												ui += '</optgroup>';
											} else ui += option(index+','+country+','+area,'&nbsp;&nbsp;'+area);
										}


									} else if (lookup.zones[country]) { // Country zones (states/provinces)
										ui += option(index+','+country,countryname+menuarrow);
										ui += '<optgroup label="'+countryname+'">';
										zones = lookup.zones[country];
										for (zone in zones) {
											ui += option(index+','+country+','+zone, zones[zone]+', '+country.substr(0,2) );
										}
										ui += '</optgroup>';
									} else ui += option(index+','+country,countryname);

								} else ui += option(index+','+country,tab+countryname);


							} // regionals

						} else ui += option(index,lookup.regions[index]); // selected.region

					}

					for (key in selected) {
						if (selected[key] != '') value.push(selected[key]);
					}

					menu.empty().html(ui).val(value.join(','));

					if (haspostcode) postcode.attr('disabled',false);

				};

			menu.change(update);

			update(false,setting.destination); // Initialize from settings

			return ui;
		},

		FlatRates = function (ui,settings) {
			if (!(ui && settings)) return;
			var rows = [],data = [],module = false,
				addrow = ui.parents('table.shopp-settings').find('button.addrate');

			if (settings.table) data = settings.table;
			if (settings.module) module = settings.module;
			if (settings.norates) ui.find('th.rate').remove();


			ui.row = function (e,data) {
				if (e) e.preventDefault();
				if (!data) data = {};
				data.module = module;
				data.row = (rows.length);
				var row = $.tmpl('flatrate-row',data),
					moneyfields = row.find('input.money').each(function () {
						this.value = asMoney(new Number(this.value));
					}).change(function () {
						this.value = asMoney(this.value);
					}).mouseup(function () { $(this).select(); }),
					loc = new LocationFields(data),
					delctrl = row.find('button.delete').click(function (e) {
						e.preventDefault();
						row.fadeRemove();
					});
				if (settings.norates) row.find('td.rate').remove();


				rows.push(row);

				if (rows.length > 1) {
					row.hover(
						function() { delctrl.fadeIn('fast'); },
						function() { delctrl.hide();
					});
				}

				loc.prependTo(row);
				row.appendTo(ui.find('tbody'));
			};

			addrow.click(ui.row);

			if (data.length == 0) ui.row(false);
			else {
				$.each(data,function (i,d) {
					ui.row(false,d);
				});
			}
		},

		TableRates = function (ui,settings) {
			if (!(ui && settings)) return;
			var rows = [],data = [],module = false,
				addrow = ui.parents('table.shopp-settings').find('button.addrate');

			if (settings.table) data = settings.table;
			if (settings.module) module = settings.module;

			ui.row = function (e,id,data) {
				var tiers = [];
				if (e) e.preventDefault();
				if (!data) data = {};

				data.module = module;
				data.row = (!id?rows.length:id);

				if (settings.unit) {
					data.unit = settings.unit[0]?settings.unit[0]:'?';
					data.unitabbr = settings.unit[1]?settings.unit[1]:$s.c;
				} else {
					data.unit = '?';
					data.unitabbr = '?';
				}

				var row = $.tmpl('tablerate-row',data),
					rowid = data.row,
					tierpanel = row.find('table.panel'),
					loc = new LocationFields(data),
					delctrl = row.find('button.delete').click(function (e) {
						e.preventDefault();
						row.fadeRemove();
					});
					loc.insertBefore(row.find('td:first'));

				rows.push(row);

				if (rows.length > 1) {
					row.hover(
						function() { delctrl.fadeIn('fast'); },
						function() { delctrl.hide();
					});
				}

				row.addtier = function (e,tierset) {
					if (e) e.preventDefault();

					if (!tierset) tierset = {threshold:1,rate:1};
					tierset.module = module;
					tierset.row = rowid;
					tierset.tier = tiers.length;
					tierset.unitabbr = '';
					tierset.threshold_class = settings.threshold_class;
					tierset.rate_class = settings.rate_class;
					if (settings.unit && settings.unit[1]) tierset.unitabbr = settings.unit[1];

					var tier = $.tmpl('tablerate-row-tier',tierset),
						rate = tier.find('input').mouseup(function () { $(this).select(); }),
						addctrl = tier.find('button.add').click(row.addtier),
						delctrl = tier.find('button.delete').click(function (e) {
							e.preventDefault();
							tier.fadeRemove();
						});

					tier.find('input.money').each(function () {
						this.value = asMoney(this.value.match(/[^(\d,\. )]/) ? asNumber(this.value) : new Number(this.value));
					}).change(function () {
						this.value = asMoney(this.value);
					});
					tier.find('input.percentage').each(function () {
						this.value = asPercent(this.value.match(/[^(\d,\. )]/) ? asNumber(this.value) : new Number(this.value));
					}).change(function () {
						this.value = asPercent(this.value);
					}).change();


					tiers.push(tier);

					if (tiers.length > 1) {
						tier.hover(
							function() { delctrl.fadeIn('fast'); },
							function() { delctrl.hide();
						});
					} else {
						delctrl.css('opacity',0)
							.removeClass('hidden')
							.unbind('click')
							.click(function(e) { e.preventDefault(); });
					}

					tier.appendTo(tierpanel);
				};


				if (!data.tiers) row.addtier();
				else {
					$.each(data.tiers,function (i,tier) {
						row.addtier(false,tier);
					});
				}
				row.appendTo(ui);
			};

			addrow.click(ui.row);

			if (data.length == 0) ui.row(false,0);
			else {
				$.each(data,function (id,settings) {
					ui.row(false,id,settings);
				});
			}

		},

		AddShipping = function (e) {
			var editortable = false;
			e.preventDefault();
			if (editing) editing.cancel(false);
			notice.hide();

			var $this = $(this),
				setting = $.getQueryVar('id',$this.attr('href')),
				selected = menu.val()?menu.val().toLowerCase():setting,
				data = settings[selected] ? settings[selected] : (defaults[selected]?defaults[selected]:{}),
				row = $this.parents('tr').hide(),
				rowid = row.size() > 0?row.attr('id').substr(17):false,
				id = data.type ? data.type : (rowid?rowid:selected),
				ui = $.tmpl(id+'-editor',data),
				cancel = ui.find('a.cancel'),
				fb = ui.find('input.fallback').attr('checked','on' == data.fallback?'checked':false),
				maxd = ui.find('select.maxdelivery').html($.tmpl('delivery-menu')).val(data.maxdelivery),
				mind = ui.find('select.mindelivery').html($.tmpl('delivery-menu')).val(data.mindelivery).change(function () {
					var $this = $(this),selection = $this.attr('selectedIndex'),maxselected = maxd.attr('selectedIndex');
					maxd.find('option').attr('disabled',false).each(function (i,option) {
						if (i < selection) $(option).attr('disabled','disabled');
					});
					if (maxselected < selection) maxd.attr('selectedIndex',selection);
				}),
				selectall = ui.find('input.selectall-toggle').change(function (e) {
					var $this = $(this),
						options = $this.parents('ul').find('input');
					options.attr('checked',$this.attr('checked'));
				}),
				ratetable = (ui.find('table.rate-table-shipping').size() > 0);

			if (row.size() == 0) row = $('#shipping-setting-'+id).hide();
			menu.get(0).selectedIndex = 0;

			$this.cancel = function (e) {
				if (e) e.preventDefault();
				editing = false;
				ui.remove();
				row.fadeIn('fast');
				if (notice.size() > 0) notice.show();
			};
			cancel.click($this.cancel);

			// Bootup rate table handlers
			if (ratetable) {
				editortable = ui.find('table.flatrates');
				if (editortable.size() > 0) FlatRates(editortable,data);
				editortable = ui.find('table.tablerates');
				if (editortable.size() > 0) TableRates(editortable,data);
			}

			if (row.size() > 0) ui.insertAfter(row);
			else ui.prependTo('#shiprates');

			editing = $this;
		};

	$('#shipping a.edit').click(AddShipping);
	menu.change(AddShipping);

	$('#shipping a.delete').click(function() {
		if (confirm($ps.confirm)) return true;
		else return false;
	});

});