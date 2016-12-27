(function ($) {
  Drupal.behaviors.problem_management = {
    attach: function (context, settings) {

        jQuery.fn.extend({
    	  removeajaxparameter: function () {
              this.each(function( index ) {
              var links = $( this ).attr('href'); 
              var new_href = links.replace('ajax_form=1&_wrapper_format=drupal_ajax', '');   
              $( this ).attr('href', new_href);
            });
          }
        });

           var anchor = $('#import_search_results_wrapper > #pagination > nav > ul > li > a');
             anchor.each(function( index ) {
               var links = $( this ).attr('href'); 
               var new_href = links.replace('ajax_form=1&_wrapper_format=drupal_ajax', '');   
               $( this ).attr('href', new_href);
             });

   
        $(context).find('table#sortable').once('problem_management').each(function () {
           // $("#import_search_results_wrapper > nav > ul > li > a").removeajaxparameter();
           // $("#problem_search_results_wrapper > nav > ul > li > a").removeajaxparameter();

           var anchor = $("#pagination > nav > ul > li > a");
             anchor.each(function( index ) {
               var links = $( this ).attr('href'); 
               var new_href = links.replace('ajax_form=1&_wrapper_format=drupal_ajax', '');   
               $( this ).attr('href', new_href);
             });
           
         // #pagination > nav > ul > li > a
         // $('body').once('problem_management').each(function () {
        $.tablesorter.addParser({
          // set a unique id
          id: 'date_sorting',
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
	            //console.log(date);
	            return parseInt(date,10);	     
	          }
          },
          // set type, either numeric or text
          type: 'numeric'
        });
        var options = {
          beforeSubmit: function(formData, jqForm, options) {
            $('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>').prependTo($('.search_string_submit'));
	          return true;
          },
          success: function (data) {
          if (data.nid) {
            window.location.href = drupalSettings.problem_management.basePath + 'node/' + data.nid;
          }
          if (data.status == true) {
            $('#problem_search_results_wrapper').html(data.data);
            $('.ajax-progress').remove();
            Drupal.attachBehaviors('.content ');
          }
        },
        type: 'POST',
        dataType:'json'
      };

      $('#problems-filter-form').ajaxForm(options);  
	    $("#sortable").tablesorter({
	      headers: {
	      	6: {
	  	      sorter: 'date_sorting'
	  	    }
	      }
	    });
	    $('.filter_submit').hide();  
	    $('.search_string').blur(text_textfield);
	    $('.search_string').focus(function() { $(this).val('') });  

//	    $('#problem_search_results_wrapper #pagination > nav > ul > li > a').click(function() {
//	      var ele = $(this);
//	      var url = ele.attr('href');
//	      var params = url.split('?')[1];
//	      var group_id = drupalSettings.problem_management.group_id;
//	      var type = drupalSettings.problem_management.type;
//	      var base_path = drupalSettings.problem_management.basePath;
//	      url = base_path + '/group/' + group_id + '/problem_search_results/' + type + '?' + params;
//	      $.post(url, {}, function(data) {
//		if (data.status == true) {
//		  $('#problem_search_results_wrapper').html(data.data);
//		  $(window).scrollTop(0);
//		  Drupal.attachBehaviors('#problem_search_results_wrapper');
//		}
//	      }, 'json');
//	      return false;
//	    });

//	    $('.problems_details_link').click(function(){
//	      var query = $(this).attr('query');
//	      var nid = $(this).attr('nid');
//	   //   var url = drupalSettings.problem_management.basePath + 'back_to_search';
//	      $.post(url, {'from':'problems','query':query}, function(data) {
//		if (data.status == true) {
//		  window.location.href = drupalSettings.problem_management.basePath + 'node/' + nid;
//		}
//	      }, 'json');
//	      return false;
//	    });

//        $('#import_search_results_wrapper .pager li a').click(function() {
//          var ele = $(this);
//          var url = ele.attr('href');
//          var params = url.split('?')[1];
//          var group_id = drupalSettings.problem_management.group_id;
//          var type = drupalSettings.problem_management.type;
//          var base_path = drupalSettings.problem_management.basePath;
//          url = base_path + 'import_search_results' +  '?' + params;
//          $.post(url, {}, function(data) {
//            if (data.status == true) {
//              $('#import_search_results_wrapper').html(data.data);
//              $(window).scrollTop(0);
//              Drupal.attachBehaviors('#import_search_results_wrapper');
//            }
//          }, 'json');
//          return false;
//        });
      });
      var nid = drupalSettings.problem_management.nid;
      if (typeof nid !== "undefined") {
        window.location.href = drupalSettings.problem_management.basePath + '/node/' + nid;
      } 
    }
  };
})(jQuery);

function search_string(e)  {
  if(e.keyCode == 13) {
    $('#problems-filter-form').submit();	
    return false;
  }
}

function clear_textfield(){
  this.value = '';
}

function text_textfield() {
  var string = drupalSettings.problem_management.search_string;
  if(this.value == '') {
    this.value = string;
  }
}

function reset_form_elements() {
  var res;
  jQuery('#edit-service').val(0);
  jQuery('#edit-function').val(0);
  jQuery('#edit-release').val(0);
  jQuery('#edit-string').val('');
  jQuery('#edit-limit').val(20);
  url = window.location.href; 
  res = url.split('?');
  window.location.assign(res['0']);
  return false;
}
