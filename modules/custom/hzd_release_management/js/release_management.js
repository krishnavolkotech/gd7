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
  Drupal.behaviors.release_management = {
    attach: function (context) {
        
          var anchor = $("#released_results_wrapper #pagination > nav > ul > li > a");
             anchor.each(function( index ) {
               var links = $( this ).attr('href'); 
               var new_href = links.replace('ajax_form=1&_wrapper_format=drupal_ajax', '');   
               $( this ).attr('href', new_href);
             });
           
            $(context).find("#quickinfo-sortable").tablesorter({
                5:{sorter:'deployed_date'},
                widgets: ['zebra']
            });
            
            $.tablesorter.addParser({
                  // set a unique id
                  id: 'release_datesortable',
                  is: function(s) {
                      // return false so this parser is not auto detected
                      return false;
                  },
                  format: function(s) {
                  // format your data for normalization 
                     if (s) {
                       var mydatetime = s.split(' ');
          	       if (mydatetime[0] && mydatetime[1]) {	         
          		 var dateele = mydatetime[0].split('.');
          		 var timeele = mydatetime[1].split(':');
          		 
          		 //adding 20 if date is formatted in only YY format.
          		 if (dateele[2].length == 2) {
          		   dateele[2] = '20' + dateele[2];
          		 }
          		 var date = dateele[2] + dateele[1] + dateele[0] + timeele[0] + timeele[1] + timeele[2];
          		 return parseInt(date,15);
          	       }
          	     
          	   }
                  },
                  // set type, either numeric or text
                  type: 'numeric'
              });


            $.tablesorter.addParser({
                  // set a unique id
                  id: 'release_date',
                  is: function(s) {
                      // return false so this parser is not auto detected
                      return false;
                  },
                  format: function(s) {
          	    // format your data for normalization 
                     if (s) {
          	     var type = drupalSettings.release_management.type;
          	     //if (type != 'released') {
          	       var mydatetime = s.split(' ');
          	       if (mydatetime[0] && mydatetime[1]) {	         
          		 var dateele = mydatetime[0].split('.');
          		 var timeele = mydatetime[1].split(':');
          		 
          		 //adding 20 if date is formatted in only YY format.
          		 if (dateele[2].length == 2) {
          		   dateele[2] = '20' + dateele[2];
          		 }
          		 var date = dateele[2] + dateele[1] + dateele[0] + timeele[0] + timeele[1] + timeele[2];
          		 return parseInt(date,10);
          	       }
          	     //} 
          	     /*else {
          	     $("#sortable").attr('id', 'release_sortable');
          	       var mydatetime = s.split('.');
          	       if (mydatetime[0] && mydatetime[1] && mydatetime[2] ) {
          		 if (mydatetime[2].length == 2) {
          		   mydatetime[2] = '20' + dateele[2];
          		 }
          		 var date = mydatetime[2] + mydatetime[1] + mydatetime[0];
          		 return parseInt(date,10);
          	       }
          	       else {
          		 return 0;
          	       }
          	     }*/


          	   }
                  },
                  // set type, either numeric or text
                  type: 'numeric'
              });



            if (drupalSettings.release_management.type != 'released' && drupalSettings.release_management.type != "deployed") {
            // jQuery("#sortable").tablesorter();
             
              $(context).find("#sortable").tablesorter({
          	widgets: ['zebra']
              }); 
            
            }
            else if (drupalSettings.release_management.type == "deployed") {
                $.tablesorter.addParser({ 
                  id: "deployed_date", 
                  is: function(s) { 
                      return false; 
                  }, 
                  format: function(s,table) { 
                      s = s.replace(/\-/g,"/"); 
                      s = s.replace(/(\d{1,2})[\/\.](\d{1,2})[\/\.](\d{4})/, "$3/$2/$1");                            
                      return $.tablesorter.formatFloat(new Date(s).getTime()); 
                  }, 
                  type: "numeric" 
               });
               
                $(context).find("#sortable").tablesorter({
                dateFormat: 'dd.mm.yyyy',
                headers: {
                    4:{sorter:'deployed_date'},
                    5: {
                        sorter: false
                            },
                    6: {
                        sorter: false
                            },
                  },

              sortList: [[0,0],[1,0],[2,0],[4,0],[5,0]],
              widgets: ['zebra']
                  });
                  
            }
            else {
               
              $(context).find("#sortable").tablesorter({
                    headers: {
                        2: { sorter:'release_datesortable' },
                        3: { sorter: false },
                        4: { sorter: false },
                    },
                       widgets: ['zebra']
                    });
            }

            $('.filter_submit', context).hide();

            $('.public_releses_output .pager li a', context).click(function() {
          	var ele = $(this);
          	var url = ele.attr('href');
          	
          	var params = url.split('?')['1'];
          	var group_id = drupalSettings.release_management.group_id;
          	var type = drupalSettings.release_management.type;
          	var base_path = drupalSettings.release_management.base_path;
                
          	url = base_path + '/releases_search_results/' + type + '?' + params;	
          	$.post(url, {}, function(data) {
          	    if (data.status == true) {
          		$('#released_results_wrapper').html(data.data);
                          $(window).scrollTop(0);
          		Drupal.attachBehaviors('#released_results_wrapper');
          	    }
          	}, 'json');
          	return false;
              });
          	
            /*$('.quickinfo_content_output .pager li a', context).click(function() {
          	var ele = $(this);
          	var url = ele.attr('href');
          	
          	var params = url.split('?')[1];
          	var group_id = Drupal.settings.group_id;
          	var base_path = Drupal.settings.basePath;
          	url = base_path + 'filter_quickinfo/' +  '?' + params;	
          	$.post(url, {}, function(data) {
          	    if (data.status == true) {
          		$('#released_results_wrapper').html(data.data);
                          $(window).scrollTop(0);
          		Drupal.attachBehaviors('#released_results_wrapper');
          	    }
          	}, 'json');
          	return false;
              })*/

            $('.releses_output .pager li a', context).click(function() {
          	var ele = $(this);
          	var url = ele.attr('href');
          	
          	var params = url.split('?')[1];
                var group_id = drupalSettings.release_management.group_id;
                var type = drupalSettings.release_management.type;
                var base_path = drupalSettings.release_management.base_path;
          	url = base_path + '/group/' + group_id + '/releases_search_results/' + type + '?' + params;	
          	$.post(url, {}, function(data) {
          	    if (data.status == true) {
          		$('#released_results_wrapper').html(data.data);
                          $(window).scrollTop(0);
          		Drupal.attachBehaviors('#released_results_wrapper');
          	    }
          	}, 'json');
          	return false;
              });

         // };
         
     }
  };

})(jQuery, Drupal);

