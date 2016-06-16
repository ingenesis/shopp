/*!
 * arrange.js - Drag-drop custom category order
 * Copyright © 2008-2010 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */
jQuery(document).ready( function() {
	var $ = jQuery;

	/* Provide table row drag and drop for rows that relate through category relationships */
	$.fn.dragRelatedRows = function(settings) {
		var $this = $(this),
			rows = $this.is('table')?$this.find('tbody tr'):$this,
			notextselect = typeof $(document).attr('onselectstart') != 'undefined', // IE workaround for mousedown selections
			defaults = { onDrop: function () {} },
			settings = $.extend(defaults,settings);

		/* Capture mouse down to initiate dragging */
		return rows.unbind('mousedown.dragrow').bind('mousedown.dragrow', function(e) {
			var target = $(this),
				slug = target.attr('rel'),
				tr_classes = target.attr('class').replace(' alternate','').split(' '),
				tr_top = tr_classes[tr_classes.length-1] == "top"?(tr_classes.pop()):false,
				tr_siblings_class = tr_classes[tr_classes.length-1].substr(-6) == '-child'?(tr_classes.pop()):tr_top,
				tr_ancestry = tr_classes.slice(0,-1),
				tr = target.add('tr.'+slug, target.parent()); // The target rows

				if ($(e.target).is('input,button') || !target.attr('class')) return true;

				tr.not(':hidden').fadeTo('fast', 0.4);

			lastY = e.pageY; // Y-position of the mouse on last button press

			/* Evaluate when to move the dragged row when hovering over another row */
			$('tr', tr.parent() ).not(tr).bind('mouseenter.dragrow',function(e) {
				var row = $(this),
					classes = row.attr('class').replace(' alternate','').split(' '),
					top = classes[classes.length-1] == "top"?(classes.pop()):false,
					siblingsClass = classes[classes.length-1].substr(-6) == '-child'?(classes.pop()):top,
					ancestry = classes.slice(0,-1),
					parentClass = ancestry[ancestry.length-1],
					lastDescendant = $('tr.'+row.attr('rel')+':last', tr.parent());

				if (ancestry.toString() != tr_ancestry.toString())
					return lastY = e.pageY; 		// Save Y-position of mouse for next mouseenter event

				if (e.pageY > lastY) lastDescendant.after(tr); // Move down
				else row.before(tr);				// Move up
				lastY = e.pageY; 					// Save Y-position of mouse for next mouseenter event
			});

			/* Catch mouseup to stop dragging (drop) the table row */
			$('body').bind('mouseup.dragrow',function() {
				tr.not(':hidden').fadeTo('fast', 1);
				$('tr', tr.parent()).unbind('mouseenter.dragrow'); // Clear the mouseenter events to drop the row
				$('body').unbind('mouseup.dragrow'); // Remove this mouseup event handler
				if (notextselect) $(document).unbind('selectstart'); // Make text selectable in IE again
				settings.onDrop(tr);
			});
			e.preventDefault(); // Stop text highlights

			if (notextselect) $(document).bind('selectstart', function () { return false; });
			return false;
		}).css('cursor', 'row-resize');
	};

	/* Attach category row arranging behaviors to a row (or rows) */
	$.fn.arrangeRow = function (settings) {
		if (!$(this).is('tr')) return false;
		if (!$(this).parent().is('tbody')) return false;
		var $this = $(this),
			row,id,slug,classes,top,siblingsClass,branchSiblings,lastSibling,siblings,ancestry,parentClass,parent,position;

		/* Add drag-drop behaviors */
		$this.dragRelatedRows({onDrop:updatePositions});

		/* Get the current row properties for the row getting interacted with */
		function thisRow (e) {
			if (e.is('tr')) {
				row = e;
				cell = false;
			} else {
				cell = e.parent();
				row = e.parent().parent();
			}

			id = row.find('input[name=id]').val();
			position = row.find('input[name^=position]').val();
			slug = row.attr('rel');
			classes = row.attr('class').replace(' alternate','').split(' ');
			top = classes[classes.length-1] == "top"?(classes.pop()):false;
			siblingsClass = classes[classes.length-1].substr(-6) == '-child'?(classes.pop()):top;
			branchSiblings = row.parent().find('tr.'+siblingsClass);
			lastSibling = branchSiblings.filter(':last');
			siblings = branchSiblings.not(row);
			ancestry = classes.slice(0,-1);
			parentClass = ancestry[ancestry.length-1]?ancestry[ancestry.length-1]:top;
			parent = row.parent().find('tr[rel='+parentClass+']');
		}

		/* Move to top behavior */
		$this.find('button[name=top]').hover(function () { $(this).toggleClass('hover'); }).click(function () {
			thisRow($(this));
			if (position == 1) return false;
			row.parent().find('tr.'+slug).not(row).remove();
			row.find('button.collapsing').addClass('closed').css('background-position','-180px top');
			if (!parent.size()) row.insertBefore(row.parent().find('tr:first'));
			else row.insertAfter(parent);
			updatePositions(row);
		});

		/* Move to bottom behavior */
		$this.find('button[name=bottom]').hover(function () { $(this).toggleClass('hover'); }).click(function () {
			thisRow($(this));
			if (position == branchSiblings.size()) return false;
			row.find('button.collapsing').addClass('closed').css('background-position','-180px top');
			row.parent().find('tr.'+slug).not(row).remove();
			row.insertAfter(lastSibling);
			updatePositions(row);
		});

		/* Row collapse/expand behavior */
		$this.find('button.collapsing').click(function (e) {
			var $button = $(this),
				oslug = false,children=false,
				step = 20,
				pos = new Number($button.css('background-position').replace('%','').replace('px','').split(' ').shift()),
				max = 180;

			thisRow($button);

			if ($button.hasClass('closed')) {
				openedButton = siblings.find('button.collapsing:not(button.closed)');
				opened = openedButton.parent().parent();
				if (opened.size() > 0) {
					oslug = opened.attr('rel');
					children = opened.parent().find('tr.'+oslug).not('tr[rel='+oslug+']').remove();
					siblings.find('button.collapsing:not(button.closed)');
					openedButton.addClass('closed').css('background-position','-180px top');
				}

				$button.bind('closed',function () {
					// row.parent().find('tr.'+slug).not(row).fadeIn();
					$button.removeClass('closed');
				});
				function openIcon () {
					pos += step;
					$button.css('background-position',pos+'px top');
					if (pos < 0) setTimeout(openIcon,20);
					else $button.trigger('closed');
				}
				cell.addClass('updating');

				$.ajax({
					url:loadchildren_url+'&action=shopp_category_children&parent='+id,
					timeout:5000,
					dataType:'json',
					success:function (categories) {
						cell.removeClass('updating');
						if (categories.length == 0) $button.remove();
						else if (categories instanceof Object) {
							$.each(categories,function () {
								new ProductCategory(this,row);
							});
						} else $button.addClass('closed').css('background-position','-180px top');
					},
					error:function (request, err) {
						cell.removeClass('updating');
						$button.addClass('closed').css('background-position','-180px top');
						alert(LOAD_ERROR+' ('+err+')');
					}
				});

				return setTimeout(openIcon,20);
			}

			$button.bind('opened',function () {
				row.parent().find('tr.'+slug).not(row).fadeOut('fast').remove();
				$button.addClass('closed');
			});

			function closeIcon () {
				pos -= step;
				$button.css('background-position',pos+'px top');
				if (Math.abs(pos) < max) setTimeout(closeIcon,20);
				else $button.trigger('opened');
			}
			return setTimeout(closeIcon,20);

		}).hover(function() { $(this).toggleClass('hover'); }).css('background-position','-180px top');

		/* Update row positions in DOM and submit to server */
		function updatePositions (row) {
			thisRow(row);
			var positions = branchSiblings.find('input[name^=position]'),data=false,updating;
			positions.each(function (p,v) { $(v).val(p+1); });
			data = positions.serialize();
			if (!parent.size()) updating = row.find('button.collapsing').parent().addClass('updating');
			else updating = parent.find('button.collapsing').parent().addClass('updating');
			$.ajax({
				url:updates_url+"&action=shopp_category_order",
				timeout:7000,
				type: "POST",
				datatype:'text',
				data:data,
				success:function () {
					updating.removeClass('updating');
				},
				error:function (request, err) {
					updating.removeClass('updating');
					alert(SAVE_ERROR+' ('+err+')');
				}
			});
		}

		/* Create a new row in the DOM for a loaded category (from AJAX) */
		function Category (category,parent) {
			var indent = '<span class="indent">&nbsp;</span>',
				classes = category.uri.split('/'),
				editurl = '',
				lastDescendant = $('tr.'+parent.attr('rel')+':last', parent.parent());

			classes.push(classes[classes.length-2]+'-child');

			return $('<tr class="'+classes.join(' ')+'" rel="'+category.slug+'">'+
				'<td>'+indent.repeat(classes.length-1)+'<button type="button" name="top" class="moveto top">&nbsp;</button><button type="button" name="bottom" class="moveto bottom">&nbsp;</button><a class="row-title" href="'+editurl+'" title="&quot;'+category.name+'&quot;">'+category.name+'</a><input type="hidden" name="id" value="'+category.id+'" /><input type="hidden" name="position['+category.id+']" value="'+category.priority+'" /></td>'+
				'<th scope="row" width="48"><button type="button" name="collapse" class="collapsing closed">&nbsp;</button></th>'+
			'</tr>').insertAfter(lastDescendant).arrangeRow();

		}

		/* Helper to repeat a string */
		String.prototype.repeat = function (r) {
			var result = "",i=1;
			for (i=1; i<r; i++) result += this;
			return result;
		};

	};

	$('#arrange-categories tbody tr').arrangeRow();

});