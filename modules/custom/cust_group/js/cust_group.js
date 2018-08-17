(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.cust_group = {
        attach: function (context, settings) {
            $('.edit-ticket-id').click(function () {
                $('.show-ticket-value', $(this).parents('tr')).hide();
                $('.ticket-update-form', $(this).parents('tr')).removeClass('hide');
                return false;
            });
            $('.ticket-update-form button.js-form-submit').click(function () {
                $data = $('.form-text', $(this).parents('form'));

                if ($.trim($data.val()) == "") {
                    $data.val('');
                    return false;
                }
            });
        }
    }
})(jQuery, Drupal, drupalSettings);



