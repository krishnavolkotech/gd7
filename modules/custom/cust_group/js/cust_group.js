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

            $("form#problem-settings-form input#edit-services-all").click(function () {
                $('form#problem-settings-form input:checkbox').not(this).prop('checked', this.checked);
            });

            $("form#problem-settings-form input:checkbox").click(function () {
                if (($(this.id.match(/edit-services\-\d+/))) && ($("form#problem-settings-form input#edit-services-all").prop("checked"))) {
                    if($(this).prop("checked") == false) {
                        $("form#problem-settings-form input#edit-services-all").prop("checked", false);
                    }
                }
                var numberOfChecked = $('form#problem-settings-form input:checkbox:checked').length;
                var totalCheckboxes = $('form#problem-settings-form input:checkbox').length;
                var numberNotChecked = totalCheckboxes - numberOfChecked;
                if(numberNotChecked == 1) {
                    $("form#problem-settings-form input#edit-services-all").prop("checked", true);
                }

            });

	    jQuery('.node-form #edit-submit').prop('disabled', true);
	    var send_notification = jQuery('input[name="node_notification_checkbox"]:checked').val();
	    if (typeof send_notification != 'undefined') {
		jQuery('.node-form #edit-submit').prop('disabled', false);
	    }

	    $("input[name='node_notification_checkbox']").click(function(){
		var send_notification = jQuery('input[name="node_notification_checkbox"]:checked').val();
		if (typeof send_notification != 'undefined') {
		    jQuery('.node-form #edit-submit').prop('disabled', false);
		}
		else {
		    jQuery('.node-form #edit-submit').prop('disabled', true);
		}
	    });
	    

        }
    }
})(jQuery, Drupal, drupalSettings);



