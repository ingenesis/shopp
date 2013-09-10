/*!
 * One Click Upload - jQuery Plugin
 * Copyright © 2008 Michael Mitchell - http://www.michaelmitchell.co.nz
 */
(function($){
	$.fn.upload = function(options) {
		/** Merge the users options with our defaults */
		options = $.extend({
			name: 'file',
			enctype: 'multipart/form-data',
			action: '',
			accept: false,
			maxfilesize: false,
			autoSubmit: true,
			onSubmit: function() {},
			onComplete: function() {},
			onSelect: function() {},
			params: {}
		}, options);

		return new $.ocupload(this, options);
	};

	$.ocupload = function(element, options) {
		var self = this,

		/** A unique id so we can find our elements later */
			id = new Date().getTime().toString().substr(8),

		/** Upload Iframe */
			iframe = $(
				'<iframe id="iframe'+id+'" name="iframe'+id+'" />'
			).css({
				display: 'none'
			}),

		/** Form */
			form = $(
				'<form method="post" enctype="'+options.enctype+'" action="'+options.action+'" target="iframe'+id+'" />'
			).css({
				margin: 0,
				padding: 0
			}).submit(function (e) {
				if (e) e.stopPropagation();
			}),

		/** File Input */
			input = $(
				'<input name="'+options.name+'" type="file" />'
			).css({
				position: 'absolute',
				display: 'block',
				opacity: 0
			}),

		/** Remember the element's parent **/
			ep = element.parent(),

		/** Wrap in a container and attach to body to get accurate dimensions **/
			container = element.wrap('<div />').parent().appendTo($('body'));

			elementHeight = element.outerHeight(true);
			elementWidth = element.outerWidth(true);

		/** Move it all back where it belongs **/
			container.appendTo(ep);

			if (options.maxfilesize)
				$('<input type="hidden" name="MAX_FILE_SIZE" value="'+options.maxfilesize+'" />').appendTo(form);

		/** Put everything together **/
			form.append(input);
			element.after(form).after(iframe);

		/** Find the container and make it nice and snug */
		container = element.parent().css({
			position: 'relative',
			height: (elementHeight)+'px',
			width: (elementWidth)+'px',
			overflow: 'hidden',
			cursor: 'pointer',
			margin: 0,
			padding: 0
		});

		/** Put our file input in the right place */
		input.css({
			width:elementWidth+'px',
			height:elementHeight+'px',
			marginTop:-elementHeight+'px',
			marginLeft:'0px',
			fontSize:'2em'	// Make sure the input is large enough (height) to cover the element
		});

		/** Watch for file selection */
		input.change(function() {
			if (this.value == '') return false;
			if ($.ua.msie) {
				// prevent double change events firing in IE
				if (this.firedChange) return this.firedChange = false;
				this.firedChange = true;
			}

			/** Do something when a file is selected. */
			self.onSelect();

			/** Submit the form automaticly after selecting the file */
			if(self.autoSubmit) {
				 self.submit();
			}
		});

		/** Methods */
		$.extend(this, {
			autoSubmit: true,
			onSubmit: options.onSubmit,
			onComplete: options.onComplete,
			onSelect: options.onSelect,

			/** get filename */
			filename: function() {
				return input.attr('value');
			},

			/** get/set params */
			params: function(params) {
				var params = params ? params : false;
				if (params) options.params = $.extend(options.params, params);
				return options.params;
			},

			/** get/set name */
			name: function(name) {
				var name = name ? name : false;
				if (name) input.attr('name', value);
				return input.attr('name');
			},

			/** get/set action */
			action: function(action) {
				var action = action ? action : false;
				if (action) form.attr('action', action);
				return form.attr('action');
			},

			/** get/set enctype */
			enctype: function(enctype) {
				var enctype = enctype ? enctype : false;
				if(enctype) form.attr('enctype', enctype);
				return form.attr('enctype');
			},

			accept: function(accept) {
				var accept = accept ? accept : false;
				if (accept) form.attr('accept', accept);
				return form.attr('accept');
			},

			/** set options */
			set: function(obj, value) {
				var value =	value ? value : false;

				function option (action, value) {
					switch(action) {
						default:
							throw new Error('[jQuery.ocupload.set] \''+action+'\' is an invalid option.');
							break;
						case 'name':
							self.name(value);
							break;
						case 'action':
							self.action(value);
							break;
						case 'enctype':
							self.enctype(value);
							break;
						case 'params':
							self.params(value);
							break;
						case 'autoSubmit':
							self.autoSubmit = value;
							break;
						case 'onSubmit':
							self.onSubmit = value;
							break;
						case 'onComplete':
							self.onComplete = value;
							break;
						case 'onSelect':
							self.onSelect = value;
							break;
					}
				}

				if (value) option(obj, value);
				else {
					$.each(obj, function(key, value) {
						option(key, value);
					});
				}
			},

			/** Submit the form */
			submit: function() {
				/** Do something before we upload */
				this.onSubmit();

				/** add additional paramters before sending */
				var oa = form.attr('action').split('?'),
					url = oa[0],
					qp = oa[1]?oa[1].split('&'):[],
					fparams = {},
					action = false;

				$.each(qp, function (i,pair) {
					var kv = pair.split('=');
					if (kv.length == 2 && !options.params[kv[0]]) fparams[kv[0]] = kv[1];
				});

				action = url+'?'+('' != $.param(fparams)?$.param(fparams)+'&':'')+$.param(options.params);
				form[0].setAttribute('action',action);

				/** Submit the actual form */
				form.submit();

				/** Do something after we are finished uploading */
				iframe.unbind().load(function() {
					/** Get a response from the server in plain text */
					var myFrame = document.getElementById(iframe.attr('name')),
						response = $(myFrame.contentWindow.document.body).text();

					/** Do something on complete */
					self.onComplete(response); //done :D
				});

			}
		});
	};
})(jQuery);