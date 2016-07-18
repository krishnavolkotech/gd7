(function ($, Drupal, drupalSettings) {    
  Drupal.behaviors.services = {
    attach: function (context, settings) {
    
      $('.downtime_check_form input').click(function(){
        var id = $(this).attr('node_id');
        var status;
        $("."+id).show();
        if(this.checked) {
          status = 'update';
          update_service(id, status);  
        }
        else {
          status = 'delete';
          update_service(id, status);  
        }
      });

      function update_service(id, status) {
        window.location.href = drupalSettings.path.baseUrl + 'service_notifications_update/' + id + '/' + status;
      }

    }
  }
})(jQuery, Drupal, drupalSettings);
