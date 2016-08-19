(function ($, Drupal) {
    Drupal.behaviors.service_data = {
        attach: function (context, settings) {
            // script for check dependent services in maintenance form
            /*var services = Drupal.settings.dependent_service_string;
             if ($.browser.msie && $.browser.version <= 8) {
             var arr = eval('(' + services + ')');
             } else {
             var arr = JSON.parse(services);
             }
             var result;
             Array.prototype.unique = function () {
             var arr = this;
             return $.grep(arr, function (v, i) {
             return $.inArray(v, arr) === i;
             });
             }*/
            /*$(".maintenance_services input").unbind("click").bind("click", function () {
             result = new Array();
             var select_val = $(this).val();
             var checked = $(this).attr("checked");
             var a = arr[select_val];
             if (a) {
             result = dependent_values(select_val);
             uniqueArray = result.unique();
             /*uniqueArray = result.filter(function(elem, pos) {
             return $.inArray(elem, result) == pos;
             });*/

            /* $.each(uniqueArray, function (index, value) {
             if (checked) {
             $('input[value="' + value + '"]').attr('checked', true);
             }
             });
             }
             });*/

            // Recursive function to get dependent services
            function dependent_values(select_val) {
                if (select_val != null) {
                    result.push(select_val);
                }
                if (arr[select_val] == null) {
                    return;
                } else {
                    for (var i = 0; i < arr[select_val].length; i++) {
                        if ($.inArray(arr[select_val][i], result) < 0) {
                            dependent_values(arr[select_val][i]);
                        }
                    }
                }
                return result;
            }

            $(".service-tooltip").unbind("hover").hover(function () {
                var id = $(this).attr('id');
                $(".service-profile-data-" + id).css("display", "block");
            }, function () {
                var id = $(this).attr('id');
                $(".service-profile-data-" + id).css("display", "none");
            });

            $(".service-tooltip").unbind("click").click(function () {
                var id = $(this).attr('id');
                $(".service-profile-data-" + id).css("display", "block");
                $("#close-" + id).css("display", "block");
                //$(this).unbind("mouseenter mouseleave");
                $(".service-profile-data-" + id).attr("class", "data");
            });

            $(".service-profile-close a").unbind("click").click(function () {
                var id = $(this).attr('id');
                var btn_id = id.split("-")[1];
                //$(".service-profile-data-"+btn_id).css("display","none");
                $(this).closest('div.data').css("display", "none");
                $(this).closest('div.data').attr("class", "service-profile-data");
                $(this).closest('div.service-profile-data').addClass("service-profile-data-" + btn_id);
                $("#close-" + btn_id).css("display", "none");
                //$("#close-"+btn_id).parent('label.option').find('.service-tooltip').bind('mouseenter mouseleave');
            });
        }
    };
})(jQuery, Drupal);