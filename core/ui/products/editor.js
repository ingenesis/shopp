/*!
 * editor.js - Product editor behaviors
 * Copyright © 2008-2010 by Ingenesis Limited
 * Licensed under the GPLv3 (or later) {@see license.txt}
 **/

var Pricelines = new Pricelines(),
 	productOptions = new Array(),
 	productAddons = new Array(),
 	optionMenus = new Array(),
 	addonGroups = new Array(),
 	addonOptionsGroup = new Array(),
 	selectedMenuOption = false,
 	detailsidx = 1,
 	variationsidx = 1,
 	addon_group_idx = 1,
 	addonsidx = 1,
 	optionsidx = 1,
 	pricingidx = 1,
 	fileUploader = false,
 	changes = false,
 	saving = false,
 	flashUploader = false,
	template = false,
 	fileUploads = false,
	changesMade = false,
	isSave = false;

jQuery(document).ready(function($) {
	var title = $('#title'),
		titlePrompt = $('#title-prompt-text'),
		publishfields = $('.publishdate');

	// Give the product name initial focus
	title.bind('focus keydown',function () {
		titlePrompt.hide();
	}).blur(function () {
		if (title.val() == '') titlePrompt.show();
		else titlePrompt.hide();
	});

	if (!product) {
		title.focus();
		titlePrompt.show();
	}

	// Init postboxes for the editor
	postboxes.add_postbox_toggles(screenid);
	// close postboxes that should be closed
	$('.if-js-closed').removeClass('if-js-closed').addClass('closed');

	$('.postbox a.help').click(function () {
		$(this).colorbox({iframe:true,open:true,innerWidth:768,innerHeight:480,scrolling:false});
		return false;
	});

	// Handle publishing/scheduling
	$('<div id="publish-calendar" class="calendar"></div>').appendTo('#wpwrap').PopupCalendar({
		m_input:$('#publish-month'),
		d_input:$('#publish-date'),
		y_input:$('#publish-year'),
		autoinit:true,
		title:calendarTitle,
		startWeek:startWeekday
	});

	$('#schedule-toggle').click(function () {
		$('#scheduling').slideToggle('fast',function () {
			if ($(this).is(':visible')) publishfields.removeAttr('disabled');
			else publishfields.attr('disabled',true);
		});
	});
	$('#scheduling').hide();
	publishfields.attr('disabled',true);

	$('#published').change(function () {
		if ($(this).prop('checked')) $('#publish-status,#schedule-toggling').show();
		else $('#publish-status,#schedule-toggling,#scheduling').hide();
	}).change();

	$('#process-time').change(function () {
		var pt = $('#processing');
		if ($(this).prop('checked')) pt.slideDown('fast');
		else pt.hide();
	}).change();

	// Setup the slug editor
	editslug = new SlugEditor(product,'product');

	// Load up existing specs & setup the add new button
	if (specs) $.each(specs,function () { addDetail(this); });
	$('#addDetail').click(function() { addDetail(); });

	// Initialize file uploads before the pricelines
	fileUploads = new FileUploader('flash-upload-file',$('#ajax-upload-file'));

	// Initalize the base price line
	basePrice = $(prices).get(0);
	if (basePrice && basePrice.context == "product") Pricelines.add(false,basePrice,'#product-pricing');
	else Pricelines.add(false,false,'#product-pricing');

	// Initialize variations
	$('#variations-setting').bind('toggleui',variationsToggle).click(function() {
		$(this).trigger('toggleui');
	}).trigger('toggleui');
	loadVariations(!options || (!options.v && !options.a)?options:options.v,prices);

	$('#addVariationMenu').click(function() { addVariationOptionsMenu(); });
	$('#linkOptionVariations').click(linkVariationsButton).change(linkVariationsButtonLabel);

	// Initialize Add-ons
	$('#addons-setting').bind('toggleui',addonsToggle).click(function () {
		$(this).trigger('toggleui');
	}).trigger('toggleui');
	$('#newAddonGroup').click(function() { newAddonGroup(); });
	if (options && options.a) loadAddons(options.a,prices);

	imageUploads = new ImageUploads($('#image-product-id').val(),'product');

	// Setup categories
	categories();
	tags();
	quickSelects();

	$('#product').change(function () { changes = true; }).unbind('submit').submit(function(e) {
		e.stopPropagation();
		var url = $('#product').attr('action').split('?'),
			action = url[0]+"?"+$.param(request); 		// Add our workflow request parameters before submitting
		$('#product')[0].setAttribute('action',action); // More compatible for **stupid** IE
		saving = true;
		return true;
	});

	$('#prices-loading').remove();

	// Try to detect if changes are made to any fields on the page - other than the MCE editor,
	// we'll deal with that using TinyMCE's isDirty() function
	$("input").on("change", function() {
		changesMade = true;
		$(this).off("change"); // We don't need this anymore
	});

	// We don't need an AYS dialog when saving
	$("input[name='save']").click(function() { isSave = true });

	// Confirm navigation dialog (avoid people accidentally losing work upon navigation)
	window.onbeforeunload = function() {
		var editor = (typeof(tinymce) != 'undefined') ? tinymce.activeEditor : false;
		if (!isSave && (changesMade || (editor && editor.isDirty() && !editor.isHidden())) )
			return $msg.confirm;
	}
});

