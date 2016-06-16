jQuery(document).ready( function() {
	var $ = jQuery;

	$.fn.dragrow = function(settings) {
		var $this = $(this),
			rows = $this.find('tbody > tr'),
			notextselect = typeof $(document).attr('onselectstart') != 'undefined', // IE workaround for mousedown selections
			defaults = { onDrop: function () {} },
			settings = $.extend(defaults,settings);

		return rows.bind('mousedown.dragrow', function(e) {
			var tr = $(this).fadeTo('fast', 0.4); // The target row

			lastY = e.pageY; // Y-position of the mouse on last button press

			$('tr', tr.parent() ).not(tr).bind('mouseenter.dragrow',function() {
				var row = $(this);
				if (e.pageY > lastY) row.after(tr); // Move down
				else row.before(tr);				// Move up
				lastY = e.pageY; 					// Save Y-position of mouse for next mouseenter event
			});

			// Catch mouseup to stop dragging (drop) the table row
			$('body').bind('mouseup.dragrow',function() {
				tr.fadeTo('fast', 1);
				$('tr', tr.parent()).unbind('mouseenter.dragrow'); // Clear the mouseenter events to drop the row
				$('body').unbind('mouseup.dragrow'); // Remove this mouseup event handler
				if (notextselect) $(document).unbind('selectstart'); // Make text selectable in IE again
				settings.onDrop(tr);
			});
			e.preventDefault(); // Stop text highlights

			if (notextselect) $(document).bind('selectstart', function () { return false; });
			return false;
		}).css('cursor', 'move');
	};

	$.fn.arrangeRows = function (settings) {
		var $this = $(this);

		$this.dragrow({onDrop:updatePositions}); // Enable drag-drop behaviors

		updatePositions(); // Initialize positions

		$this.find('button[name=top]').hover(function () { $(this).toggleClass('hover'); }).click(function () {
			var row = $(this).parent().parent(),position=row.find('input[name^=position]');
			if (position.val() == 0) return false;
			row.insertBefore(row.parent().find('tr:first'));
			updatePositions();
		});

		/* Move to bottom behavior */
		$this.find('button[name=bottom]').hover(function () { $(this).toggleClass('hover'); }).click(function () {
			var row = $(this).parent().parent(),position=row.find('input[name^=position]');
			row.insertAfter(row.parent().find('tr:last'));
			updatePositions();
		});

		/* Recalculate positions  */
		function updatePositions () {
			var updates = $('#category-id'),updating = $this.find('th.column-name .shoppui-spinner');
			$this.find('tbody tr input[name^=position]').each(function (p,e) {
				var element = $(e),position = p+1;
				if ((element.attr('alt') != '' && position != element.attr('alt')) || element.val() == '0') {
					element.val(position).attr('alt',position);
					updates = updates.add(element);
				} else {
					if (element.attr('alt') == '') element.val(position).attr('alt',position);
					else element.val(position);
				}
			});
			if (updates.size() == 1) return;

			// Send updates to the server
			updating.hide().show();
			$.ajax({
				url:updates_url+"&action=shopp_category_products_order",
				timeout:5000,
				type: "POST",
				datatype:'text',
				data:updates.serialize(),
				success:function (r) {
					updating.hide();
				},
				error:function (r, e) {
					updating.hide();
				}
			});
		}

	};

	$('#arrange-products').arrangeRows();

});