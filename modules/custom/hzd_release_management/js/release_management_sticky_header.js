(function ($) {
  Drupal.behaviors.release_management = {
    attach: function (context, settings) {

	var stickySidebar = jQuery('#released_results_wrapper > table > thead > tr').offset().top;

	jQuery(window).scroll(function() {  
	    if (jQuery(window).scrollTop() > stickySidebar) {
		jQuery('#released_results_wrapper > table > thead ').addClass('affix');
	    }
	    else {
		jQuery('#released_results_wrapper > table > thead ').removeClass('affix');
	    }  
	});

    }
  };
})(jQuery);
