/*!
 * imageset.js - Image settings UI behaviors
 * Copyright © 2011 by Ingenesis Limited. All rights reserved.
 * Licensed under the GPLv3 {@see license.txt}
 */

jQuery(document).ready( function($) {
	$.template('editor',$('#editor'));

	var editing = false,
		table = $('#image-setting-table');

	$('#images a.edit, a.add-new').click(function (e) {
		e.preventDefault();
		var $this = $(this),
			row = $this.parents('tr').hide(),
			id = row.size() > 0?row.attr('id').substr(14):false,
			data = images[id]?images[id]:{name:''},
			ui = $.tmpl('editor',data),
			emptylabel = table.find('tr.empty'),
			sm = ui.find('select.fit-menu').val(data.fit),
			qm = ui.find('select.quality-menu').val(data.quality),
			percentage = function () { $(this).val( asPercent( $(this).val() ) ); },
			ps = ui.find('.percentage').each(percentage).change(percentage),
			cancel = ui.find('a.cancel');

		$this.cancel = function (e) {
			if (e) e.preventDefault();
			editing = false;
			if (emptylabel.size() == 1) emptylabel.show();
			ui.remove();
			row.fadeIn('fast');
		};
		cancel.click($this.cancel);

		if (editing) editing.cancel(false);

		if (row.size() > 0) ui.insertAfter(row);
		else {
			if (emptylabel.size() > 0) emptylabel.hide();
			table.prepend(ui);
		}

		editing = $this;
	});

	$('#images a.delete').click(function() {
		if (confirm($is.confirm)) return true;
		else return false;
	});

});