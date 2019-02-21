(function ($) {
  Drupal.behaviors.earlywarnings = {
    attach: function (context, settings) {
       
           var anchor = $("#earlywarnings_results_wrapper #pagination > nav > ul > li > a");
             anchor.each(function( index ) {
               var links = $( this ).attr('href'); 
               var new_href = links.replace('ajax_form=1&_wrapper_format=drupal_ajax', '');   
               $( this ).attr('href', new_href);
             });
            $.fn.admin_toolbar = function () {
              if($('#toolbar-administration').length) {
                  return 80;
              } else {
                  return 0;
              }
            }
           $("#earlywarnings_release_sortable").tablesorter({
                headers: {
                    3: {sorter: false}
                },
                showProcessing: true,
                headerTemplate : '{content} {icon}',
                widgets: ['zebra',"pager", 'stickyHeaders'],
                widgetOptions: {
                    stickyHeaders: 'sticky-header',
                    stickyHeaders_offset: $.fn.admin_toolbar(),
                    stickyHeaders_cloneId: '-sticky',
                    stickyHeaders_addResizeEvent: true,
                    stickyHeaders_includeCaption: true,
                    stickyHeaders_zIndex: 2,
                    stickyHeaders_attachTo: null,
                    stickyHeaders_xScroll: null,
                    stickyHeaders_yScroll: null,
                    stickyHeaders_filteredToTop: true
                }
           });
            $.tablesorter.addParser({
                // set a unique id                                                                                                             
                id: 'created_on',
                is: function(s) {
                    // return false so this parser is not auto detected                                                                        
                    return false;
                },
                format: function(s) {
                    // format your data for normalization                                                                                      
                   if (s) {
                     var date_info = s.split(' ');
                     var dateele = date_info[0].split('.');
                    //adding 20 if date is formatted in only YY format.                                                                        
                      if (dateele[2].length == 2) {
                       dateele[2] = '20' + dateele[2];
                     }
                     var date = dateele[2] + dateele[1] + dateele[0];
                     return parseInt(date,10);
                   }
                },
                // set type, either numeric or text                                                                                            
                type: 'numeric'
            });

	$("#viewearlywarnings_sortable").tablesorter({
	    headers: {
		3: {sorter: false },
		1: {sorter: 'created_on'}
	    },
            showProcessing: true,
            headerTemplate : '{content} {icon}',
            widgets: ['zebra',"pager", 'stickyHeaders'],
            widgetOptions: {
                stickyHeaders: 'sticky-header',
                stickyHeaders_offset: $.fn.admin_toolbar(),
                stickyHeaders_cloneId: '-sticky',
                stickyHeaders_addResizeEvent: true,
                stickyHeaders_includeCaption: true,
                stickyHeaders_zIndex: 2,
                stickyHeaders_attachTo: null,
                stickyHeaders_xScroll: null,
                stickyHeaders_yScroll: null,
                stickyHeaders_filteredToTop: true
            }
	});

             
       $(context).find('table#sortable').once('earlywarnings').each(function () {
	   if ( jQuery('.end_date').length>0) {
	     jQuery('.end_date').datepicker();
           }
	   if ( jQuery('.end_date').length>0) {
	     jQuery('.start_date').datepicker();
	   }
           
	  $("#public_earlywarnings_release_sortable").tablesorter({
                headers: {
                    3: {sorter: false}
                },
                showProcessing: true,
                headerTemplate : '{content} {icon}',
                widgets: ['zebra',"pager", 'stickyHeaders'],
                widgetOptions: {
                    stickyHeaders: 'sticky-header',
                    stickyHeaders_offset: $.fn.admin_toolbar(),
                    stickyHeaders_cloneId: '-sticky',
                    stickyHeaders_addResizeEvent: true,
                    stickyHeaders_includeCaption: true,
                    stickyHeaders_zIndex: 2,
                    stickyHeaders_attachTo: null,
                    stickyHeaders_xScroll: null,
                    stickyHeaders_yScroll: null,
                    stickyHeaders_filteredToTop: true
                }
	  });
	  $("#viewearlywarnings_sortable").tablesorter({
                headers: {
                    3: {sorter: false},
                    1: {sorter: 'created_on'}
                },
                showProcessing: true,
                headerTemplate : '{content} {icon}',
                widgets: ['zebra',"pager", 'stickyHeaders'],
                widgetOptions: {
                    stickyHeaders: 'sticky-header',
                    stickyHeaders_offset: $.fn.admin_toolbar(),
                    stickyHeaders_cloneId: '-sticky',
                    stickyHeaders_addResizeEvent: true,
                    stickyHeaders_includeCaption: true,
                    stickyHeaders_zIndex: 2,
                    stickyHeaders_attachTo: null,
                    stickyHeaders_xScroll: null,
                    stickyHeaders_yScroll: null,
                    stickyHeaders_filteredToTop: true
                }
	  });

	  $('#public_earlywarnings_results_wrapper .pager li a').click(function() {
	    var ele = $(this);
	    var url = ele.attr('href');
	    
	    var params = url.split('?')[1];
	    var group_id = Drupal.settings.group_id;
	    var type = Drupal.settings.type;
	    var base_path = Drupal.settings.basePath;
	    
	    url = base_path + 'public_search_earlywarning' + '?' + params;
	    
	    $.post(url, {}, function(data) {
		     if (data.status == true) {
		       $('#public_earlywarnings_results_wrapper').html(data.data);
		       $(window).scrollTop(0);
		       Drupal.attachBehaviors('#public_earlywarnings_results_wrapper');
		     }
		   }, 'json');
	    return false;	
	    
	});
  
/**
	  $('#earlywarnings_results_wrapper .pager li a').click(function() {
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
		       $(window).scrollTop(0);
		       Drupal.attachBehaviors('#earlywarnings_results_wrapper');
		     }
		   }, 'json');
	    return false;
	   });
*/

        });

        $ewpath = $("#earlywarnings-filter-form").attr('action');
        if (typeof $ewpath === 'undefined' || $ewpath === null) {
        } else {
            $("#earlywarnings-filter-form").attr('action', $ewpath + '#earlywarnings_results_wrapper');
        }

      }
   };
})(jQuery);


function reset_form_elements(){
  // var base_path = Drupal.settings.basePath;
  // var group_id = Drupal.settings.group_id;

  // var type = Drupal.settings.type;
  // var path = base_path + 'release-management/view_earlywarnings';
  //var path = base_path + 'node/' + group_id +'/view_earlywarnings';
  
  jQuery('#edit-deployed-services').val(0);
  jQuery('#edit-deployed-releases').val(0);
  jQuery('#edit-deployed-date').val('');

  jQuery('.state_search_dropdown select').val(0);
  jQuery('.service_search_dropdown select').val(0);
  jQuery('.releases_search_dropdown select').val(0);
  jQuery('.filter_start_date input').val('');
  jQuery('.filter_end_date input').val('');
  jQuery('.limit_search_dropdown select').val(0);
  
  // window.location = path;
  url = window.location.href; 
  res = url.split('?');
  window.location.assign(res['0'] + '#earlywarnings_results_wrapper');
  return false;
}
  
