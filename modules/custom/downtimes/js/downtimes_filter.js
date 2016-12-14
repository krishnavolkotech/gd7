(function ($, Drupal) {
    Drupal.behaviors.service_data = {
        attach: function (context, settings) {
            $('.reset_all').click(function () {
                /*var form_id = $(this).attr('reset_form_name');
                 $("." + form_id).resetForm();*/
                /*jQuery('#edit-type').val('select');
                jQuery('#edit-states').val(1);
                jQuery('#edit-services-effected').val(1);
                jQuery('#edit-filter-startdate').val('');
                jQuery('#edit-filter-enddate').val('');
                jQuery('#edit-time-period').val(0);
                jQuery('#edit-string').val('');*/
                var current_page = window.location.href;
                if ( (current_page.toLowerCase().indexOf("string=") >= 0 ) === true ) {  
                  current_page =  current_page.substring(0, current_page.toLowerCase().indexOf("?"));
                  window.location.href = current_page;
                } else {
                  window.location.reload();
                }
            });
        var archived_filter = '';
        var anchor = $("#archived_search_results_wrapper #pagination > nav > ul > li > a");
        
//        if (drupalSettings.string == "archived") {
//            archived_filter = archived_filter + 'string=' + drupalSettings.string + '&';
//            archived_filter = archived_filter + 'downtimes_type=' + drupalSettings.downtimes_type + '&';
//            archived_filter = archived_filter + 'time_period=' + drupalSettings.time_period + '&';
//            archived_filter = archived_filter + 'states=' + drupalSettings.states + '&';
//            archived_filter = archived_filter + 'services_effected=' + drupalSettings.services_effected + '&';
//            archived_filter = archived_filter + 'filter_startdate=' + drupalSettings.filter_startdate + '&';
//            archived_filter = archived_filter + 'filter_enddate=' + drupalSettings.filter_enddate + '&';
//            archived_filter = archived_filter + 'search_string=' + drupalSettings.search_string;
//            var current_page = window.location.href;
//            if ( (current_page.toLowerCase().indexOf("?string=") >= 0 ) === true ) {  
//              current_page =  current_page.substring(0, current_page.toLowerCase().indexOf("?string="));
//            }
//            window.history.pushState( {}, null, current_page + '?' + archived_filter);
//        }
        
        anchor.each(function (index) {
        var links = $(this).attr('href');
                var new_href = links.replace('ajax_form=1&_wrapper_format=drupal_ajax', '');
//                if ( (new_href.toLowerCase().indexOf("string=") >= 0 ) === true ) {
//                    if ((new_href.toLowerCase().indexOf("&string=") >= 0) === true) {
//                      new_href = new_href.substring(0, new_href.toLowerCase().indexOf("&string="));
//                    }
//                }
//               if (drupalSettings.string == "archived") {
//                 $(this).attr('href', new_href + '&' + archived_filter);
//               } else {
                 $(this).attr('href', new_href);  
 //              }
        }); 
        
      }
    };
})(jQuery, Drupal);