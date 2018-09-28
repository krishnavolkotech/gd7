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
                    //return false;
                }
            });

            var acc = document.getElementsByTagName('caption');
            var i;
            //$( "#block-views-block-groups-faq-block-1 div.table-responsive tbody" ).wrap( "<div class='panel'></div>" );
            $("#block-views-block-groups-faq-block-1 div.table-responsive tbody").hide();
            for (i = 0; i < acc.length; i++) {
                acc[i].addEventListener("click", function () {
                    this.classList.toggle("active");
                    var panel = this.nextElementSibling;
                    $(panel).toggle("fast");
                });
            }
        }
    }
})(jQuery, Drupal, drupalSettings);



