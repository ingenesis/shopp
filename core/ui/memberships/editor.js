/*!
 * editor.js - Membership editor behaviors
 * Copyright © 2011 by Ingenesis Limited. All rights reserved.
 * Licensed under the GPLv3 {@see license.txt}
 */

jQuery(document).ready( function() {
	var $=jQuery,stages = [],r_ui = $('#rules'),rulecount = 0;

	postboxes.add_postbox_toggles('shopp_page_shopp-memberships');
	// close postboxes that should be closed
	$('.if-js-closed').removeClass('if-js-closed').addClass('closed');

	$('.postbox a.help').click(function () {
		$(this).colorbox({iframe:true,open:true,innerWidth:768,innerHeight:480,scrolling:false});
		return false;
	});

	function Rule (parent,content,access,stage,data) {
		var $=jQuery,
			_ = this,
			id = rulecount++,
			accessMenu = false,
			label = rule_types[content],
			source = rule_groups[content.substr(0,content.indexOf('_'))],
			panel = $.tmpl('panelUI',{label:source+' '+label,type:'rule '+content}),
			ui = panel.find('div.ui'),

			deleteButton = panel.find('button.delete').hide().click(function () {
				if (!confirm(DELETE_RULE_PROMPT)) return false;
				parent.trigger('rule-change',[content,accessMenu.find('option:selected').attr('class'),true]);
				panel.fadeRemove();
			}),

			panelLabel = panel.find('div.label').hover(function () {
				deleteButton.show();
			},function () {
				deleteButton.fadeOut('fast');
			}),

			accessMenu = $.tmpl('accessMenu',{name:'stages['+stage+'][rules]['+content+']['+id+'][access]'}).appendTo(ui)
				.find('select.access').change(function () {

					var selectedClass = accessMenu.find('option:selected').attr('class');
					if (accessMenu.val().indexOf('-all') == -1) selector.ui.show();
					else selector.ui.hide();
					parent.trigger('rule-change',[content]);

				}),

			selector = new SearchSelector(
				content,ui,
				sugg_url,
				'stages['+stage+'][rules]['+content+']['+id+'][content]',
				'Type the name of the '+source+' '+label+' to add.',
				'membership'
			);

			if (access !== false) accessMenu.val(access);
			else accessMenu.val;

			if (data) { // Load content items
				$.each(data,function(id,label) {
					selector.ui.prepend(selector.newItem(id,label));
				});
			}

			if (!access) access = accessMenu.val();

			panel.appendTo(parent);
			if (!data) accessMenu.change();

		return this;
	}

	function StagePanel (parent,data) {
		stages.push(this);

		var registry = {},
			id = stages.length,
			labeling = (stages.length > 1?STAGES_LABEL:STAGE_LABEL),
			panel = $.tmpl('panelUI',{type:'stage'}),
			recordid = (typeof data == 'undefined' || typeof data.id == 'undefined'?'':data.id),
			controls = $.tmpl('stagePanelControls',{index:id,id:recordid}).appendTo(panel),
			ui = panel.find('div.ui'),
			rules = $('<ul/>').appendTo(ui),
			panelLabel = panel.find('div.label'),
			addRule = function (type,access) {
				new Rule(rules,type,false,id);
			},

			contentMenu = panel.find('select.content').change(function () {
				var $this = $(this);
				addRule($this.val());
				$this.val('');
			}),

			scheduling = controls.find('span.schedule').hide(),

			periodMenu = panel.find('select.period'),
			intMenu = panel.find('select.interval').change(function () {
				var $this = $(this),
					periods = bill_periods[0],
					firstLabel = periodMenu.find('option:first').html(),
					periodSelected = periodMenu.val();

				if ($this.val() == '1')	periods = bill_periods[1];

				if (firstLabel == periods[0].label) return;

				// Plurality has changed, rebuild the period menu with updated labels
				periodMenu.html('');
				$.tmpl('billPeriodOptions',periods).appendTo(periodMenu);
				periodMenu.val(periodSelected);

			}).change(),

			advance = panel.find('input.advance').change(function () {
				if (advance.attr('checked')) scheduling.show();
				else scheduling.hide();
			}).click(function () {
				if (advance.attr('checked') &&
					!panel.next().get(0)) new StagePanel(parent);
			}),

			deleteButton = panelLabel.find('button.delete').hide().click(function () {
				if (!confirm(DELETE_GROUP_PROMPT)) return false;
				stages.splice(id-1,1);
				panel.fadeRemove('fast',function () {
					if (stages.length > 1)
						parent.find('li.stage').addClass('advance').last().removeClass('advance');
					else parent.removeClass('steps').find('li.stage:last').removeClass('advance');
					parent.find('li.stage div.label').trigger('relabel');
				});
			}),

			load = function (data) {
				var content = [];
				$.each(data,function (type,rulesets) {
					if ('content' == type) content = rulesets;
					if ('rules' != type) return;
					$.each(rulesets,function(content_type,access) {
						$.each(access,function (r,rule) {
							new Rule(rules,rule.name,rule.value,id,content[rule.id]);
						});
					});
					rules.trigger('rule-change',[type]);
					content = [];
				});
				if (data.settings) {
					$.each(data.settings,function (name,value) {
						var input = controls.find('.'+name);
						if (input.is('input[type=checkbox]') && value == input.val())
							input.attr('checked',true);
						if (input.is('select')) input.val(value);
						input.change();
					});
				}

			};

			panelLabel.hover(function () {
				if (panel.prev().get(0))
					deleteButton.show();
			},function () {
				deleteButton.fadeOut('fast');
			});

			rules.bind('rule-change',function (e,content,remove) {
				var menus = $(this).find('li.'+content+' div.ui select.access'),
					active = [],
					reselected = false;

				// when an access rule changes
				// iterate through each of the menus of that type to collect what setting classes are in use
				menus.each(function(id,menu) {
					var selected = $(menu).find('option:selected').attr('class');
					if ($.inArray(selected,active) == -1)
						active.push(selected);
				});

				// reset the menu state to full enabled
				menus.find('option').attr('disabled',false);

				// for each menu, disable appropriate class of options
				menus.each(function(id,menu) {
					var $menu = $(menu),
						index = id==0?1:0,
						disableClass = active[index];

					if (!disableClass) return;
					$menu.find('option.'+disableClass).attr('disabled',true);
					if ($menu.find('option:selected').attr('disabled')) {
						$menu.find('option:enabled:first').attr('selected',true);
						reselected = true;
					}
				});

				// If both option classes are used, disable content type from content menu
				if (active.length == 2)
					contentMenu.find('option[value='+content+']').attr('disabled',true);
				else contentMenu.find('option[value='+content+']').attr('disabled',false);

				// If a different option was selected because of disabling options
				// Rerun the entire rule change behavior to capture new active options
				if (reselected) rules.trigger('rule-change',[content]);
			});

			panelLabel.bind('relabel',function () {
				var label = (stages.length > 1?STAGES_LABEL:STAGE_LABEL);
				panelLabel.find('label').html(label);
			}).trigger('relabel');

			if (stages.length > 1) {
				parent.addClass('steps')
					.find('li.stage').addClass('advance')
					.find('div.label').trigger('relabel');
			}

			if (data) load(data);

		panel.appendTo(parent).parent().sortable('refresh');
		advance.change();

	}

	$.template('panelUI',$('#panelUI'));
	$.template('stagePanelControls',$('#stagePanelControls'));
	$.template('billPeriodOptions',$('#billPeriodOptions'));
	$.template('accessMenu',$('#accessMenu'));

	$('#add-stage').click(function () {
		new StagePanel(r_ui);
	});

	if (rules) {
		$.each(rules,function (i,data) {
			new StagePanel(r_ui,data);
		});
	} else new StagePanel(r_ui);

	r_ui.sortable({'axis':'y','handle':'div.label','update':function (e,ui) {
		if (stages.length > 1) {
			r_ui.find('li.stage').addClass('advance')
				.last().removeClass('advance')
					.find('input.advance').attr('checked',false).change();
		}
	}});
});