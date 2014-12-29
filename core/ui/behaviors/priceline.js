/*!
 * priceline.js - Priceline editor
 * Copyright © 2008-2010 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */

function Pricelines () {
	var $=jQuery,_ = this;
	_.idx = 0;
	_.row = new Object();
	_.variations = new Array();
	_.addons = new Array();
	_.linked = new Array();

	_.add = function (options,data,target,attachment) {

		if (!data) data = {'context':'product'};
		var key,p,targetkey,index;

		if (data.context == "variation") {
			key = xorkey(options);
			p = new Priceline(_.idx,options,data,target,attachment);
			_.row[key] = p;

			if (attachment) {
				targetkey = parseInt(target.optionkey.val(),10);
				index = $.inArray(targetkey,_.variations);
				if (index != -1) {
					if (attachment == "before") _.variations.splice(index,0,xorkey(p.options));
				 	else _.variations.splice(index+1,0,xorkey(p.options));
				}
			} else _.variations.push(xorkey(p.options));
		} else if (data.context == "addon") {
			p = new Priceline(_.idx,options,data,target,attachment);
			_.row[options] = p;
		} else if (data.context == "product") {
			p = new Priceline(0,options,data,target,attachment);
			_.row[0] = p;
		}

		$('#prices').val(_.idx++);
	};

	_.exists = function (key) {
		if (_.row[key]) return true;
		return false;
	};

	_.remove = function (row) {
		var index = $.inArray(row,_.variations);
		if (index != -1) _.variations.splice(index,1);

		_.row[row].row.remove(); // Remove UI
		delete _.row[row];		// Remove data
	};

	_.reorderVariation = function (key,options) {
		var variation = _.row[key],
			index = $.inArray(key,_.variations);

		variation.row.appendTo('#variations-pricing');
		variation.setOptions(options);

		if (index == -1) return;
		_.variations.splice(index,1);
		_.variations.push(xorkey(variation.options));
	};

	_.reorderAddon = function (id,pricegroup) {
		var addon = _.row[id];
		addon.row.appendTo(pricegroup);
	};

	_.updateVariationsUI = function (type) {
		var i,key,row,option;
		for (i in _.variations) {
			key = _.variations[i];
			if (!Pricelines.row[key]) {
				delete _.variations[i]; continue;
			}
			row = Pricelines.row[key];
			row.updateTabIndex(i);	// Re-number tab indexes
			if (type && type == "tabs") continue;
			row.unlinkInputs();			// Reset linking
			for (option in _.linked) {
				if ($.inArray(option,_.row[key].options) != -1) {
					if (!_.linked[option][key]) _.linked[option].push(key);
					_.row[key].linkInputs(option);
				}
			}
		}
	};

	_.linkVariations = function (option) {
		if (!option) return;
		for (var key in _.row) {
			if ($.inArray(option.toString(),_.row[key].options) != -1) {
				if (!_.linked[option]) _.linked[option] = new Array();
				_.linked[option].push(key);
				_.row[key].linkInputs(option);
			}
		}
	};

	_.unlinkVariations = function (option) {
		if (!option) return;
		if (!_.linked[option]) return;
		for (var row in _.linked[option]) {
			if (_.row[_.linked[option][row]])
				_.row[_.linked[option][row]].unlinkInputs(option);
		}
		_.linked.splice(option,1);
	};

	_.unlinkAll = function () {
		for (var key in _.row) {
			_.row[key].unlinkInputs();
		}
		_.linked.splice(0,1);
	};

	_.updateVariationLinks = function () {
		if (!_.linked) return;
		var key,option;
		for (key in _.row) {
			_.row[key].unlinkInputs();
		}
		for (option in _.linked) {
			_.linked[option] = false;
			_.linkVariations(option);
		}
	};

	_.allLinked = function () {
		if (_.linked[0]) return true;
		return false;
	};

	_.linkAll = function () {
		_.unlinkAll();
		_.linked = new Array();
		_.linked[0] = new Array();
		for (var key in _.row) {
			if (key == 0) continue;
			_.linked[0].push(key);
			_.row[key].linkInputs(0);
		}
	};

}