function categories () {
	var $=jQuery;
	$('#product .category-metabox').each(function () {
		var $this = $(this),
			taxonomy = $(this).attr('id').split('-').slice(1).join('-'),
			setting = taxonomy+'_tab',
			addui = $this.find('div.new-category').hide(),
			tabui = $this.find('ul.category-tabs'),
			tabs = tabui.find('li a').click(function (e) {
				e.preventDefault();
				var $this = $(this),
					href = $this.attr('href');
				$this.parent().addClass('tabs').siblings('li').removeClass('tabs');
				$(href).show().siblings('div.tabs-panel').hide();
				if ($this.parent().hasClass('new-category')) {
					addui.slideDown('fast',function () {
						addui.find('input').focus();
					});
				} else addui.hide();
			}),

			catAddBefore = function( s ) {
				if ( !$('#new-'+taxonomy+'-name').val() )
					return false;
				s.data += '&' + $( ':checked', '#'+taxonomy+'-checklist' ).serialize();
				$( '#' + taxonomy + '-add-submit' ).prop( 'disabled', true );
				return s;
			},

			catAddAfter = function( r, s ) {
				var sup, drop = $('#new'+taxonomy+'_parent');

				$( '#' + taxonomy + '-add-submit' ).prop( 'disabled', false );
				if ( 'undefined' != s.parsed.responses[0] && (sup = s.parsed.responses[0].supplemental.newcat_parent) ) {
					drop.before(sup);
					drop.remove();
				}
			};

			$('#' + taxonomy + '-checklist').wpList({
				alt: '',
				response: taxonomy + '-ajax-response',
				addBefore: catAddBefore,
				addAfter: catAddAfter
			});

			tabui.find('li.tabs a').click();

			$('#' + taxonomy + '-checklist li.popular-category :checkbox, #' + taxonomy + '-checklist-pop :checkbox').on( 'click', function(){
				var t = $(this), c = t.is(':checked'), id = t.val();
				if ( id && t.parents('#taxonomy-'+taxonomy).length )
					$('#in-' + taxonomy + '-' + id + ', #in-popular-' + taxonomy + '-' + id).attr( 'checked', c );
			});

	});

	// Handles toggling a category on/off when the category is pre-existing
	$('.category-metabox input[type=checkbox]').change(function () {
		if (!this.checked) return true;
		var id,details = new Array();

		// Build current list of spec labels
		$('#details-menu').children().children().find('input.label').each(function(id,item) {
			details.push($(item).val());
		});

		id = $(this).val();
		// Load category spec templates
		$.getJSON(spectemp_url+'&action=shopp_spec_template&category='+id,function (speclist) {
			if (!speclist) return true;
			for (id in speclist) {
				speclist[id].add = true;
				if (details.toString().search(speclist[id]['name']) == -1) addDetail(speclist[id]);
			}
		});

		// Load category variation option templates
		$.getJSON(opttemp_url+'&action=shopp_options_template&category='+id,function (t) {
			if ( ! (t && (t.options && t.prices) && ( Object.keys(t.options).length > 0 || Object.keys(t.prices).length > 0 ))) return true;

			var variant_setting = $('#variations-setting'),
				options = !t.options.v?t.options:t.options.v,
				added = false;

			if (!variant_setting.attr('checked'))
				variant_setting.attr('checked',true).trigger('toggleui');

			if (optionMenus.length > 0) {
				$.each(options,function (tid,tm) {
					if (!(tm && tm.name && tm.options)) return;
					if (menu = optionMenuExists(tm.name)) {
						added = false;
						$.each(tm.options,function (i,o) {
							if (!(o && o.name)) return;
							if (!optionMenuItemExists(menu,o.name)) {
								menu.addOption(o);
								added = true;
							}
						});
						if (added) addVariationPrices();
					} else {
						// Initialize as new menu items
						delete tm.id;
						$.each(tm.options,function (i,o) {
							if (!(o && o.name)) return;
							// Remove the option ID so the option will be built into the
							// the variations permutations
							delete o.id;
						});
						addVariationOptionsMenu(tm);
					}

				});
			} else loadVariations(options,t.prices);

		});
	});
}

function tags () {
	var $=jQuery;
	$('#product .tags-metabox').each(function () {
		var $this = $(this),
			taxonomy = $(this).attr('id').split('-').slice(1).join('-'),
			textarea = $this.find('.tags'),
			tags = textarea.val().split(','),
			selector = new SearchSelector({
				source:taxonomy,
				parent:$this,
				url:tagsugg_url,
				fieldname:'tax_input['+taxonomy+']',
				label:TAG_SEARCHSELECT_LABEL,
				classname:'tags',
				freeform:true,
				autosuggest:'shopp_popular_tags'
			});

		textarea.val('');
		$.each(tags,function (id,tag) {
			if (tag.length == 0) return;
			selector.ui.prepend(selector.newItem('',tag));
		});
	});
}
