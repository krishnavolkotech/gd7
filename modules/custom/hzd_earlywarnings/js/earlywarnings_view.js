(function ($) {
  Drupal.behaviors.earlywarnings_view = {
    attach: function (context, settings) {

       $(context).find('table#sortable').once('earlywarnings').each(function () {

// Drupal.behaviors.earlywarnings_view = function() {

         var anchor = $("#earlywarnings_results_wrapper #pagination > nav > ul > li > a");
         anchor.each(function( index ) {
         var links = $( this ).attr('href'); 
         var new_href = links.replace('ajax_form=1&_wrapper_format=drupal_ajax', '');   
           $( this ).attr('href', new_href);
         });


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

  jQuery('#edit-deployed-services').val(0);
  jQuery('#edit-deployed-releases').val(0);
  jQuery('#edit-deployed-date').val('');

  jQuery('.state_search_dropdown select').val(0);
  jQuery('.service_search_dropdown select').val(0);
  jQuery('.releases_search_dropdown select').val(0);
  jQuery('.filter_start_date input').val('');
  jQuery('.filter_end_date input').val('');
  jQuery('.limit_search_dropdown select').val(0);
  url = window.location.href; 
  res = url.split('?');
  window.location.assign(res['0']);
  return false;
  // window.location.reload();
}
  
