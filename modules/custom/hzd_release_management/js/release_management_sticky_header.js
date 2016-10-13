(function ($, Drupal) {

  'use strict';

  /**
   * Adds summaries to the book outline form.
   *
   * @type {Drupal~behavior}
   *#quickinfo-sortable
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior to book outline forms.
   */
  Drupal.behaviors.release_management_sticky_header = {
    attach: function (context) {
        
	var stickySidebar = jQuery('#released_results_wrapper > table > thead > tr').offset().top;

	jQuery(window).scroll(function() {  
	    if (jQuery(window).scrollTop() > stickySidebar) {
		jQuery('#released_results_wrapper > table > thead ').addClass('sticky_do_header');
	    }
	    else {
		jQuery('#released_results_wrapper > table > thead ').removeClass('sticky_do_header');
	    }  
	});

     }
  };

})(jQuery, Drupal);

