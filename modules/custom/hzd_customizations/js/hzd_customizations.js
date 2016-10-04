(function ($) {
    Drupal.behaviors.hzd_customizations = {
        attach: function (context, settings) {

            if ($.browser.msie && $.browser.version >= 10) {
                $("body").addClass("ie10");
            }
            if (navigator.userAgent.match(/Trident.*rv:11\./)) {
                $("body").addClass("ie11");
            }

            $("#CheckboxAll").appendTo(".field-recipients-checkbox");

            $('#edit-submit-og').attr('value', '');
            /*    $('#edit-persistent-login', context).click(function() {
             if(this.checked == true) {
             alert("Sie sollten diese Option sicherheitshalber nur auf Ihrem eigenen Computer anschalten.");
             }
             });
             */
            $('.search-admin-form-keys', context).focus(function () {
                if ($(this).val() == $(this).attr('default_value')) {
                    $(this).val('');
                }
            }).blur(function () {
                if ($(this).val().trim() == '') {
                    $(this).val($(this).attr('default_value'));
                }
            });

            $('.user_search_field', context).focus(function () {
                $(this).val('');
            }).blur(function () {
                $(this).val($(this).attr('default_value'));
            });

            $("#edit-og-private").click(function () {
                if (!$("#edit-og-private:checked").val()) {
                    $("#edit-og-directory").attr('checked', 'true');
                }
            });

//            // Check and uncheck all for quickinfo checkboxes.
//            $("#CheckboxAll").click(function () {
//                $('.field-recipients-checkbox input').each(function () {
//                    var checked = $("#CheckboxAll input:checked").val();
//                    if (checked == 1) {
//                        $(this).attr('checked', 'true');
//                    } else {
//                        $(this).removeAttr('checked');
//                    }
//                });
//            });

            $('#attachments a').attr({target: "_blank"});

            $(".notifications-disable-button").click(function () {
                alert(Drupal.t("You have disabled all your notifications. Enable your notifications"));
                return false;
            });

            var string = location.pathname;
            var args = string.split("/");
            if (location.pathname == '/autoren-rz-schnellinfo/rz-schnellinfos/erstellen' || location.pathname == '/autoren-rz-schnellinfo/add/rz-schnellinfo' || location.pathname == '/autoren-rz-schnellinfo/add/quickinfo' || (args[1] == 'autoren-rz-schnellinfo' && (args[2] == 'rz-schnellinfos' || args[2] == 'quickinfo') && args[4] == 'edit')) {

                $("#publish_content").click(function () {
                    $(".confirm_message").css("display", "inline-block");
                    $(".blur-div").height($("body").height() + 100);
                    $(".blur-div").css("display", "inline-block");
                    return false;
                });

                $("#confirm").click(function () {
                    $(".confirm_message, .blur-div").css("display", "none");
                    $("#node-form").submit();
                });

                $("#cancel").click(function () {
                    $(".confirm_message, .blur-div").css("display", "none");
                    return false;
                });
            }

        }
    };
})(jQuery);





// automatically click on upload button when browse a file to override the existing planning file.
//$(document).ready(function () {
//    $('#edit-field-upload-planning-file-0-upload-wrapper input.form-file').live('change', function () {
//        $('#edit-field-upload-planning-file-0-filefield-upload').mousedown();
//    });
//});
