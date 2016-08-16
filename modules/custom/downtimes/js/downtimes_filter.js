(function ($, Drupal) {
    Drupal.behaviors.service_data = {
        attach: function (context, settings) {
            $('.reset_all input').click(function () {
                var form_id = $(this).attr('reset_form_name');
                $("." + form_id).resetForm();
                window.location.reload();
            })
            $('.start_date').datepicker({dateFormat: 'dd.mm.yy'});
            $('.end_date').datepicker({dateFormat: 'dd.mm.yy'});
        }
    };
})(jQuery, Drupal);