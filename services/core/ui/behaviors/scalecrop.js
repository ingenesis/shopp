/*!
 * scalecrop.js - Image scaling & cropping interface
 * Copyright © 2008-2010 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */
jQuery.fn.scaleCrop = function (settings) {
	var $ = jQuery,
		defaults = {
			imgsrc:false,
			target:{width:300,height:300},
			init:{x:false,y:false,s:false}
		},
		settings = $.extend(defaults,settings),
		totalWidth = settings.target.width+100,
		totalHeight = settings.target.height+100,
		maskTop = maskBottom = (totalHeight-settings.target.height)/2,
		maskLeft = maskRight = (totalWidth-settings.target.width)/2,
		minWidth = settings.target.width,
		minHeight = settings.target.height,
		maxWidth = maxHeight = aspect = 0,
		px = 'px',
		solid = ' solid',
		whiteBorder = px+solid+' white',
		blackBorder = px+solid+' black',
		cropping = {x:0,y:0,s:100},
		$this = $(this),
		parent = $this.parent().parent().parent().css({width:(totalWidth+22)+px}),
		viewport = $('<div class="scalecrop"/>').css({
				position:'relative',overflow:'hidden',width:totalWidth+px,height:totalHeight+px
			}).appendTo($this),
		image = $('<img src="'+settings.imgsrc+'" />').appendTo(viewport),
		mask = $('<div/>').css({
				width:settings.target.width+px,height:settings.target.height+px,border:(maskTop+1)+whiteBorder,opacity:0.8
			}).appendTo(viewport),
		frame = $('<div/>').css({
				top:maskTop+px,left:maskLeft+px,width:settings.target.width+px,height:settings.target.height+px,border:'1'+blackBorder
			}).appendTo(viewport),
		container = $('<div/>').appendTo(viewport),
		resizeImage = function (scale) {
			var w = ((maxWidth-minWidth)*scale)+minWidth,
				h = ((maxHeight-minHeight)*scale)+minHeight,
				ratio=(aspect<1)?h/maxHeight:w/maxWidth,
				fp = frame.position(),
				ip = image.position(),
				id = {width:image.width(),height:image.height()},
				inst = handle.data("draggable"),
				d = false; // Delta coords

			// Resize the image
			if (aspect<1) image.width(maxWidth*ratio).height(h);
			else image.width(w).height(maxHeight*ratio);

			image[0].style.left = (image.position().left+Math.ceil((id.width-image.width())/2))+px;
			image[0].style.top = (image.position().top+Math.ceil((id.height-image.height())/2))+px;

			d = image.position(); // Update the image position

			// Reposition image if the top/left image edge goes inside the frame
			if (d.left > fp.left) {
				image[0].style.left = (fp.left+1)+px;
				d = image.position(); // Update the image position
			}

			if (d.top > fp.top) {
				image[0].style.top = (fp.top+1)+px;
				d = image.position(); // Update the image position
			}

			// Reposition image if the right/bottom image edge goes inside the frame
			if (d.left+image.width() < fp.left+frame.width()+1) {
				image[0].style.left = fp.left+frame.width()-image.width()+1+px;
				d = image.position(); // Update the image position
			}

			if (d.top+image.height() < fp.top+frame.height()+1) {
				image[0].style.top = fp.top+frame.height()-image.height()+1+px;
				d = image.position(); // Update the image position
			}

			if ( inst )
				inst.element.css({left:d.left,top:d.top}); // Causing errors

			container.width((image.width()*2)-frame.width()).height((image.height()*2)-frame.height())
				.css({
					left:fp.left+((frame.width()-container.width())/2)+1+px,
					top:fp.top+((frame.height()-container.height())/2)+1+px
				});
			handle.draggable('option','containment',container);
			// Remove an extra 1 to account for the frame border
			cropping = {x:d.left-fp.left-1,y:d.top-fp.top-1,s:ratio};
			$this.trigger('change.scalecrop',[cropping]);
		},
		metrics = function () {	return cropping; },
		handle = $('<div/>').css({width:'100%',height:'100%',cursor:'move'})
			.draggable({
				helper:function () { return image.get(0); },
				start:function (e,ui) {
					ui.position = ui.offset;
				},
				stop: function (e,ui) {
					var inst = $(this).data("draggable");
					inst.cancelHelperRemoval = true;
					inst.element.css({left:ui.position.left,top:ui.position.top});
					cropping.x = image.position().left-frame.position().left-1;
					cropping.y = image.position().top-frame.position().top-1;
					$this.trigger('change.scalecrop',[cropping]);
				}
			}).appendTo(viewport),
		slidebar = $('<div class="slidebar"/>').css({
				left:(totalWidth-176)/2+px
			}).appendTo(viewport);
		scaler = $('<div class="slideball"/>').unbind().mousedown(function () {
			scaler.css({backgroundPosition:'left -16px'});
			$(document).mouseup(function () {
				scaler.css({backgroundPosition:'left top'});
			});
		}).draggable({
			axis:'x',
			containment:'parent',
			drag:function (e,ui) {
				var max = slidebar.width()-$(this).width(),
					scale = ui.position.left/max;
				resizeImage(scale);
			}
		}).appendTo(slidebar);


		image.hide().load(function () {
			maxWidth = this.width;
			maxHeight = this.height;
			aspect = maxWidth/maxHeight;
			handle.width(maxWidth).height(maxHeight);
			var initLeft = (settings.init.x !== false)?
					settings.init.x+px:frame.position().left+((frame.outerWidth()-image.width())/2)+px,
				initTop = (settings.init.y !== false)?
					settings.init.y+px:frame.position().top+((frame.outerHeight()-image.height())/2)+px,
				initScale = (settings.init.s !== false)?settings.init.s:0;

			resizeImage(initScale);
			inst = handle.data("draggable");
			image.css({
				left:initLeft,
				top:initTop
			}).fadeIn('fast');
			resizeImage(initScale);
			if (initScale != 0) scaler.get(0).style.left = (slidebar.width()-scaler.width())*initScale+px;
			$this.trigger('ready.scalecrop',[cropping]);
		});
	return $this;
};