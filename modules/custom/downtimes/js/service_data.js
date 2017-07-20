(function ($, Drupal) {
    Drupal.behaviors.service_data = {
        attach: function (context, settings) {
            var dependantServices = $.parseJSON(settings.dependantServices);
//            $('#edit-services-effected input[type="checkbox"]').change(function () {
//                if ($(this).is(':checked')) {
//                    var serviceId = $(this).val();
//                    data = $.parseJSON(dependantServices);
//                    $.each(data[serviceId], function (key, val) {
//                        $('#edit-services-effected #edit-services-effected-' + val).prop("checked", true);
//                    });
//                }
//            });
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
            $('#edit-services-effected input[type="checkbox"]').unbind("change").bind("change", function () {
                result = new Array();
                var select_val = $(this).val();
                var checked = $(this).is(":checked");
                var a = dependantServices[select_val];
                if (a) {
                    result = dependent_values(select_val);
                    uniqueArray = $.unique(result);
                    /*uniqueArray = result.filter(function(elem, pos) {
                     return $.inArray(elem, result) == pos;
                     });*/

                    $.each(uniqueArray, function (index, value) {
                        if (checked) {
                            $('#edit-services-effected input[value="' + value + '"]').prop("checked", true);
                        }
                    });
                }
            });

            // Recursive function to get dependent services
            function dependent_values(select_val) {
                if (select_val != null) {
                    result.push(select_val);
                }
                if (dependantServices[select_val] == null) {
                    return;
                } else {
                    for (var i = 0; i < dependantServices[select_val].length; i++) {
                        if ($.inArray(dependantServices[select_val][i], result) < 0) {
                            dependent_values(dependantServices[select_val][i]);
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
                // On click of service profile tool tip, close already open ones.
                $('.service-profile-close a').click();
                // Open the tool tip data
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
            ///moving every third element to its left by 190 px for better visibility
            // var count = 1;
            // $('#edit-services-effected div.form-checkbox').each(function () {
            //     var totaldivs = $("#edit-services-effected").find("div.form-checkbox").length;
            //     var subtotal = (totaldivs/3) * 2;
            //     if(count++ > subtotal) {
            //         $(this).find('div.service-profile-data').css({left: '-250px'});
            //     }
            // });

        }
    };
})(jQuery, Drupal);

