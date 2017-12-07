/*!
 * suggest.js - Shopp search suggestion library
 * Copyright © 2011 by Ingenesis Limited, Copyright 2007 by Mark Jaquith and Alexander Dick
 * Licensed under the GPLv3 {@see license.txt}
 */

/*
 * suggest.js 1.2  - 2011-03-30
 * Patched by Jonathan Davis with extra options
 * Patched by Mark Jaquith with Alexander Dick's "multiple items" patch to allow for auto-suggesting of more than one tag before submitting
 * See: http://www.vulgarisoip.com/2007/06/29/jquerysuggest-an-alternative-jquery-based-autocomplete-library/#comment-7228
 *
 *	Uses code and techniques from following libraries:
 *	1. http://www.dyve.net/jquery/?autocomplete
 *	2. http://dev.jquery.com/browser/trunk/plugins/interface/iautocompleter.js
 *
 *	All the new stuff written by Peter Vulgaris (www.vulgarisoip.com)
 *	Feel free to do whatever you want with this file
 *
 */
(function($) {

	$.suggest = function(obj, options) {
		var $anchor, $input, $results, timeout, prevLength, cache, cacheSize;

		$anchor = $(obj);
		input = $anchor.is('input')?$anchor:$anchor.find('input');

		$input = $(input).attr('autocomplete', 'off');
		$results = $('<ul/>').appendTo('body');

		timeout = false;		// hold timeout ID for suggestion results to appear
		prevLength = 0;			// last recorded length of $input.val()
		cache = [];				// cache MRU list
		cacheSize = 0;			// size of cache in chars (bytes?)

		$results.addClass(options.resultsClass).appendTo('body');

		resetPosition();
		$(window)
			.on('load', resetPosition)		// just in case user is changing size of page while loading
			.resize(resetPosition);

		$input.blur(function() {
			setTimeout(function() { $results.hide(); }, 200);
		});

		if (options.showOnFocus) {
			$input.focus(function() {
				resetPosition();
				displayItems();
				if (options.autoSuggest) autoSuggest();

			});
		}

		// help IE users if possible
		if ( $.ua.msie ) {
			try {
				$results.bgiframe();
			} catch(e) { }
		}

		// I really hate browser detection, but I don't see any other way
		if ($.ua.mozilla)
			$input.keypress(processKey);	// onkeypress repeats arrow keys in Mozilla/Opera
		else
			$input.keydown(processKey);		// onkeydown repeats arrow keys in IE/Safari

		function resetPosition() {
			var offset = $anchor.offset();
			$results.css({
				top: (offset.top + $anchor.outerHeight() + options.yoffset) + 'px',
				left: offset.left + 'px'
			});
		}

		function processKey(e) {

			// handling up/down/escape requires results to be visible
			// handling enter/tab requires that AND a result to be selected
			if ((/27$|38$|40$/.test(e.keyCode) && $results.is(':visible')) ||
				(/^13$|^9$/.test(e.keyCode) && getCurrentResult())) {

				if (e.preventDefault)
					e.preventDefault();
				if (e.stopPropagation)
					e.stopPropagation();

				e.cancelBubble = true;
				e.returnValue = false;

				switch(e.keyCode) {

					case 38: // up
						prevResult();
						break;

					case 40: // down
						nextResult();
						break;

					case 9:  // tab
					case 13: // return
						selectCurrentResult();
						break;

					case 27: //	escape
						$results.hide();
						break;

				}

			} else if ($input.val().length != prevLength) {

				if (timeout)
					clearTimeout(timeout);
				timeout = setTimeout(suggest, options.delay);
				prevLength = $input.val().length;

			}

		}

		function suggest() {

			var q = $.trim($input.val()), multipleSepPos, items;

			// Use a default query when none has been entered
			if (q.length == 0 && options.autoSuggest) q = options.autoSuggest;

			if ( options.multiple ) {
				multipleSepPos = q.lastIndexOf(options.multipleSep);
				if ( multipleSepPos != -1 ) {
					q = $.trim(q.substr(multipleSepPos + options.multipleSep.length));
				}
			}
			if (q.length >= options.minchars) {

				cached = checkCache(q);

				if (cached) {

					displayItems(cached['items']);

				} else {

					$.get(options.source, {q: q}, function(data) {

						$results.hide();
						if ('json' == options.format) {
							items = $.parseJSON(data);
						} else items = parseTxt(data, q);

						displayItems(items);
						addToCache(q, items, data.length);

					});

				}

			} else $results.hide();

		}

		function autoSuggest () {
			var timeout = false;
			if (timeout) clearTimeout(timeout);
			timeout = setTimeout(suggest, 3000);
			$input.blur(function () {
				clearTimeout(timeout);
			});
		}


		function checkCache(q) {
			var i;
			for (i = 0; i < cache.length; i++)
				if (cache[i]['q'] == q) {
					cache.unshift(cache.splice(i, 1)[0]);
					return cache[0];
				}

			return false;

		}

		function addToCache(q, items, size) {
			var cached;
			while (cache.length && (cacheSize + size > options.maxCacheSize)) {
				cached = cache.pop();
				cacheSize -= cached['size'];
			}

			cache.push({
				q: q,
				size: size,
				items: items
				});

			cacheSize += size;

		}

		function displayItems(items) {
			var html = '', i;
			if (!items) {
				if (options.label) $results.html('<li>'+options.label+'</li>').show();
				return;
			}

			if (!items.length) {
				if (options.label) $results.html('<li>'+options.label+'</li>').show();
				return;
			}

			resetPosition(); // when the form moves after the page has loaded

			if ('json' == options.format) {
				for (i = 0; i < items.length; i++)
					html += '<li alt="'+items[i].id+'">' + items[i].name + '</li>';
			} else {
				for (i = 0; i < items.length; i++)
					html += '<li>' + items[i] + '</li>';
			}


			$results.html(html).show();

			$results
				.children('li')
				.mouseover(function() {
					$results.children('li').removeClass(options.selectClass);
					$(this).addClass(options.selectClass);
				})
				.click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					selectCurrentResult();
				});

			if (options.autoSelect) nextResult();
		}

		function parseTxt(txt, q) {

			var items = [], tokens = txt.split(options.delimiter), i, token;

			// parse returned data for non-empty items
			for (i = 0; i < tokens.length; i++) {
				token = $.trim(tokens[i]);
				if (token) {
					token = token.replace(
						new RegExp(q, 'ig'),
						function(q) { return '<span class="' + options.matchClass + '">' + q + '</span>'; }
						);
					items[items.length] = token;
				}
			}

			return items;
		}

		function getCurrentResult() {
			var $currentResult;
			if (!$results.is(':visible'))
				return false;

			$currentResult = $results.children('li.' + options.selectClass);

			if (!$currentResult.length)
				$currentResult = false;

			return $currentResult;

		}

		function selectCurrentResult() {

			$currentResult = getCurrentResult();

			if ($currentResult) {
				if ( options.multiple ) {
					if ( $input.val().indexOf(options.multipleSep) != -1 ) {
						$currentVal = $input.val().substr( 0, ( $input.val().lastIndexOf(options.multipleSep) + options.multipleSep.length ) );
					} else {
						$currentVal = "";
					}
					$input.val( $currentVal + $currentResult.text() + options.multipleSep);
					$input.focus();
				} else {
					if ('alt' == options.value) {
						$input.val($currentResult.attr('alt'));
					} else $input.val($currentResult.text()).attr('alt',$currentResult.attr('alt'));
				}
				$results.hide();

				if (options.onSelect)
					options.onSelect.apply($input[0]);

			}

		}

		function nextResult() {

			$currentResult = getCurrentResult();

			if ($currentResult)
				$currentResult
					.removeClass(options.selectClass)
					.next()
						.addClass(options.selectClass);
			else
				$results.children('li:first-child').addClass(options.selectClass);

		}

		function prevResult() {
			var $currentResult = getCurrentResult();

			if ($currentResult)
				$currentResult
					.removeClass(options.selectClass)
					.prev()
						.addClass(options.selectClass);
			else
				$results.children('li:last-child').addClass(options.selectClass);

		}
	};

	$.fn.suggest = function(source, options) {

		if (!source)
			return;

		options = options || {};
		options.multiple = options.multiple || false;
		options.multipleSep = options.multipleSep || ", ";
		options.showOnFocus = options.showOnFocus || false;
		options.source = source;
		options.yoffset = options.yoffset || 0;
		options.delay = options.delay || 100;
		options.autoDelay = options.autoDelay || 3000;
		options.autoQuery = options.autoQuery || false;
		options.resultsClass = options.resultsClass || 'suggest-results';
		options.selectClass = options.selectClass || 'suggest-select';
		options.matchClass = options.matchClass || 'suggest-match';
		options.minchars = options.minchars || 2;
		options.delimiter = options.delimiter || '\n';
		options.format = options.format || 'string';
		options.label = options.label || false;
		options.value = options.value || 'text';
		options.onSelect = options.onSelect || false;
		options.autoSelect = options.autoSelect || false;
		options.maxCacheSize = options.maxCacheSize || 65536;

		this.each(function() {
			new $.suggest(this, options);
		});

		return this;

	};

})(jQuery);