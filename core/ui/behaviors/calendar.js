/*!
 * calendar.js - Modal calendar date selector
 * Copyright © 2008-2013 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */
jQuery.fn.PopupCalendar = function (settings) {
	var $ = jQuery,
		_ = this,
		$this = $(this),
		defaults = {
			month:(new Date().getMonth()+1),
			year:new Date().getFullYear(),
			selection:false,
			m_input:false,
			d_input:false,
			y_input:false,
			input:false,
			startWeek:0,
			title:'my',
			scheduling:true,
			scheduleAfter:false,
			disabled:'disabled',
			scopeMonth:'month',
			active:'active',
			selected:'selected',
			hover:'hover',
			autoinit:false
		},
		settings = $.extend(defaults,settings),
		DAYS_IN_MONTH = new Array(new Array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31),
								  new Array(0, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31)),
		MONTH_NAMES = new Array('',$cal.jan, $cal.feb, $cal.mar, $cal.apr, $cal.may, $cal.jun,
									$cal.jul, $cal.aug, $cal.sep, $cal.oct, $cal.nov, $cal.dec),
		WEEK_DAYS = new Array($cal.sun, $cal.mon,$cal.tue, $cal.wed, $cal.thu, $cal.fri, $cal.sat),

		/* Date Constants */
		K_FirstMissingDays = 639787, /* 3 Sep 1752 */
		K_MissingDays = 11, /* 11 day correction */
		K_MaxDays = 42, /* max slots in a calendar map array */
		K_Thursday = 4, /* for reformation */
		K_Saturday = 6, /* 1 Jan 1 was a Saturday */
		K_Sept1752 = new Array(30, 31, 1, 2, 14, 15, 16,
							   17, 18, 19, 20, 21, 22, 23,
							   24, 25, 26, 27, 28, 29, 30,
							   -1, -1, -1, -1, -1, -1, -1,
							   -1, -1, -1, -1, -1, -1, -1,
							   -1, -1, -1, -1, -1, -1, -1
							  ),
		today = new Date(),
		calendar = new Array(),
		dates = new Array(),

		month = settings.month,
		year = settings.year,
		m_input = settings.m_input,
		d_input = settings.d_input,
		y_input = settings.y_input,
		input = (m_input)?m_input:settings.input,
		inputs = (m_input)?m_input.add(d_input).add(y_input):input,
		sw = settings.startWeek,
		tf = settings.title,
		autoinit = settings.autoinit,

		// Literals
		disabled = settings.disabled,
		scopeMonth = settings.scopeMonth,
		active = settings.active,
		selected = settings.selected,
		hover = settings.hover;

	// Set today starting at 12am
	today = new Date(today.getFullYear(),today.getMonth(),today.getDate());
	_.scope = scopeMonth;
	_.scheduling = settings.scheduling;
	_.selection = (!settings.selection)?today:settings.selection;
	_.ui = false;

	$this.mouseenter(function () {
		_.ui = true;
	}).mouseleave(function () {
		_.ui = false;
	});

	_.change(function () {
		if (input !== false) input.val((_.selection.getMonth()+1)+"/"+_.selection.getDate()+"/"+_.selection.getFullYear());
		if (m_input !== false) m_input.val(_.selection.getMonth()+1);
		if (d_input !== false) d_input.val(_.selection.getDate());
		if (y_input !== false) y_input.val(_.selection.getFullYear());
		if (_.focused) _.focused.select();
		return this;
	});

	_.bind('updated',function () {
		var match = false;
		if (m_input !== false &&
			!(m_input.val() == '' ||
			d_input.val() == '' ||
			y_input.val() == '')
			) _.select(new Date(y_input.val(),m_input.val()-1,d_input.val()));
		else if (input !== false) {
			match = input.val().match(/^(\d{1,2}).{1}(\d{1,2}).{1}(\d{4})/);
			if (match) _.select(new Date(match[3],(match[1]-1),match[2]));
		}
	});

	_.select = function (selection) {
		if (selection) {
			if (selection instanceof Date) _.selection = selection;
			else _.selection = new Date(selection);
		}
		_.change().render(_.selection.getMonth()+1,_.selection.getFullYear()).autoselect();
	};

	_.render = function (month,year) {
		$this.empty();

		var backarrow,previousMonth,nextarrow,title,dayname,thisMonth,thisYear,thisDate,
			i = 0,
			w = 0,
			wd = 0,
			scheduleAfter = false,
			dayLabels = new Array(),
			weeks = new Array();

		if (!month) month = _.selection.getMonth()+1;
		if (!year) year = _.selection.getFullYear();

		dates = this.getDayMap(month, year,sw,true);

		if (settings.scheduleAfter.selection) scheduleAfter = settings.scheduleAfter.selection;
		if (settings.scheduleAfter === false) scheduleAfter = today;

		backarrow = $('<span class="back">&#9664;</span>').appendTo($this);
		previousMonth = new Date(year,month-1,0);

		if (!_.scheduling || (_.scheduling && previousMonth.getTime() >= scheduleAfter.getTime())) {
			backarrow.click(function () {
				_.scope = scopeMonth;
				_.selection = new Date(year,month-2);
				_.render(_.selection.getMonth()+1,_.selection.getFullYear());
				_.change();
			});
		}
		nextarrow = $('<span class="next">&#9654;</span>').click(function () {
			_.scope = scopeMonth;
			_.selection = new Date(year,month);
			_.render(_.selection.getMonth()+1,_.selection.getFullYear());
			_.change();
		}).appendTo($this);

		title = $('<h3></h3>').appendTo($this);
		for (i = 0; i < tf.length; i++) {
			if (-1 != $.inArray(tf[i],['m','n','M','F'])) $('<span class="month">'+MONTH_NAMES[month]+'</span>').appendTo(title);
			if (-1 != $.inArray(tf[i],['y','Y'])) $('<span class="year">'+year.toString()+'</span>').appendTo(title);
			$('<span> </span>').appendTo(title);
		}

		weeks[w] = $('<div class="week"></week>').appendTo($this);

		wd = sw;
		for (i = 0; i < 7; i++) {
		 	dayname = WEEK_DAYS[wd];
		 	dayLabels[i] = $('<div class="label">'+dayname.substr(0,3)+'</span>').appendTo(weeks[w]);
			wd++;
			if (wd >= WEEK_DAYS.length) wd = 0;

		}

		for (i = 0; i < dates.length; i++) {
			var thisMonth = dates[i].getMonth()+1,
				thisYear = dates[i].getFullYear(),
				thisDate = new Date(thisYear,thisMonth-1,dates[i].getDate());

			// Start a new week
			if (i % 7 == 0) weeks[++w] = $('<div class="week"></div>').appendTo($this);
			if (dates[i] != -1) {
				calendar[i] = $('<div title="'+i+'">'+thisDate.getDate()+'</div>').appendTo(weeks[w]);
				calendar[i].date = thisDate;

				if (thisMonth != month) calendar[i].addClass(disabled);
				if (_.scheduling && thisDate.getTime() < scheduleAfter.getTime()) calendar[i].addClass(disabled);
				if (thisDate.getTime() == today.getTime()) calendar[i].addClass('today');

				calendar[i].hover(function () {
					$(this).addClass(hover);
				},function () {
					$(this).removeClass(hover);
				});

				calendar[i].mousedown(function () { $(this).addClass(active); });
				calendar[i].mouseup(function () { $(this).removeClass(active); });


				if (!_.scheduling || (_.scheduling && thisDate.getTime() >= scheduleAfter.getTime())) {
					calendar[i].click(function () {
						_.resetCalendar();
						if (!$(this).hasClass(disabled)) $(this).addClass(selected);

						_.select(dates[$(this).attr('title')]);
						_.scope = "day";

						if (_.selection.getMonth()+1 != month) {
	 						_.render(_.selection.getMonth()+1,_.selection.getFullYear());
							_.autoselect();
						} else {
							_.ui = false;
							$this.hide();
						}
						_.change().trigger('calendarSelect');
					});
				}
			}
		}

		return this;
	};

	_.autoselect = function () {
		for (var i = 0; i < dates.length; i++)
			if (dates[i].getTime() == _.selection.getTime())
				return calendar[i].addClass(selected);
	};

	_.resetCalendar = function () {
		for(var i = 0; i < calendar.length; i++)
			calendar[i].removeClass(selected);
	};

	/**
	 * Fill an array of 42 integers with a calendar.  Assume for a moment
	 * that you took the (maximum) 6 rows in a calendar and stretched them
	 * out end to end. You would have 42 days or spaces. This routine
	 * builds that calendar list for any month from Jan. 1 through Dec. 9999.
	 * @param int month Month of the calendar
	 * @param int year Year of the calendar (4-digits)
	 * @param int sw Start of the week offset (0 for Sunday)
	 * @param boolean all Include previous and next month days
	 **/
	_.getDayMap = function (month, year, sw, all) {
		var i,pm,dm,dw,pw,ceiling,
			day = 1,
			c = 0,
			days = new Array(),
			last_month = (month - 1 == 0)? 12: month - 1,
			last_month_year = (last_month == 12)? year - 1: year;

		if(month == 9 && year == 1752) return K_Sept1752;

		for(i = 0; i < K_MaxDays; i++)
			days.push(-1);

		pm = DAYS_IN_MONTH[(_.is_leapyear(last_month_year))?1:0][last_month];	// Get the last day of the previous month
		dm = DAYS_IN_MONTH[(_.is_leapyear(year))?1:0][month];			// Get the last day of the selected month
		dw = _.dayInWeek(1, month, year, sw); // Find where the 1st day of the month starts in the week
		pw = _.dayInWeek(1, month, year, sw); // Find the 1st day of the last month in the week

		if (all) while(pw--) days[pw] = new Date(last_month_year,last_month-1,pm--);
		while(dm--) days[dw++] = new Date(year,month-1,day++);
		ceiling = days.length - dw;
		if (all) while(c < ceiling)
			days[dw++] = new Date(year,month,++c);

		return days;
	};

	/**
	 * Return the day of the year
	 **/
	_.dayInYear = function (day, month, year) {
	    var i,leap = _.is_leapyear(year)?1:0;
	    for(i = 1; i < month; i++)
			day += DAYS_IN_MONTH[leap][i];
	    return day;
	};

	/**
	 * Return the x based day number for any date from 1 Jan. 1 to
	 * 31 Dec. 9999.  Assumes the Gregorian reformation eliminates
	 * 3 Sep. 1752 through 13 Sep. 1752.  Returns Thursday for all
	 * missing days.
	 * @param int day Day of the date
	 * @param int month Month of the date
	 * @param int year Year of the date
	 * @param int sw Start of the week offset (0 for Sunday)
	 **/
	_.dayInWeek = function (day, month, year, sw) {
		// Find 0 based day number for any date from Jan 1, 1 - Dec 31, 9999
		var daysSinceBC = (year - 1) * 365 + _.leapYearsSinceBC(year - 1) + _.dayInYear(day, month, year),
 			val = K_Thursday;
	    // Set val
		if(daysSinceBC < K_FirstMissingDays) val = ((daysSinceBC - 1 + K_Saturday ) % 7);
		if(daysSinceBC >= (K_FirstMissingDays + K_MissingDays)) val = (((daysSinceBC - 1 + K_Saturday) - K_MissingDays) % 7);

	    // Shift depending on the start day of the week
	    if (val <= sw) return val += (7 - sw);
	    else return val -= sw;

	};

	_.is_leapyear = function (yr) {
		if (yr <= 1752) return !((yr) % 4);
		else return ((!((yr) % 4) && ((yr) % 100) > 0) || (!((yr) % 400)));
	};

	_.centuriesSince1700 = function (yr) {
		if (yr > 1700) return (Math.floor(yr / 100) - 17);
		else return 0;
	};

	_.quadCenturiesSince1700 = function (yr) {
		if (yr > 1600) return Math.floor((yr - 1600) / 400);
		else return 0;
	};

	_.leapYearsSinceBC = function (yr) {
		return (Math.floor(yr / 4) - _.centuriesSince1700(yr) + _.quadCenturiesSince1700(yr));
	};


	if ( input !== false && input.length > 0 ) {
		pos = input.offset();
        pad = $('#wpadminbar').length > 0 ? $('#wpadminbar').height() * -1 : 0;

		if (pos.left+pos.top == 0) {
			pos = input.parent().parent().css('display','block').offset();
			pad = 6;
		}

		$this.css({left:pos.left+'px',top:(pos.top+input.outerHeight(true))+'px' });

		if (m_input !== false && (y_input.val()+m_input.val()+d_input.val() != '')) {
			_.selection = new Date(y_input.val(),m_input.val()-1,d_input.val());
		}

		inputs.focus(function (e) {
			var p = $(this).offset();
			$this.css({left:p.left+'px',top:p.top+'px' });
			_.show();
			_.focused = $(this);
		}).click(function () {
			if (_.focused) _.focused.focus().select();
		}).blur(function (e) {
			if (_.ui) $(this).focus().select();
			else _.hide();
		}).bind('change.input',function () {
			_.trigger('updated');
		}).trigger('change.input');

	}

	if (settings.scheduleAfter.selection) {
		settings.scheduleAfter.change(function () {
			_.render().autoselect();
		});
	}

	if (autoinit) _.change();

	_.render().autoselect();
	return this;
};