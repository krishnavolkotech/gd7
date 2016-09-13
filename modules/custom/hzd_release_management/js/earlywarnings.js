Drupal.behaviors.earlywarnings = function (context) {
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