(function ($, Drupal, drupalSettings) {    
  Drupal.behaviors.hzd_sams_notifications = {
    attach: function () {
      $('.update-sams-notifications .form-submit').click(function(){
        this.form.clicked_button = this;
      });
      
      $('.update-sams-notifications').submit(function(e){
        e.preventDefault();
        var type = this.clicked_button.attributes.hzdaction.value;
        var id = this.id.value;
        if(type == 'delete') {
          if(confirm(unescape("Diese Benachrichtigung wirklich l%F6schen?"))) {
          
          }
          else {
            // If cancel do nothing.
            return false;
          }
        }
        
        var url = drupalSettings.path.baseUrl + 'update_sams_notifications';
        $.post( url, {'type': type, 'id': id}, function() {
          window.location = window.location.href;
        }, 'json');
        return false;
      });

    }
  };
})(jQuery, Drupal, drupalSettings);
