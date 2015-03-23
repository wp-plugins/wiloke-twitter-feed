;(function($, window, document, undefined)
{
	"use strict";
	$(window).load(function()
	{
		$(".pi_color_picker").wpColorPicker();
	})

	$(document).ajaxComplete(function(event, xhr, settings){
		if ( typeof settings.data !='undefined' && (settings.data).search("widget_twitter_feed") != -1 )
		{
			$("input.pi_color_picker").wpColorPicker();
			var $control = $("input.pi_color_picker").closest(".wp-picker-container");
			if ( $control.parent().prev().hasClass("wp-color-result") )
			{	
				$control.parent().prev().addClass("hidden");
			}
			// $(".wp-picker-container a:first-child").remove();
		}
	})

})(jQuery, window, document);