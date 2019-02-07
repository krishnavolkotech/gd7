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

            var acc = document.getElementsByClassName('faq-title');
            var i;
            for (i = 0; i < acc.length; i++) {
                acc[i].addEventListener("click", function () {
                    this.classList.toggle("active");
                    var panel = this.nextElementSibling;
                    $(panel).slideToggle("slow");
                });
            }

            $gpath = $("#deployed-releases-filter-form").attr('action');
            if (typeof $gpath === 'undefined' || $gpath === null) {
            } else {
                $("#deployed-releases-filter-form").attr('action', $gpath + '#deployedreleases_posting');
                $rpath = $("#deployed-releases-filter-form a#edit-link").attr('href');
                $("#deployed-releases-filter-form a#edit-link").attr("href", $rpath + '#deployedreleases_posting');
            }
        }
    }
})(jQuery, Drupal, drupalSettings);