/**
function reset_form_elements(){ 
  $('.service_search_dropdown select').val(0);
  $('.releases_search_dropdown select').val(0);
  $('.filter_start_date input').val('');
  $('.filter_end_date input').val('');
  $('.limit_search_dropdown select').val(0);
  window.location.reload();
}
*/

function reset_form_elements(){ 
  jQuery('#edit-release-type').val();
  jQuery('#edit-services').val(0);
  jQuery('#edit-releases').val(0);
  jQuery('#edit-filter-startdate').val('');
  jQuery('#edit-filter-enddate').val(0);
  jQuery('#edit-limit').val(0);
  url = window.location.href; 
  res = url.split('?');
  window.location.assign(res['0']);
  return false;
}


/*  
Drupal.behaviors.release_management = function(context) {
 
  alert("HHHH");
  // ajaxifying pagination


$('.pager li a', context).click(function() {
    var ele = $(this);
    var url = ele.attr('href');
    var path= url.split('/')[1];
    
    var params = url.split('?')[1];
    console.log(params);
    url = '/' + path + '/releases_search_results?'+params;  
    //url = window.location +'?'+params;
    $.post(url, {}, function(data) {
       if (data.status == true) {
         $('.releses_output').html(data.data);
         Drupal.attachBehaviors('.releses_output');
       }
     }, 'json');
    return false;
   });

};
  */