jQuery.fn.toggler = function (s,ui,f) {
	var $ = jQuery, $this = $(this);
	$this.closest('tr').on('change.toggle', $this, function () {
		if ($this.prop('checked')) { s.hide(); ui.show(); }
		else { s.show(); ui.hide(); }
	}).trigger('change.toggle');
	return this;
};

function Priceline (id,options,data,target,attachment) {
	var $ = jQuery,
		_ = this,
		tmp = template,
		controller = Pricelines,
		typeOptions = "",
		i,fn,heading,labelText,myid,context,optionids,sortorder,optionkey,type,
		dataCell,pricingTable,headingsRow,inputsRow;

	_.id = id;				// Index position in the Pricelines.rows array
	_.options = options;	// Option indexes for options linked to this priceline
	_.data = data;			// The data associated with this priceline
	_.label = false;		// The label of the priceline
	_.links = new Array();	// Option linking registry
	_.inputs = new Array();	// Inputs registry
	_.lasttype = false;		// Tracks the previous product type selected

	// Give this entry a unique runtime id
	i = _.id;

	// Build the interface
	fn = 'price['+i+']'; // Field name base

	_.row = $('<div id="row-'+i+'" class="priceline" />');
	if (attachment == "after") _.row.insertAfter(target);
	else if (attachment == "before") _.row.insertBefore(target);
	else _.row.appendTo(target);

	heading = $('<div class="pricing-label" />').appendTo(_.row);
	labelText = $('<label for="label-'+i+'" />').appendTo(heading);

	_.label = $('<input type="hidden" name="price['+i+'][label]" id="label-'+i+'" />')
		.appendTo(heading);
	$(_.row).on('change.label', '#label-'+i, function () { labelText.text($(this).val()); });

	if (!data.id) data.id = '';
	if (!data.product) data.product = product;
	if (!data.donation) data.donation = {'var':false,min:false};
	if (!data.dimensions) data.dimensions = {weight:0,height:0,width:0,length:0};

	$('<input type="hidden" name="'+fn+'[id]" id="priceid-'+i+'" value="'+data.id+'" />'+
		'<input type="hidden" name="'+fn+'[product]" id="product-'+i+'" value="'+data.product+'" />'+
		'<input type="hidden" name="'+fn+'[context]" id="context-'+i+'"/>'+
		'<input type="hidden" name="'+fn+'[optionkey]" id="optionkey-'+i+'" class="optionkey" />'+
		'<input type="hidden" name="'+fn+'[options]" id="options-'+i+'" value="" />'+
		'<input type="hidden" name="sortorder[]" id="sortorder-'+i+'" value="'+i+'" />').appendTo(heading);

	myid = $('#priceid-'+i);
	context = $('#context-'+i);
	optionids = $('#options-'+i);
	sortorder = $('#sortorder-'+i);
	optionkey = $('#optionkey-'+i);
	_.row.optionkey = optionkey;

	$(priceTypes).each(function (t,option) {
 		if ('addon' == data.context && 'Subscription' == option.label) return; // Prevent subscription addons [#1544]
		typeOptions += '<option value="'+option.value+'">'+option.label+'</option>';
	});
	type = $('<select name="price['+i+'][type]" id="type-'+i+'"></select>').html(typeOptions).appendTo(heading);

	if (data && data.label) {
		_.label.val(htmlentities(data.label)).change();
		type.val(data.type);
	}

	dataCell = $('<div class="pricing-ui clear" />').appendTo(_.row);
	pricingTable = $('<table/>').addClass('pricing-table').appendTo(dataCell);
	headingsRow = $('<tr/>').appendTo(pricingTable);
	inputsRow = $('<tr/>').appendTo(pricingTable);

	// Build individual fields
	_.price = function (price,tax) {
		var hd,ui;
		hd = $('<th><label for="price-'+i+'">'+PRICE_LABEL+'</label></th>').appendTo(headingsRow);
		ui = $('<td><input type="text" name="'+fn+'[price]" id="price-'+i+'" value="0" size="10" class="selectall money right" /><br />'+
					 '<input type="hidden" name="'+fn+'[tax]" value="on" /><input type="checkbox" name="'+fn+'[tax]" id="tax-'+i+'" value="off" />'+
					 '<label for="tax-'+i+'"> '+NOTAX_LABEL+'</label><br /></td>').appendTo(inputsRow);

		_.p = $('#price-'+i).val(asMoney(new Number(price || 0)));
		_.t = $('#tax-'+i).prop('checked', ( tax == "off" ));
	};

	_.saleprice = function (toggle,saleprice) {
		var hd,ui,dis;
		hd = $('<th><input type="hidden" name="'+fn+'[sale]" value="off" />'+
					'<input type="checkbox" name="'+fn+'[sale]" id="sale-'+i+'" value="on" />'+
					'<label for="sale-'+i+'"> '+SALE_PRICE_LABEL+'</label></th>').appendTo(headingsRow);
		ui = $('<td><span class="status">'+NOT_ON_SALE_TEXT+'</span><span class="ui">'+
					'<input type="text" name="'+fn+'[saleprice]" id="saleprice-'+i+'" size="10" class="selectall money right" />'+
					'</span></td>').appendTo(inputsRow);

		dis = ui.find('span.status');
		ui = ui.find('span.ui').hide();

		_.sp = $('#saleprice-'+i).val(asMoney(new Number(saleprice || 0)));
		_.spt = $('#sale-'+i).prop('checked',toggle == "on");
		_.toggler(_.spt,dis,ui,_.sp);

	};

	_.donation = function (price,tax,variable,minimum) {
		var hd,ui,hd2,ui2;
		hd = $('<th><label for="price-'+i+'"> '+AMOUNT_LABEL+'</label></th>').appendTo(headingsRow);
		ui = $('<td><input type="text" name="'+fn+'[price]" id="price-'+i+'" value="0" size="10" class="selectall money right" /><br />'+
					 '<input type="hidden" name="'+fn+'[tax]" value="on" /><input type="checkbox" name="'+fn+'[tax]" id="tax-'+i+'" value="off" />'+
					 '<label for="tax-'+i+'"> '+NOTAX_LABEL+'</label><br /></td>').appendTo(inputsRow);

		_.p = $('#price-'+i).val(asMoney(new Number(price || 0)));
		_.t = $('#tax-'+i).prop('checked',tax == "on"?false:true);

		hd2 = $('<th />').appendTo(headingsRow);
		ui2 = $('<td width="80%"><input type="hidden" name="'+fn+'[donation][var]" value="off" />'+
					'<input type="checkbox" name="'+fn+'[donation][var]" id="donation-var-'+i+'" value="on" />'+
					'<label for="donation-var-'+i+'"> '+DONATIONS_VAR_LABEL+'</label><br />'+
					'<input type="hidden" name="'+fn+'[donation][min]" value="off" />'+
					'<input type="checkbox" name="'+fn+'[donation][min]" id="donation-min-'+i+'" value="on" />'+
					'<label for="donation-min-'+i+'"> '+DONATIONS_MIN_LABEL+'</label><br /></td>').appendTo(inputsRow);

		_.dv = $('#donation-var-'+i).prop('checked',variable == "on");
		_.dm = $('#donation-min-'+i).prop('checked',minimum == "on");
	};

	_.shipping = function (toggle,dimensions,fee) {
		var hd,ui,dis,inf,dc,dw,dl,dwd,dh,dv,nf = getCurrencyFormat();
		nf.precision = '2';

		hd = $('<th><input type="hidden" name="'+fn+'[shipping]" value="off" /><input type="checkbox" name="'+fn+'[shipping]" id="shipping-'+i+'" value="on" /><label for="shipping-'+i+'"> '+SHIPPING_LABEL+'</label></th>').appendTo(headingsRow);
		ui = $('<td><span class="status">'+FREE_SHIPPING_TEXT+'</span>'+
					'<span class="ui"><input type="text" name="'+fn+'[dimensions][weight]" id="weight-'+i+'" size="8" class="selectall right" />'+
					'<label for="weight-'+i+'" id="weight-label-'+i+'" title="'+WEIGHT_LABEL+'"> '+WEIGHT_LABEL+((weightUnit)?' ('+weightUnit+')':'')+'</label><br /><span class="dimui"></span>'+
					'<input type="text" name="'+fn+'[shipfee]" id="shipfee-'+i+'" size="8" class="selectall money right" />'+
					'<label for="shipfee-'+i+'" title="'+SHIPFEE_XTRA+'"> '+SHIPFEE_LABEL+'</label><br />'+
					'</span></td>').appendTo(inputsRow);

		dis = ui.find('span.status');
		inf = ui.find('span.ui').hide();
		dui = ui.find('.dimui');

		_.w = $('#weight-'+i).val(formatNumber(new Number(dimensions.weight || 0),nf,true));

		inf.on('change.value', '#weight-'+i, function () {
			this.value = formatNumber(isNaN(this.value)?0:this.value,nf,true);
		});

		_.fee = $('#shipfee-'+i);
		_.fee.val(asMoney(new Number(fee || 0)));

		_.st = hd.find('#shipping-'+i).prop('checked',(toggle == "off"?false:true));
		_.toggler(_.st,dis,inf,_.w);

		if (dimensionsRequired) {
			dv = function () {
				$this.val(formatNumber($this.val(),nf,true));
			};

			$('#weight-label-'+i).html(' '+dimensionUnit+'<sup>3</sup>/'+weightUnit);
			dc = $('<div class="dimensions">'+
				'<div class="inline">'+
				'<input type="text" name="'+fn+'[dimensions][weight]" id="dimensions-weight-'+i+'" size="4" class="selectall right weight" />'+
				(weightUnit?'<label>'+weightUnit+'&nbsp;</label>':'')+'<br />'+
				'<label for="dimensions-weight-'+i+'" title="'+WEIGHT_LABEL+'"> '+WEIGHT_LABEL+'</label>'+
				'</div>'+
				'<div class="inline">'+
				'<input type="text" name="'+fn+'[dimensions][length]" id="dimensions-length-'+i+'" size="4" class="selectall right" />'+
				'<label> x </label><br />'+
				'<label for="dimensions-length-'+i+'" title="'+LENGTH_LABEL+'"> '+LENGTH_LABEL+'</label>'+
				'</div>'+
				'<div class="inline">'+
				'<input type="text" name="'+fn+'[dimensions][width]" id="dimensions-width-'+i+'" size="4" class="selectall right" />'+
				'<label> x </label><br /><label for="dimensions-width-'+i+'" title="'+WIDTH_LABEL+'"> '+WIDTH_LABEL+'</label>'+
				'</div>'+
				'<div class="inline">'+
				'<input type="text" name="'+fn+'[dimensions][height]" id="dimensions-height-'+i+'" size="4" class="selectall right" />'+
				'<label>'+dimensionUnit+'</label><br />'+
				'<label for="dimensions-height-'+i+'" title="'+HEIGHT_LABEL+'"> '+HEIGHT_LABEL+'</label>'+
				'</div>'+
				'</div>').hide().appendTo(dui);

			if (!(dimensions instanceof Object))
				dimensions = {'weight':0,'length':0,'width':0,'height':0};

			dc.on('change.value','input', dv).trigger('change.value');

			_.dw = $('#dimensions-weight-'+i).val(new Number(dimensions.weight || 0));
			_.dl = $('#dimensions-length-'+i).val(new Number(dimensions.length || 0));
			_.dwd = $('#dimensions-width-'+i).val(new Number(dimensions.width || 0));
			_.dh = $('#dimensions-height-'+i).val(new Number(dimensions.height || 0));

			function volumeWeight () {
				var d = 0, w = 0;
				dc.find('input').each(function (id,dims) {
					if ($(dims).hasClass('weight')) { w = asNumber(dims.value); }
					else {
						if (d == 0) d = asNumber(dims.value);
						else d *= asNumber(dims.value);
					}
				});
				if (!isNaN(d/w)) _.w.val((d/w)).trigger('change.value');
			}

			function toggleDimensions () {
				_.w.toggleClass('extoggle');
				dc.toggle(); _.dw.focus();
				volumeWeight();
			}


			_.st.change(function () { // Make sure to hide the dimensions panel if shipping is disabled
				if (!$(this).prop('checked')) dc.hide();
			});

			_.dh.blur(toggleDimensions);
			_.w.click(toggleDimensions).attr('readonly',true);
			volumeWeight();

		}

	};

	_.inventory = function (toggle,stock,sku) {
		var hd,ui,dis;
		hd = $('<th><input type="hidden" name="'+fn+'[inventory]" value="off" />'+
					'<input type="checkbox" name="'+fn+'[inventory]" id="inventory-'+i+'" value="on" />'+
					'<label for="inventory-'+i+'"> '+INVENTORY_LABEL+'</label></th>').appendTo(headingsRow);
		ui = $('<td><span class="status"></span><input type="text" name="'+fn+'[sku]" id="sku-'+i+'" size="8" title="'+SKU_XTRA+'" class="selectall" />'+
					'<label for="sku-'+i+'" title="'+SKU_LABEL_HELP+'"> '+SKU_LABEL+'</label><br />'+
					'<span class="ui"><input type="text" name="'+fn+'[stocked]" id="stock-'+i+'" size="8" class="selectall right" />'+
					'<label for="stock-'+i+'"> '+IN_STOCK_LABEL+'</label>'+
					'</span></td>').appendTo(inputsRow);

		dis = ui.find('span.status');
		ui = ui.find('span.ui').hide();

		_.stock = $('#stock-'+i).val(new Number(stock || 0));

		ui.on('change.value', '#stock-'+i, function () {
			this.value = isNaN(this.value) ? 0 : new Number(this.value).roundFixed(0);
		});

		_.sku = $('#sku-'+i).val(sku);
		_.it = hd.find('#inventory-'+i).prop('checked',toggle == "on")
		_.toggler(_.it,dis,ui,_.stock);
	};

	_.download = function (d) {
		var hd,ui,hd2,fc;
		hd = $('<th><label for="download-'+i+'">'+PRODUCT_DOWNLOAD_LABEL+'</label></th>').appendTo(headingsRow);
		ui = $('<td width="31%"><input type="hidden" name="'+fn+'[downloadpath]" id="download_path-'+i+'"/><input type="hidden" name="'+fn+'[downloadfile]" id="download_file-'+i+'"/><div id="file-'+i+'" class="status">'+NO_DOWNLOAD+'</div></td>').appendTo(inputsRow);

		hd2 = $('<td rowspan="2" class="controls" width="75"><button type="button" class="button-secondary" style="white-space: nowrap;" id="file-selector-'+i+'"><small>'+SELECT_FILE_BUTTON_TEXT+'&hellip;</small></button></td>').appendTo(headingsRow);

		_.file = $('#file-'+i);
		_.selector = $('#file-selector-'+i).FileChooser(i,_.file);

		if (d && d.id) {
			fc = d.mime.replace('/',' ');
			_.file.attr('class','file').html('<div class="icon shoppui-file '+fc+'"></div>'+d.name+'<br /><small>'+readableFileSize(d.size)+'</small>').click(function () {
				window.location.href = adminurl+"admin.php?src=download&shopp_download="+d.id;
			});
		}
	};

	_.recurring = function (r) {
		var hd,ui,hd2,ui2,ints,n,cycs = '<option value="0">&infin;</option>',pp,ps;

		for(n = 1; n < 31; n++) {
			ints += '<option value="'+n+'">'+n+'</option>';
			if (n > 1) cycs += '<option value="'+n+'">'+n+'</option>';
		}

		$(billPeriods[0]).each(function (n,option) { pp += '<option value="'+option.value+'">'+option.label+'</option>'; });
		$(billPeriods[1]).each(function (n,option) { ps += '<option value="'+option.value+'">'+option.label+'</option>'; });

		hd2 = $('<th><input type="hidden" name="'+fn+'[recurring][trial]" value="off" />'+
					'<input type="checkbox" name="'+fn+'[recurring][trial]" id="trial-'+i+'" />'+
					'<label for="trial-'+i+'"> '+TRIAL_LABEL+'</label></th>').appendTo(headingsRow);

		ui2 = $('<td><span class="status">'+NOTRIAL_TEXT+'</span>'+
					'<span class="ui"><select name="'+fn+'[recurring][trialint]" id="trialint-'+i+'">'+ints+'</select>'+
					'<select name="'+fn+'[recurring][trialperiod]" id="trialperiod-'+i+'" class="period">'+pp+'</select><br />'+
					'<input type="text" name="'+fn+'[recurring][trialprice]" id="trialprice-'+i+'" value="0" size="10" class="selectall money right" />'+
					'<label for="trialprice-'+i+'">&nbsp;'+PRICE_LABEL+'</label></span></td>').appendTo(inputsRow);

		hd = $('<th><label for="billcycle-'+i+'"> '+BILLCYCLE_LABEL+'</label></th>').appendTo(headingsRow);
		ui = $('<td>'+
					'<select name="'+fn+'[recurring][interval]" id="interval-'+i+'">'+ints+'</select>'+
					'<select name="'+fn+'[recurring][period]" id="period-'+i+'" class="period">'+pp+'</select><br />'+
					'<select name="'+fn+'[recurring][cycles]" id="cycles-'+i+'">'+cycs+'</select>'+
					'<label for="cycles'+i+'">&nbsp;'+TIMES_LABEL+'</label></td>').appendTo(inputsRow);


		dis = ui2.find('span.status');
		ui = ui2.find('span.ui').hide();

		// Defaults
		if (!r) r = {period:1,interval:'d',cycles:0,trialperiod:1,trialint:1,trialprice:0.0};

		_.period = $('#period-'+i).val(r.period);
		_.interval = $('#interval-'+i).val(r.interval).change(function () {
			var $this=$(this),s = _.period.val();
			if ($this.val() == 1) _.period.html(ps);
			else _.period.html(pp);
			_.period.val(s);
		}).change();
		_.cycles = $('#cycles-'+i).val(r.cycles);

		_.trialperiod = $('#trialperiod-'+i).val(r.trialperiod);
		_.trialint = $('#trialint-'+i).val(r.trialint).change(function () {
			var $this=$(this),s = _.trialperiod.val();
			if ($this.val() == 1) _.trialperiod.html(ps);
			else _.trialperiod.html(pp);
			_.trialperiod.val(s);
		}).change();
		_.trialprice = $('#trialprice-'+i).val(asMoney(new Number(r.trialprice)));

		_.trial = hd2.find('#trial-'+i).prop('checked',(r.trial == "on"?true:false)).toggler(dis,ui,_.trialint);

	};

	_.toggler = function (toggle, off, ui, focal) {
		toggle.parent().on('change.toggler', { toggle: toggle, off: off, ui: ui, focal: focal }, function (e) {
			var toggle = e.data.toggle, off = e.data.off, ui = e.data.ui, focal = e.data.focal;
			if (toggle.prop('checked')) { off.hide(); ui.show(); focal.select()}
			else { off.show(); ui.hide(); }
		}).trigger('change.toggler');

	}

	_.memberlevel = function () {
		var hd,ui,memberships,mo;

		memberships = ['Basic','Silver','Gold','Platinum'];
		$(memberships).each(function (n,option) { mo += '<option value="'+option+'">'+option+'</option>'; });

		hd = $('<th><label for="membership-'+i+'"> '+MEMBERSHIP_LABEL+'</label></th>').appendTo(headingsRow);
		ui = $('<td><select name="'+fn+'[membership]" id="membership-'+i+'" class="membership">'+mo+'</select></td>').appendTo(inputsRow);
	};

	_.Shipped = function (data) {
		_.price(data.price,data.tax);
		_.saleprice(data._sale,data.saleprice);
		_.shipping(data.shipping,data.dimensions,data.shipfee);
		if (!tmp) _.inventory(data.inventory,data.stock,data.sku);
	};

	_.Virtual = function (data) {
		_.price(data.price,data.tax);
		_.saleprice(data.sale,data.saleprice);
		if (!tmp) _.inventory(data.inventory,data.stock,data.sku);
	};

	_.Download = function (data) {
		_.price(data.price,data.tax);
		_.saleprice(data.sale,data.saleprice);
		if (!tmp) _.download(data.download);
	};

	_.Donation = function (data) {
		_.donation(data.price,data.tax,data.donation['var'],data.donation['min']);
	};

	_.Subscription = function (data) {
		_.price(data.price,data.tax);
		_.saleprice(data.sale,data.saleprice);
		_.recurring(data.recurring);
	};

	_.Membership = function (data) {
		_.price(data.price,data.tax);
		_.saleprice(data.sale,data.saleprice);
		_.recurring();
		if (!tmp) _.memberlevel();
	};

	// Alter the interface depending on the type of price line
	type.on('change.value',function () {
		headingsRow.empty();
		inputsRow.empty();
		var ui = type.val();

		if (ui == "Shipped") _.Shipped(data);
		if (ui == "Virtual") _.Virtual(data);
		if (ui == "Download") _.Download(data);
		if (ui == "Donation") _.Donation(data);
		if (ui == "Subscription") _.Subscription(data);
		if (ui == "Membership") _.Membership(data);

		// Global behaviors
		moneyInputs(inputsRow);
		quickSelects(inputsRow);

	}).trigger('change.value');

	// Setup behaviors
	_.disable = function () { _.lasttype = (type.val())?type.val():false; type.val('N/A').trigger('change.value'); };
	_.enable = function () { if (_.lasttype) type.val(_.lasttype).trigger('change.value'); };

	// Set the context for the db
	if (data && data.context) context.val(data.context);
	else context.val('product');

	_.setOptions = function(options) {
		var update = false;
		if (options) {
			if (options != _.options) update = true;
			_.options = options;
		}
		if (context.val() == "variation")
			optionkey.val(xorkey(_.options));
		if (update) _.updateLabel();
	};

	_.updateKey = function () {
		optionkey.val(xorkey(_.options));
	};

	_.updateLabel = function () {
		var type = context.val(),
			string = "",
			ids = "";
		if (_.options) {
			if (type == "variation") {
				$(_.options).each(function(index,id) {
					if (string == "") string = $(productOptions[id]).val();
					else string += ", "+$(productOptions[id]).val();
					if (ids == "") ids = id;
					else ids += ","+id;
				});
			}
			if (type == "addon") {
				string = $(productAddons[_.options]).val();
				ids = _.options;
			}
		}
		if (string == "") string = DEFAULT_PRICELINE_LABEL;
		_.label.val(htmlentities(string)).trigger('change.label');
		optionids.val(ids);
	};

	_.updateTabIndex = function (row) {
		row = new Number(row);
		$.each(_.inputs,function(i,input) {
			$(input).attr('tabindex',((row+1)*100)+i);
		});
	};

	_.linkInputs = function (option) {
		_.links.push(option);
		$.each(_.inputs,function (i,input) {
			if (!input) return;
			var type = "change.linkedinputs",
				elem = $(input);
			if (elem.attr('type') == "checkbox") type = "click.linkedinputs";
			$(input).bind(type,function () {
				var value = $(this).val(),
					checked = $(this).attr('checked');
				$.each(_.links,function (l,option) {
					$.each(controller.linked[option],function (id,key) {
						if (key == xorkey(_.options)) return;
						if (!controller.row[key]) return;
						if (elem.attr('type') == "checkbox")
							$(controller.row[key].inputs[i]).attr('checked',checked);
						else $(controller.row[key].inputs[i]).val(value);
						$(controller.row[key].inputs[i]).trigger('change.value');
					});
				});
			});
		});
	};

	_.unlinkInputs = function (option) {
		if (option !== false) {
			index = $.inArray(option,_.links);
			_.links.splice(index,1);
		}
		$.each(_.inputs,function (i,input) {
			if (!input) return;
			var type = "blur.linkedinputs";
			if ($(input).attr('type') == "checkbox") type = "click.linkedinputs";
			$(input).unbind(type);
		});
	};

	if (type.val() != "N/A")
		_.inputs = new Array(
			type,_.p,_.t,_.spt,_.sp,_.dv,_.dm,
			_.st,_.w,_.dw,_.dl,_.dwd,_.dh,_.fee,
			_.it,_.stock,_.sku,_.period,_.interval,
			_.cycles,_.trialperiod,_.trialint,
			_.trialprice,_.trial);

	_.updateKey();
	_.updateLabel();

}