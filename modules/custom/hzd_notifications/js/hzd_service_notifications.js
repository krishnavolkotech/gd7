(function ($, Drupal, drupalSettings) {    
  Drupal.behaviors.hzd_service_notifications = {
    attach: function () {
      $('.update-service-specific-notifications .form-submit').click(function(){
        this.form.clicked_button = this;
      });
      
      $('.update-service-specific-notifications').submit(function(e){
        e.preventDefault();
        var type = this.clicked_button.attributes.hzdaction.value;
        var uid = this.account.value;
        var service_id =  this.services.value;
        var content_type =  this.content_type.value;
        var interval = this.send_interval.value;
        var rel_type = this.rel_type.value;
        if(type == 'delete') {
	        if(confirm(unescape("Diese Benachrichtigung wirklich l%F6schen?"))) {
	        
	        }
	        else {
            // If cancel do nothing.
            return false;
          }
        }
        
        var url = drupalSettings.path.baseUrl + 'update_notifications';
        $.post( url, {'uid': uid, 'type': type, 'service': service_id, 'content_type': content_type, 'interval' :interval, 'rel_type': rel_type}, function() {
	        window.location = window.location.href;
	      }, 'json');
      });

    }
  };
})(jQuery, Drupal, drupalSettings);
