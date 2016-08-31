(function ($, Drupal) {
    Drupal.behaviors.service_data = {
        attach: function (context, settings) {
            $('.reset_all').click(function () {
                /*var form_id = $(this).attr('reset_form_name');
                 $("." + form_id).resetForm();*/
                jQuery('#edit-type').val('select');
                jQuery('#edit-states').val(0);
                jQuery('#edit-services-effected').val(1);
                jQuery('#edit-filter-startdate').val('');
                jQuery('#edit-filter-enddate').val('');
                jQuery('#edit-time-period').val(0);
                jQuery('#edit-string').val('');
                //window.location.reload();
            });
        }
    };
})(jQuery, Drupal);