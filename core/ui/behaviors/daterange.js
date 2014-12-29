/*!
 * daterange.js - Date range field behavior
 * Copyright © 2013 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */
function DateRange (menu, start, end, container) {
	var $ = jQuery,
		_ = this,
		$this = $(_),
		range = $(menu),
		dates = $(container),

		start = $(start).change(_.formatDate),
		StartCalendar = $('<div id="start-calendar" class="calendar"></div>').appendTo('#wpwrap').PopupCalendar({
			scheduling:false,
			input:start
		}).bind('calendarSelect',function () {
			range.val('custom');
		}),
		end = $(end).change(_.formatDate),
		EndCalendar = $('<div id="end-calendar" class="calendar"></div>').appendTo('#wpwrap').PopupCalendar({
			scheduling:true,
			input:end,
			scheduleAfter:StartCalendar
		}).bind('calendarSelect',function () {
			range.val('custom');
		});

	_.formatDate = function (e) {
		if (this.value == "") match = false;
		if (this.value.match(/^(\d{6,8})/))
			match = this.value.match(/(\d{1,2}?)(\d{1,2})(\d{4,4})$/);
		else if (this.value.match(/^(\d{1,2}.{1}\d{1,2}.{1}\d{4})/))
			match = this.value.match(/^(\d{1,2}).{1}(\d{1,2}).{1}(\d{4})/);
		if (match) {
			date = new Date(match[3],(match[1]-1),match[2]);
			$(this).val((date.getMonth()+1)+"/"+date.getDate()+"/"+date.getFullYear());
			range.val('custom');
		}
	};

	range.change(function () {

		if (dates.length > 0) {
			if (this.selectedIndex == 0) {
				dates.css('display','none');
				start.val(''); end.val('');
				return;
			} else dates.css('display', 'inline-block');
		}

		var today = new Date(),
			startdate = new Date(today.getFullYear(),today.getMonth(),today.getDate()),
			enddate = new Date(today.getFullYear(),today.getMonth(),today.getDate());
		today = new Date(today.getFullYear(),today.getMonth(),today.getDate());

		switch($(this).val()) {
			case 'week':
				startdate.setDate(today.getDate()-today.getDay());
				enddate = new Date(startdate.getFullYear(),startdate.getMonth(),startdate.getDate()+6);
				break;
			case 'month':
				startdate.setDate(1);
				enddate = new Date(startdate.getFullYear(),startdate.getMonth()+1,0);
				break;
			case 'quarter':
				quarter = Math.floor(today.getMonth()/3);
				startdate = new Date(today.getFullYear(),today.getMonth()-(today.getMonth()%3),1);
				enddate = new Date(today.getFullYear(),startdate.getMonth()+3,0);
				break;
			case 'year':
				startdate = new Date(today.getFullYear(),0,1);
				enddate = new Date(today.getFullYear()+1,0,0);
				break;
			case 'yesterday':
				startdate.setDate(today.getDate()-1);
				enddate.setDate(today.getDate()-1);
				break;
			case 'lastweek':
				startdate.setDate(today.getDate()-today.getDay()-7);
				enddate.setDate((today.getDate()-today.getDay()+6)-7);
				break;
			case 'last30':
				startdate.setDate(today.getDate()-30);
				enddate.setDate(today.getDate());
				break;
			case 'last90':
				startdate.setDate(today.getDate()-90);
				enddate.setDate(today.getDate());
				break;
			case 'lastmonth':
				startdate = new Date(today.getFullYear(),today.getMonth()-1,1);
				enddate = new Date(today.getFullYear(),today.getMonth(),0);
				break;
			case 'lastquarter':
				startdate = new Date(today.getFullYear(),(today.getMonth()-(today.getMonth()%3))-3,1);
				enddate = new Date(today.getFullYear(),startdate.getMonth()+3,0);
				break;
			case 'lastyear':
				startdate = new Date(today.getFullYear()-1,0,1);
				enddate = new Date(today.getFullYear(),0,0);
				break;
			case 'lastexport':
				startdate = lastexport;
				enddate = today;
				break;
			case 'custom': return; break;
		}
		StartCalendar.select(startdate);
		EndCalendar.select(enddate);
	}).change();

}