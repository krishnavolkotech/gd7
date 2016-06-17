(function ($) {
  Drupal.behaviors.release_management = {
    attach: function (context, settings) {

//    $(context).find('div#released_results_wrapper').once('release_management').each(function () {
//      alert('kjdfkjhsd');

           var anchor = $("#released_results_wrapper #pagination > nav > ul > li > a");
             anchor.each(function( index ) {
               var links = $( this ).attr('href'); 
               var new_href = links.replace('ajax_form=1&_wrapper_format=drupal_ajax', '');   
               $( this ).attr('href', new_href);
             });

        // Drupal.behaviors.release_management = function (context) {
        $.tablesorter.addParser({
          // set a unique id
          id: 'release_date',
          is: function(s) {
            // return false so this parser is not auto detected
            return false;
          },
          format: function(s) {
		    // format your data for normalization 
		    var mydatetime = s.split(' ');
                    if (typeof(mydatetime[0]) != "undefined" && mydatetime[0] !== null &&
                      typeof(mydatetime[1]) != "undefined" && mydatetime[1] !== null ) {
		      var dateele = mydatetime[0].split('.');
		      var timeele = mydatetime[1].split(':');
        	     // console.log(dateele);
		     //adding 20 if date is formatted in only YY format.
		     if (typeof(dateele[2]) != "undefined" && dateele[2] !== null && dateele[2].length == 2) {
		        dateele[2] = '20' + dateele[2];
		     }
		     var date = dateele[2] + dateele[1] + dateele[0] + timeele[0] + timeele[1];
		     return parseInt(date,10);
            }
          },
          // set type, either numeric or text
          type: 'numeric'
       });

       $("#sortable").tablesorter({
         headers: {
	  6: {
        	sorter: false
	     },
	  3: {sorter: 'release_date'}
         }
       });  
  
       $('.filter_submit', context).hide();	
       $('.pager li a', context).click(function() {
	 var ele = $(this);
	 var url = ele.attr('href');

         if (typeof(url) != "undefined" && url !== null) {
           var params = url.split('?')[1];
         }

	 var group_id = Drupal.settings.group_id;
	 var type = Drupal.settings.type;
	 var base_path = Drupal.settings.basePath;
	 // url = '/' + path + '/releases_search_results?'+params;	
	 url = base_path + 'node/' + group_id + '/releases_search_results/' + type + '?' + params;
	 // url = window.location +'?'+params;
	 $.post(url, {}, function(data) {
	    if (data.status == true) {
		$('#released_results_wrapper').html(data.data);
		Drupal.attachBehaviors('#released_results_wrapper');
	    }
	 }, 'json');
	 return false;
        });
  //    });
    }
  };
})(jQuery);


function reset_form_elements(){ 
  jQuery('.service_search_dropdown select').val(0);
  jQuery('.releases_search_dropdown select').val(0);
  jQuery('.filter_start_date input').val('');
  jQuery('.filter_end_date').val('');
  jQuery('.limit_search_dropdown select').val(0);
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
