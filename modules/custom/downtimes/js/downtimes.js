var temp=true;

Drupal.behaviors.downtimes = function () {
  var options = {
  beforeSubmit: function(formData, jqForm, options) {
    $('<div class="ahah-progress ahah-progress-throbber"><div class="throbber">&nbsp;</div></div>').prependTo($('.search_string_submit'));
     return true;
  },
  success: function (data) {
  if (data.status == true) {
    $('#archived_maintenance_search_results_wrapper').html(data.data);
    $('.ahah-progress').remove();
       Drupal.attachBehaviors('#archived_maintenance_search_results_wrapper');
     }
  },
  dataType:'json' };
  $('#downtimes-filters').ajaxForm(options);
  $('.filter_submit').hide();
  $('.start_date').change(function(){$(".time_period_date option[value='0']").attr('selected', 'selected'); })
  $('.end_date').change(function(){$(".time_period_date option[value='0']").attr('selected', 'selected'); })
  $('.search_string').focus(clear_textfield);
  var browser=navigator.appName;

  if (browser == "Microsoft Internet Explorer") {
    $('.string_search input').keyup(search_string);
  }
  $('.search_string').blur(text_textfield);
  $(".expand").after("<div class='error_div'></div>");
      $.fn.admin_toolbar = function () {
        if($('#toolbar-administration').length) {
            return 80;
        } else {
            return 0;
        }
      }
  $("#sortable").tablesorter({
        headers: {
            4: {
                sorter: false
            }
        },
        showProcessing: true,
        headerTemplate : '{content} {icon}',
        widgets: ['zebra','stickyHeaders'],
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


  /*back to search */
  $('.downtimes_details_link').click(function(){
    var query = $(this).attr('query');
    var nid = $(this).attr('nid');
    var url = Drupal.settings.basePath + 'back_to_search';
    $.post(url, {'from': 'downtimes', 'query':query}, function(data) {
      if (data.status == true) {
 	 window.location.href = Drupal.settings.basePath + 'node/' + nid;
       }
    }, 'json');
     return false;
  });

  // ajaxifying pagination
  $('.pager li a').click(function() {
     var ele = $(this);
     var url = ele.attr('href');
     var params = url.split('?')[1];
     var limit = $('.limit_search_dropdown select').val()
     var group_id = Drupal.settings.group_id;
     var type = Drupal.settings.type;
     var base_path = Drupal.settings.basePath;			 
     if (group_id) {
       url = base_path + 'node/' + group_id + '/pagination_results/' + '?' + params;
     }
     else{
        url = base_path + 'pagination_results/' + '?' + params;
     }	
     $.post(url, {'limit':limit}, function(data) {
       if (data.status == true) {
	  $('#archived_maintenance_search_results_wrapper').html(data.data);
          $(window).scrollTop(0);
	   Drupal.attachBehaviors('#archived_maintenance_search_results_wrapper');
       }
     }, 'json');
      return false;
  });
  $('.limit_search_dropdown select').ajaxComplete(function() {
    $(window).scrollTop(0);
  });
}

function search_string(e)  {
  if(e.keyCode == 13) {
    $('#downtimes-filters').submit();	
    return false;
  }
}

function clear_textfield(){
  this.value = '';
}

function text_textfield(){
  var string = Drupal.settings.search_string;
  if(this.value == '') {
    this.value = string;
  }
}

function expand(){
  if(temp){
	temp=false;
	$(".collapsible").each(function(i){$(this).removeClass("collapsed");});	
  }else{
      temp=true;
	$(".collapsible").each(function(i){$(this).addClass("collapsed");});
  }
}
function validate(){
    $(".error_div").html("");
    $(".error_div").css("color", "red")	      		
    var selector_checked = $("input[@class=form-checkbox]:checked").length;
   if (selector_checked !=0){
      return true;
    }
    else{
       	var message_area = $(".error_div");
        message_area.html("select atleast one service");
        message_area.get(0).scrollIntoView(true);
      
	return false;
    }
}

function checkall(id){
 $('input[@type=checkbox]').each(function(){ if($(this).val()==id){$(this).attr('checked', 'true')}});
 return false;
}
function uncheckall(id){
 $('input[@type=checkbox]').each(function(){ if($(this).val()==id){$(this).attr('checked',false)}});
 return false;	
}
