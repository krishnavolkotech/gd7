(function ($) {
  Drupal.behaviors.earlywarnings_view = {
    attach: function (context, settings) {

       $(context).find('table#sortable').once('earlywarnings').each(function () {

// Drupal.behaviors.earlywarnings_view = function() {


 $("#earlywarnings_release_sortable").tablesorter({
    headers: {
	3: {sorter: false }
	  },
    widgets: ['zebra']
    });


  $("#viewearlywarnings_sortable").tablesorter({
    headers: {
	3: {sorter: false }
	  },
    widgets: ['zebra']
    });


  $('.specific_earlywarnings #earlywarnings_results_wrapper .pager li a').click(function() {
    var ele = $(this);
    var url = ele.attr('href');
    
    var params = url.split('?')[1];
    var group_id = Drupal.settings.group_id;
    var type = Drupal.settings.type;
    var base_path = Drupal.settings.basePath;
    
    url = base_path + 'release-management/search_earlywarning' + '?' + params + '&type=releaseWarnings';
    
    $.post(url, {}, function(data) {
	     if (data.status == true) {
	       $('#earlywarnings_results_wrapper').html(data.data);
	       Drupal.attachBehaviors('#earlywarnings_results_wrapper');
	     }
	   }, 'json');
    return false;
	 });
	 });
	}
   };
})(jQuery);


function reset_form_elements(){
  // var base_path = Drupal.settings.basePath;
  // var group_id = Drupal.settings.group_id;
  // var path = base_path + 'node/' + group_id +'/view_earlywarnings';
  // var path = base_path + 'release-management/earlywarnings';

  $('#edit-deployed-services').val(0);
  $('#edit-deployed-releases').val(0);
  $('#edit-deployed-date').val('');

  $('.state_search_dropdown select').val(0);
  $('.service_search_dropdown select').val(0);
  $('.releases_search_dropdown select').val(0);
  $('.filter_start_date input').val('');
  $('.filter_end_date input').val('');
  $('.limit_search_dropdown select').val(0);
  window.location.reload();
}
  
