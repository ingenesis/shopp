/*!
 * setup.js - General settings screen behaviors
 * Copyright © 2008-2010 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */

jQuery(document).ready(function ($) {

	var xhr,
		$states = $('#base_operations_zone').selectize(),
		states = $states[0].selectize,
		$base = $('#base_operations').selectize({
			onChange: function (value) {
				if ( value == '' ) {
					states.disable();
					return true;
				}
				states.disable();
				states.settings.placeholder = $ss.loading;
				states.updatePlaceholder();
				states.clearOptions();
				states.load(function(callback) {
					xhr && xhr.abort();
					xhr = $.getJSON(zones_url+'&action=shopp_country_zones&country='+value,
						function (data) {
							if ( false == data || data.length == 0 ) {
								states.settings.placeholder = ' ';
								states.updatePlaceholder();
								states.$control.fadeOut(200);
								return;
							} else states.$control.show();
							states.enable();
							$.each(data, function(value, label) {
								if ( 0 == value ) {
									states.settings.placeholder = sprintf($ss.prompt, [label]);
									states.updatePlaceholder();
									return;
								}
								states.addOption({text:label, value:value});
							});
							states.open();
						},
						function () {
							callback();
						}
					);
				});

			}
	});

	$('#selectall_targetmarkets').change(function () {
		if ($(this).attr('checked')) $('#target_markets input').not(this).attr('checked',true);
		else $('#target_markets input').not(this).attr('checked',false);
	});

});