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
  /*	
   * early warnings releases	
   */	

  $('.pager li a').click(function() {
	var ele = $(this);
	var url = ele.attr('href');
	
	var params = url.split('?')[1];
	var group_id = Drupal.settings.group_id;
	var type = Drupal.settings.type;
	var base_path = Drupal.settings.basePath;

	url = base_path + 'release-management/search_earlywarning' + '?' + params;

	$.post(url, {}, function(data) {
	    if (data.status == true) {
		$('#earlywarnings_results_wrapper').html(data.data);
		Drupal.attachBehaviors('#earlywarnings_results_wrapper');
	    }
	}, 'json');
	return false;
    });

     }
  };

})(jQuery, Drupal);
