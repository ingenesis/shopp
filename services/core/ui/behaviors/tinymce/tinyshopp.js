/*!
 * tinyshopp.js - Shopp TinyMCE Plugin
 * Copyright © 2008-2011 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */
(function() {
	tinymce.create('tinymce.plugins.Shopp', {
		init : function(ed, url) {
			ed.addCommand('mceShopp', function() {
				ed.windowManager.open({
					file : url + '/dialog.php?p='+ShoppDialog.p,
					width : 320,
					height : 200,
					inline : 1
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});
			// Register button
			ed.addButton('Shopp', {
				title : ShoppDialog.desc,
				cmd : 'mceShopp',
				image : url + '/shopp.png'
			});

		}
	});

	// Register plugin
	tinymce.PluginManager.add('Shopp', tinymce.plugins.Shopp);

	// Register language pack
	if ( typeof( tinymce ) != 'undefined' && typeof(ShoppDialog) != 'undefined')
		tinymce.addI18n('en.Shopp', ShoppDialog);
})();
