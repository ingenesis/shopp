/*!
 * taxrates.js - Tax rate editor behaviors
 * Copyright © 2008-2011 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */

jQuery(document).ready( function($) {

	$.template('editor',$('#editor'));
	$.template('conditional',$('#conditional'));
	$.template('localrate',$('#localrate'));
	$.template('property-menu',$('#property-menu'));
	$.template('countries-menu',$('#countries-menu'));

	var editing = false,
		notice = $('#no-taxrates');

	$('#taxrates a.edit, #addrate').click(function (e) {
		if (e) e.preventDefault();
		notice.hide();

		var $this = $(this),
			ratesidx = rates.length, rulesidx = 0,
			row = $this.parents('tr').hide(),
			setting = $.getQueryVar('id',$this.attr('href')),
			settings = rates[setting]?rates[setting]:{},
			data = $.extend({'id':setting?setting:ratesidx++,'rate':0,'compound':'off','country':false,'zone':false,'logic':'any','rules':[]},settings),
			ui = $.tmpl('editor',data),
			conditionsui = ui.find('div.conditionals').hide().removeClass('no-conditions'),
			rulesui = conditionsui.find('ul'),
			logicmenu = ui.find('select.logic').val(data.logic),
			countrymenu = ui.find('select.country'),
			zonemenu = ui.find('select.zone').hide().removeClass('no-zones'),
			localratesui = ui.find('.local-rates').hide().removeClass('no-local-rates'),
			localratesul = localratesui.find('ul'),
			haslocals = ui.find('.has-locals'),
			addlocalsbtn = ui.find('.add-locals').hide().removeClass('has-local-rates'),
			rmlocalsbtn = ui.find('.rm-locals').hide().removeClass('no-local-rates'),
			uploadbtn = ui.find('button.upload'),
			upbtnparent = uploadbtn.parent(),
			instructions = ui.find('p.instructions'),
			cancel = ui.find('a.cancel'),
			taxrate = ui.find('#tax-rate').change(function () { this.value = asPercent(this.value,false,4); }).change(),
			compound = ui.find('#tax-compound').attr('checked', ('on' == data.compound) ),
			addconditions = ui.find('button.add');

		$this.rules = [];

		countrymenu.html($.tmpl('countries-menu')).val(data.country).change(function (e) {
			var $this = $(this),options = '',
				country_zones = zones[$this.val()] ? zones[$this.val()] : false;

			if (country_zones != false) {
				options += '<option value=""></option>';
				$.each(country_zones,function(value,label) {
					options += '<option value="'+value+'">'+label+'</option>';
				});
				zonemenu.html(options).val(data.zone).show();
			} else zonemenu.empty().hide();
		}).change();

		$this.cancel = function (e) {
			if (e) e.preventDefault();
			editing = false;
			ui.remove();
			row.fadeIn('fast');
			if (notice.size() > 0) notice.show();
		};
		cancel.click($this.cancel);

		/** Local rates management **/
		$this.addlocals = function (e) {
			if (e) e.preventDefault();
			addlocalsbtn.hide();
			rmlocalsbtn.show();
			haslocals.val('true');
			localratesui.hide().removeClass('no-local-rates').show();
		};
		addlocalsbtn.click($this.addlocals);

		$this.rmlocals = function (e) {
			if (e) e.preventDefault();
			rmlocalsbtn.hide();
			addlocalsbtn.show();
			haslocals.val('false');
			localratesui.fadeOut('fast');
		};
		rmlocalsbtn.click($this.rmlocals);

		uploadbtn.upload({
			name: 'ratefile',
			action: ajaxurl,
			params: {
				'action':'shopp_upload_local_taxes',
				'_wpnonce':$('#_wpnonce').val()
			},
			accept: 'text/plain,text/xml',
			maxfilesize:'1048576',
			onSubmit: function() {
				localratesul.empty();
				instructions.empty().removeClass('error');
				uploadbtn.attr('disabled',true).addClass('updating').parent().css('width','100%');
			},
			onComplete: function(results) {
				uploadbtn.removeAttr('disabled').removeClass('updating');
				try {
					r = $.parseJSON(results);
					if (r.error) {
						instructions.addClass('error').html(r.error);
					} else $this.addlocals(r);
				} catch (ex) { alert('LOCAL_RATES_UPLOADERR'); }
			}
		});

		$this.addlocalrate = function (localname,localrate) {
			var localdata = {id:data.id,'localename':localname,'localerate':asNumber(localrate)};
			$.tmpl('localrate',localdata).appendTo(localratesul);

		};

		$this.addlocals = function (locals) {
			$.each(locals,function (name,rate) {
				$this.addlocalrate(name,rate);
			});
			instructions.empty();
		};

		if (data.haslocals) {
			if (data.locals != undefined) $this.addlocals(data.locals);
			localratesui.show();
			rmlocalsbtn.show();
		} else addlocalsbtn.show();


		/** Rules management **/
		$this.addrule = function (e,rid,rule) {
			if (e) e.preventDefault();

			if (!rid) rid = rulesidx;
			if (!rule) rule = {};

			rule.id = data.id;
			rule.ruleid = rid;
			rule.rulevalue = rule.v;
			var conditional = $.tmpl('conditional',rule).appendTo(rulesui),
				search = conditional.find('input.value'),
				propmenu = conditional.find('select.property').html($.tmpl('property-menu')).val(rule.p).change(function () {
					search.unbind('keydown').unbind('keypress').suggest(
						suggurl+'&action=shopp_suggestions&t='+$(this).val(),
						{ delay:500, minchars:2, format:'json' }
					);
				}).change(),
				rmrulebtn = conditional.find('button.delete').click(function(e) {
					if (e) e.preventDefault();
					conditional.remove();
					$this.rules.pop();
					if ($this.rules.length == 0) conditionsui.hide();
				}).hide();

			addrulebtn = conditional.find('button.add').click($this.addrule);
			conditional.hover(function () { rmrulebtn.show(); },function () { rmrulebtn.fadeOut('fast'); });
			conditionsui.show();
			rulesidx++;
			$this.rules.push(rulesidx);
		};
		addconditions.click($this.addrule);

		if (data.rules.length > 0) {
			$.each(data.rules,function (rid,rule) {
				$this.addrule(false,rid,rule);
			});
		}

		/** Add to DOM **/
		if (row.size() > 0) ui.insertAfter(row);
		else ui.prependTo('#taxrates-table');

		quickSelects();

		editing = $this;

	});

});