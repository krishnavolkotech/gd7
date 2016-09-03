Drupal.behaviors.maintenance_windows = function(context) {
  var service_maintenance_data = Drupal.settings.downtimes.service_maintenance_data;
  if(service_maintenance_data != '') {
     var d_items_count = Drupal.settings.downtimes.service_maintenance_data.howmany;
     d_remove_link = 0;
     if($('#maintenancewindows fieldset div.mw-item-set:first').find('.mw-remove').length == 0) {
          $('#maintenancewindows fieldset div.mw-item-set:first').append('<a id="remove-1" class="mw-remove" href="">Remove</a>');
          d_remove_link = 1;
          $('#maintenancewindows fieldset div.mw-item-set:first').find(".mw-remove").bind("click", function(e) {
              remove_item_click(e);
          });
      }
     for(var inc = 2; inc <= d_items_count; inc++) {
         if(jQuery('#edit-mw-day-from'+inc).length <= 0) {
            var cloneobj = $('#maintenancewindows fieldset div.mw-item-set:first').clone();
            $('#maintenancewindows fieldset').append(cloneobj);
            var lastobj = $('#maintenancewindows fieldset div.mw-item-set:last');
            change_item_order(lastobj, inc, d_remove_link, 0);
         }
         jQuery('#edit-mw-day-from'+inc).val(Drupal.settings.downtimes.service_maintenance_data[inc].day_from);
         jQuery('#edit-mw-day-until'+inc).val(Drupal.settings.downtimes.service_maintenance_data[inc].day_until);
         jQuery('#edit-mw-hm-from'+inc+'-hour').val(Drupal.settings.downtimes.service_maintenance_data[inc].hm_from_hour);
         jQuery('#edit-mw-hm-from'+inc+'-minute').val(Drupal.settings.downtimes.service_maintenance_data[inc].hm_from_minute);
         jQuery('#edit-mw-hm-until'+inc+'-hour').val(Drupal.settings.downtimes.service_maintenance_data[inc].hm_until_hour);
         jQuery('#edit-mw-hm-until'+inc+'-minute').val(Drupal.settings.downtimes.service_maintenance_data[inc].hm_until_minute);
     }
  }
  $('.mw-remove').click(function(e) {
      remove_item_click(e, this);
  });
  $('#edit-addmore').click(function(e) {
     var num_items = $("#edit-howmany").val();
     num_items = parseInt(num_items)+1;
     add_items(num_items);
     e.preventDefault();
  });
}
function add_items(num_items) {
   var cloneobj = $('#maintenancewindows fieldset div.mw-item-set:first').clone();
   var add_remove_link = 0;
   if(num_items > 1) {
      if($('#maintenancewindows fieldset div.mw-item-set:first').find('.mw-remove').length == 0) {
          $('#maintenancewindows fieldset div.mw-item-set:first').append('<a id="remove-1" class="mw-remove" href="">Remove</a>');
          add_remove_link = 1;
          $('#maintenancewindows fieldset div.mw-item-set:first').find(".mw-remove").bind("click", function(e) {
              remove_item_click(e);
          });
      }
   }   
   $('#maintenancewindows fieldset').append(cloneobj);

   var howmany = $('#edit-howmany').val();
   howmany = parseInt(howmany)+1;
   $('#edit-howmany').val(howmany);

   var lastobj = $('#maintenancewindows fieldset div.mw-item-set:last');
   change_item_order(lastobj, num_items, add_remove_link, 1);
}
function change_item_order(lastobj, item_no, add_remove_link, reset) {

   var selectobj1 = lastobj.find("select[id*='edit-mw-day-from']");
   selectobj1.attr("id", "edit-mw-day-from"+item_no);
   selectobj1.attr("name", "mw_day_from"+item_no);
   if(reset == 1) {
      selectobj1.val('Mon');
   }

   var selectobj2 = lastobj.find("select.date-hour[id*='edit-mw-hm-from']");
   selectobj2.attr("id", "edit-mw-hm-from"+item_no+"-hour");
   selectobj2.attr("name", "mw_hm_from"+item_no+"[hour]");
   if(reset == 1) {
      selectobj2.val('');
   }

   var selectobj3 = lastobj.find("select.date-minute[id*='edit-mw-hm-from']");
   selectobj3.attr("id", "edit-mw-hm-from"+item_no+"-minute");
   selectobj3.attr("name", "mw_hm_from"+item_no+"[minute]");
   if(reset == 1) {
      selectobj3.val('');
   }

   var selectobj4 = lastobj.find("select[id*='edit-mw-day-until']");
   selectobj4.attr("id", "edit-mw-day-until"+item_no);
   selectobj4.attr("name", "mw_day_until"+item_no);
   if(reset == 1) {
      selectobj4.val('Mon');
   }

   var selectobj5 = lastobj.find("select.date-hour[id*='edit-mw-hm-until']");
   selectobj5.attr("id", "edit-mw-hm-until"+item_no+"-hour");
   selectobj5.attr("name", "mw_hm_until"+item_no+"[hour]");
   if(reset == 1) {
      selectobj5.val('');
   }

   var selectobj6 = lastobj.find("select.date-minute[id*='edit-mw-hm-until']");
   selectobj6.attr("id", "edit-mw-hm-until"+item_no+"-minute");
   selectobj6.attr("name", "mw_hm_until"+item_no+"[minute]");
   if(reset == 1) {
      selectobj6.val('');
   }

   if(add_remove_link == 1) {
      lastobj.append('<a id="remove-'+item_no+'" class="mw-remove" href="">Remove</a>');
   }else {
      var remove_link = lastobj.find('.mw-remove');
      remove_link.attr('id', 'remove-'+item_no);
   }
   lastobj.find(".mw-remove").bind("click", function(e) {
       remove_item_click(e, this);      
   });
   //Drupal.attachBehaviors($('.mw-remove'));
}
function remove_item_click(e, obj) {
   var cfm = confirm("Are you sure you want to remove it ?");
   if(cfm)  {
     var howmany = $('#edit-howmany').val();
     howmany = parseInt(howmany)-1;
     $('#edit-howmany').val(howmany);
     $(obj).parent('.mw-item-set').remove();
     if(howmany == 1) {
          $('#maintenancewindows fieldset div.mw-item-set:first').find('.mw-remove').remove();
     }
     var index = 1;
     $('#maintenancewindows fieldset div.mw-item-set').each(function() {
         change_item_order($(this), index, 0, 0);
         index++;
     });
   }
   e.preventDefault();
}
