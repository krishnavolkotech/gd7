(function ($, Drupal) {
    Drupal.behaviors.hzd_downtimes = {
        attach: function (context, settings) {
            var a = settings.downtime;
            $('.reason-for-noncompliance').find('label').next('span.form-required').remove()
            $('.reason-for-noncompliance').find('label').after('<span class="form-required"></span>');
            if ($('#edit-reason-for-noncompliance').val() == 0)
                $('.reason-for-noncompliance').hide();
            function convert_to_valid_format(date) {
                var temp = date.split('.');
                var remove_white_space = temp['2'].replace(/-+/g, '');
                date = temp['1'] + '/' + temp['0'] + '/' + remove_white_space;
                return date;
            }
            window.onerror = function () {
                // Return true to tell IE we handled it
                return true;
            };
            function today_date() {

                var today_format = new Date();
                var month = today_format.getMonth() + 1;
                var year = today_format.getFullYear();
                var day = today_format.getDate();
                var hours = today_format.getHours();
                var min = today_format.getMinutes();
                var today = month + "/" + day + "/" + year + "  " + hours + ":" + min;
                return today;

            }

            function present_date() {
                var today_format = new Date();
                var present_month = today_format.getMonth() + 1;
                var present_year = today_format.getFullYear();
                var present_day = today_format.getDate() - 1;
                var present = present_month + "/" + present_day + "/" + present_year + " 23:59";
                return present;
            }
            $('form.node-downtimes-form,form.node-downtimes-edit-form').submit(function () {
                if ($('.reason-for-noncompliance').is(':visible') && $('#edit-reason-for-noncompliance').val() == 0) {
                    $('.reason-for-noncompliance').find('.reason-error').show();
                    $('#edit-reason-for-noncompliance').focus();
                    $('.form-submit').removeAttr("disabled");
                    return false;
                } else {
                    $('.form-submit').attr('disabled', true);
                    $('.reason-for-noncompliance').find('reason-error').hide();
                }
            });

            // Advance Time validations.
            $('input#edit-startdate-planned').focusout(function () {
                var start_date = convert_to_valid_format($(this).val());
                var checkbox_count = $("#edit-services-effected input:checked").length;
                if (checkbox_count) {
                    //Getting today's timestamp.
                    var today = today_date();
                    var present = present_date();
                    var diff = toTimestamp(start_date) - toTimestamp(today);
                    var max_adv_time = new Array();
                    if (toTimestamp(start_date) < toTimestamp(today) && $(this).parents('form#node-downtimes-form').length) {
                        $('button.form-submit,button#edit-preview').attr('disabled', 'true');
                        $('.downtimes-date-wrapper p.text-danger, .downtimes-date-wrapper p.text-warning').remove();
                        $('input#edit-startdate-planned').parents('div.start-date-wrapper').append('<p class="text-danger">' + Drupal.t('Das Startdatum muss in der Zukunft liegen.') + '</p>');
                        $('input#edit-startdate-planned').addClass('text-danger');
                        return;
                    }
                    $("#edit-services-effected input:checkbox:checked").each(function () {
                        var service_id = $(this).val();
                        if (a.advance_time[service_id] instanceof Object) {
                            if (a.advance_time[service_id].adv_time)
                                max_adv_time.push(a.advance_time[service_id].adv_time * 60);
                        }
                    });

                    if (max_adv_time.length) {
                        max_adv_time = Math.max.apply(Math, max_adv_time);
                    } else {
                        max_adv_time = a.sitewide_adv;
                    }

                    $("#edit-services-effected input:checkbox:checked").each(function () {
                        var service_id = $(this).val();
                        if($(this).parents('form#node-downtimes-form').length){
                        if (a.advance_time[service_id].adv_time) {
                            var adv_in_seconds = a.advance_time[service_id].adv_time * 60 * 60;
                            if (diff >= adv_in_seconds) {
                                $('button.form-submit,button#edit-preview').removeAttr('disabled');
                                $('.downtimes-date-wrapper p.text-danger,.downtimes-date-wrapper p.text-warning').remove();
                                $('input#edit-startdate-planned').removeClass('text-danger');
                                $('input#edit-maintenance-type').val("R");
                                $('input#edit-startdate-planned').removeClass('text-warning');
                            } else if ((diff <= adv_in_seconds) && (diff >= (a.sitewide_adv * 60))) {
                                $('button.form-submit,button#edit-preview').removeAttr('disabled');
                                $('input#edit-maintenance-type').val("I");
                                $('.downtimes-date-wrapper p.text-danger, .downtimes-date-wrapper p.text-warning').remove();
                                $('input#edit-startdate-planned').removeClass('text-danger');
                                $('input#edit-startdate-planned').addClass('text-warning');
                                $('input#edit-startdate-planned').parents('div.start-date-wrapper').append('<p class="text-warning">' + Drupal.t('Please note: The proposed schedule violates the service-specific SLA according to which maintenances have to be scheduled at least ') + (max_adv_time / 60) + Drupal.t(' hours in advance.') + '</p>');
                            } else {
                                $('button.form-submit,button#edit-preview').attr('disabled', 'true');
                                $('.downtimes-date-wrapper p.text-danger, .downtimes-date-wrapper p.text-warning').remove();
                                $('input#edit-startdate-planned').parents('div.start-date-wrapper').append('<p class="text-danger">' + Drupal.t('Please note: Maintenances have to be scheduled at least ') + (a.sitewide_adv) + Drupal.t(' minutes before the actual start. If you need to perform immediate maintenance work, please ') + "<a href='/incident-management/stoerungen/melden'>" + Drupal.t(' report an Incident instead') + "</a>" + Drupal.t('.') + '</p>');
                                $('input#edit-startdate-planned').addClass('text-danger');
                            }
                        }

                        // Else for if no service values are not enabled.
                        else {
                            if ((diff >= (max_adv_time * 60))) {
                                $('button.form-submit,button#edit-preview').removeAttr('disabled');
                                $('.downtimes-date-wrapper p.text-danger,.downtimes-date-wrapper p.text-warning').remove();
                                $('input#edit-startdate-planned').removeClass('text-danger');
                                $('input#edit-maintenance-type').val("R");
                                $('input#edit-startdate-planned').removeClass('text-warning');
                            } else if ((diff <= (max_adv_time * 60)) && (diff >= (a.sitewide_adv * 60))) {
                                $('button.form-submit,button#edit-preview').removeAttr('disabled');
                                $('input#edit-maintenance-type').val("I");
                                $('.downtimes-date-wrapper p.text-danger, .downtimes-date-wrapper p.text-warning').remove();
                                $('input#edit-startdate-planned').removeClass('text-danger');
                                $('input#edit-startdate-planned').addClass('text-warning');
                                $('input#edit-startdate-planned').parents('div.start-date-wrapper').append('<p class="text-warning">' + Drupal.t('Please note: The proposed schedule violates the service-specific SLA according to which maintenances have to be scheduled at least ') + (max_adv_time / 60) + Drupal.t(' hours in advance.') + '</p>');
                            } else {
                                $('button.form-submit,button#edit-preview').attr('disabled', 'true');
                                $('.downtimes-date-wrapper p.text-danger, .downtimes-date-wrapper p.text-warning').remove();
                                $('input#edit-startdate-planned').parents('div.start-date-wrapper').append('<p class="text-danger">' + Drupal.t('Please note: Maintenances have to be scheduled at least ') + (a.sitewide_adv) + Drupal.t(' minutes before the actual start. If you need to perform immediate maintenance work, please ') + "<a href='/incident-management/stoerungen/melden'>" + Drupal.t(' report an Incident instead') + "</a>" + Drupal.t('.') + '</p>');
                                $('input#edit-startdate-planned').addClass('text-danger');
                            }
                        }
                    }
                    });

                }
                // Else for if no checkboxes are checked.
                else {
                    $('button.form-submit,button#edit-preview').attr('disabled', 'true');
                    //$('button.form-submit,button#edit-preview').removeAttr('disabled');
                    $('.downtimes-date-wrapper p.text-danger, .downtimes-date-wrapper p.text-warning').remove();
                    //$('input#edit-startdate-planned').removeClass('text-danger');
                    //$('input#edit-startdate-planned').removeClass('warning');
                    $('input#edit-startdate-planned').parents('div.start-date-wrapper').append('<p class="text-danger">' + Drupal.t('Bitte wählen Sie zuerst betroffene Verfahren/Infrastruktur.') + '</p>');
                    $('input#edit-startdate-planned').addClass('text-danger');
                }
            });

            $('input#edit-enddate-planned').focus(function () {
                $(this).parent('div').find('p.text-danger').remove();
                $(this).removeClass('text-danger');
            });


            // Maintenance window validations.
            //$('input#edit-enddate-planned').focusout(function () {
            $("#edit-startdate-planned, #edit-enddate-planned").on("dp.change", function(e) {
                var end_day = $('input#edit-enddate-planned').val();
                if(!end_day) {
                    return;
                }
                if ($(this).val()) {
                    var end_date = $(this).val().split('-');
                    end_date = end_date[1];
                    var checkbox_count = $("#edit-services-effected input:checked").length;
                    if (checkbox_count > 0) {
                        var start_date_list = new Array();
                        var end_date_list = new Array();
                        var weekday = new Array(7);
                        weekday[0] = "Mon";
                        weekday[1] = "Tue";
                        weekday[2] = "Wed";
                        weekday[3] = "Thu";
                        weekday[4] = "Fri";
                        weekday[5] = "Sat";
                        weekday[6] = "Sun";

                        var start_day = $('input#edit-startdate-planned').val();
                        var start_day_obj = new Date(convert_to_valid_format(start_day));
                        start_day = weekday[start_day_obj.getDayOveridden()];

                        //var end_day = $(this).val();
                        var end_day_obj = new Date(convert_to_valid_format(end_day));
                        end_day = weekday[end_day_obj.getDayOveridden()];
                        if (start_day_obj.getTime() >= end_day_obj.getTime()) {
                            $(this).parents('div.end-date-wrapper').find('p.text-danger').remove();
                            $(this).parents('div.end-date-wrapper').append('<p class="text-danger">' + Drupal.t('Das Enddatum sollte nach dem Startdatum liegen.') + '</p>');
                            $(this).addClass('text-danger');
                        }else{
                            $(this).parents('div.end-date-wrapper').find('p.text-danger').remove();
                            $(this).removeClass('text-danger');
                        }
                        var maintenance_exists = check_type();
                        if (!maintenance_exists) {
                            $('.reason-for-noncompliance').show();
                            $('#edit-maintenance-result').val(1);
                        } else {
                            $('.reason-for-noncompliance').hide();
                            $('#edit-maintenance-result').val(0);
                            $('#edit-reason-for-noncompliance option').removeAttr('selected', 'selected');
                            $('#edit-reason-for-noncompliance option[value = "0"]').attr('selected', 'selected');
                        }
                        // return true;

                        /*$("#edit-services-effected input:checkbox:checked").each(function () {
                            var service_id = $(this).val();
                            if ((a.maintenance[service_id][start_day] instanceof Object) && (a.maintenance[service_id][end_day] instanceof Object)) {
                                start_date_list.push(get_time_in_seconds(a.maintenance[service_id][start_day].from_time));
                                end_date_list.push(get_time_in_seconds(a.maintenance[service_id][end_day].to_time));
                            } else {
                                start_date_list.push("NoVal");
                                end_date_list.push("NoVal");
                                // return true;
                            }
                        });*/

                        /*if ((jQuery.inArray("NoVal", start_date_list) == -1) || (jQuery.inArray("NoVal", end_date_list) == -1)) {
                            if ((start_date_list.length) && (end_date_list.length)) {
                                var max_start_date = Math.max.apply(Math, start_date_list);
                                var min_end_date = Math.min.apply(Math, end_date_list);
                            } else {
                                var max_start_date = '';
                                var min_end_date = '';
                            }
                        } else {
                            var max_start_date = '';
                            var min_end_date = '';
                        }*/

                        /*var start_date = $('input#edit-startdate-planned').val().split('-');
                        start_date = start_date[1];
                        if (get_maintenance_window(get_time_in_seconds(end_date), get_time_in_seconds(start_date), max_start_date, min_end_date, start_date_list, end_date_list)) {
                            $('.reason-for-noncompliance').show();
                            $('#edit-maintenance-result').val(1);
                        } else {
                            $('.reason-for-noncompliance').hide();
                            $('#edit-maintenance-result').val(0);
                            $('#edit-reason-for-noncompliance option').removeAttr('selected', 'selected');
                            $('#edit-reason-for-noncompliance option:[value = "0"]').attr('selected', 'selected');
                        }*/
                    }
                }
            });
            var reason = $("#edit-reason-for-noncompliance", context).val();
            if ($('#edit-maintenance-result', context).val() == 1) {
                $('.reason-for-noncompliance', context).show();
                $('#edit-maintenance-result', context).val(1);
            } else if (($('#edit-maintenance-result', context).val() == 1) || ($("#edit-reason-for-noncompliance", context).val() != 0) && $("#edit-reason-for-noncompliance", context).val() != null) {
                $('.reason-for-noncompliance', context).show();
                $('#edit-maintenance-result', context).val(1);
            } else {
                $('.reason-for-noncompliance', context).hide();
                $('#edit-maintenance-result', context).val(0);
                $('#edit-reason-for-noncompliance option', context).removeAttr('selected', 'selected');
                $('#edit-reason-for-noncompliance option:[value = "0"]', context).attr('selected', 'selected');
            }

            function get_maintenance_window(end_date, start_date, max_start_date, min_end_date, start_date_list, end_date_list) {
                var weekday = new Array(7);
                weekday[0] = "Mon";
                weekday[1] = "Tue";
                weekday[2] = "Wed";
                weekday[3] = "Thu";
                weekday[4] = "Fri";
                weekday[5] = "Sat";
                weekday[6] = "Sun";

                var start_day = $('input#edit-startdate-planned').val();
                start_day = new Date(convert_to_valid_format(start_day));
                start_day = weekday[start_day.getDayOveridden()];

                var end_day = $('input#edit-enddate-planned').val();
                end_day = new Date(convert_to_valid_format(end_day));
                end_day = weekday[end_day.getDayOveridden()];

                var start_day1 = $('input#edit-startdate-planned').val().split("-");
                start_day1[0] = $.trim(start_day1[0]);

                var end_day1 = $('input#edit-enddate-planned').val().split("-");
                end_day1[0] = $.trim(end_day1[0]);
                var sitewide_from = get_time_in_seconds(a.sitewide_maintain[start_day].from_time);
                var sitewide_to = get_time_in_seconds(a.sitewide_maintain[end_day].to_time);

                if ((((start_date >= max_start_date) && (start_date <= min_end_date)) && ((end_date >= max_start_date) && (end_date <= min_end_date))) || ((start_date >= sitewide_from) && (start_date <= sitewide_to)) && ((end_date >= sitewide_from) && (end_date <= sitewide_to)) && (start_day1[0] == end_day1[0])) {
                    return 0;
                } else if ((!sitewide_from && !sitewide_to) && ((start_date >= max_start_date) && (start_date <= min_end_date)) && ((end_date >= max_start_date) && (end_date <= min_end_date)) && (start_day1[0] == end_day1[0])) {
                    return 0;
                } else if ((!max_start_date && !min_end_date) && ((start_date >= sitewide_from) && (start_date <= sitewide_to)) && ((end_date >= sitewide_from) && (end_date <= sitewide_to)) && (start_day1[0] == end_day1[0])) {
                    return 0;
                } else if (((jQuery.inArray("NoVal", start_date_list) != -1) || (jQuery.inArray("NoVal", end_date_list) != -1)) && ((start_date >= sitewide_from) && (start_date <= sitewide_to)) && ((end_date >= sitewide_from) && (end_date <= sitewide_to)) && (start_day1[0] == end_day1[0])) {
                    return 0;
                } else if ((!sitewide_from && !sitewide_to) && (!max_start_date && !min_end_date)) {
                    return 1;
                } else if ((start_day1[0] != end_day1[0])) {
                    return 1;
                } else {
                    return 1;
                }

            }

            function get_time_in_seconds(time) {
                var time = time.split(':');
                var in_seconds = (time[0] * 60 * 60) + (time[1] * 60);
                return in_seconds;
            }
            function check_type() {
                var weekday = new Array(7);

                weekday[0] = "Mon";
                weekday[1] = "Tue";
                weekday[2] = "Wed";
                weekday[3] = "Thu";
                weekday[4] = "Fri";
                weekday[5] = "Sat";
                weekday[6] = "Sun";

                var start_date = $('input#edit-startdate-planned').val();
                start_day = new Date(convert_to_valid_format(start_date));
                var start_week_no = Math.ceil((start_day.getDate() - 1 - start_day.getDay()) / 7);
                start_day = start_day.getDayOveridden();

                var end_date = $('input#edit-enddate-planned').val();
                end_day = new Date(convert_to_valid_format(end_date));
                var end_week_no = Math.ceil((end_day.getDate() - 1 - end_day.getDay()) / 7);
                end_day = end_day.getDayOveridden();

                var passed = 0;
                var final_check = 0;

                if (check_with_sitewide_maintenance(start_date, end_date, weekday)) {
                    return 1;
                }
                $("#edit-services-effected input:checkbox:checked").each(function () {
                    passed = 0;
                    var service_id = $(this).val();
                    if (a.maintenance[service_id].length == 0) {
                        return false;
                    }
                    $.each(a.maintenance[service_id], function (key, value) {
                        var day_from_index = jQuery.inArray(key, weekday);
                        var day_until_index = jQuery.inArray(value.day_until, weekday);
                        var valid_week_days = new Array();
                        var check_day = 0;
                        if (day_from_index > day_until_index) {
                            for (var inc = day_from_index; inc <= 6; inc++) {
                                valid_week_days.push(inc);
                            }
                            for (inc = 0; inc <= day_until_index; inc++) {
                                valid_week_days.push(inc);
                            }
                            if (jQuery.inArray(start_day, valid_week_days) != -1 && jQuery.inArray(end_day, valid_week_days) != -1) {
                                check_day = 1;
                            }
                        } else if (start_day >= day_from_index && end_day <= day_until_index) {
                            check_day = 1;
                        }
                        if (check_day == 1) {
                            if (check_conditions(start_day, end_day, day_from_index, day_until_index, value.from_time, value.to_time)) {
                                passed = 1;
                                return false;
                            }
                        }
                    });
                    if (passed == 0) {
                        final_check = 0;
                        return false;
                    }else{
                        if(start_week_no == end_week_no) {
                            final_check = 1; // For same week maintenance Window validation will work, As assuming maintenance will not more than 4 weeks
                        }else {
                            final_check = 0; // For Diff week Mandatory to ask reason
                        }
                        return false;
                    }
                });
                return final_check;
            }
            function check_with_sitewide_maintenance(start_date, end_date, weekday) {
                var passed_flag = 0;
                var start_day = new Date(convert_to_valid_format(start_date));
                var start_week_no = Math.ceil((start_day.getDate() - 1 - start_day.getDay()) / 7);
                start_day = start_day.getDayOveridden();
                var end_day = new Date(convert_to_valid_format(end_date));
                var end_week_no = Math.ceil((end_day.getDate() - 1 - end_day.getDay()) / 7);
                end_day = end_day.getDayOveridden();
                /*console.log("--------");
                 console.log(start_date);
                 console.log(start_day);
                 console.log(end_date);
                 console.log(end_day);
                 console.log("----- END ---");
                 console.log(weekday);
                 console.log(a.sitewide_maintain);*/
                $.each(a.sitewide_maintain, function (key, value) {
                    var check_day = 0;
                    var day_from_index = jQuery.inArray(key, weekday);
                    var day_until_index = jQuery.inArray(value.day_until, weekday);
                    var valid_week_days = new Array();
                    /*console.log('from');
                     console.log(day_from_index);
                     console.log('until');
                     console.log(day_until_index);*/
                    if (day_from_index > day_until_index) {
                        for (var inc = day_from_index; inc <= 6; inc++) {
                            valid_week_days.push(inc);
                        }
                        for (inc = 0; inc <= day_until_index; inc++) {
                            valid_week_days.push(inc);
                        }
                        /*console.log(valid_week_days);
                         console.log('Startday array');
                         console.log(jQuery.inArray(start_day, valid_week_days));
                         console.log('Endday array');
                         console.log(jQuery.inArray(end_day, valid_week_days));*/
                        if (jQuery.inArray(start_day, valid_week_days) != -1 && jQuery.inArray(end_day, valid_week_days) != -1) {
                            check_day = 1;
                        }
                    } else if (start_day >= day_from_index && end_day <= day_until_index) {
                        //console.log(start_day + ">=" + day_from_index + "&&" + end_day + "<=" + day_until_index);
                        check_day = 1;
                    }
                    if (check_day == 1) {
                        if (check_conditions(start_day, end_day, day_from_index, day_until_index, value.from_time, value.to_time)) {
                            if(start_week_no == end_week_no) {
                                passed_flag = 1; // For same week maintenance Window validation will work, As assuming maintenance will not more than 4 weeks
                            }else {
                                passed_flag = 0; // For Diff week Mandatory to ask reason
                            }
                            return true;
                        }
                    }
                });
                //console.log(passed_flag);
                return passed_flag;
            }
            function check_conditions(start_day, end_day, day_from_index, day_until_index, service_from_time, service_to_time) {
                if (start_day == day_from_index) {
                    var start_time = $('input#edit-startdate-planned').val().split('-');
                    start_time = start_time[1];
                    start_time = get_time_in_seconds(start_time);
                    var service_start_time = get_time_in_seconds(service_from_time);
                    if (start_time >= service_start_time) {
                    } else {
                        return 0;
                    }
                }
                if (end_day == day_until_index) {
                    var end_time = $('input#edit-enddate-planned').val().split('-');
                    end_time = end_time[1];
                    end_time = get_time_in_seconds(end_time);
                    var service_end_time = get_time_in_seconds(service_to_time);
                    if (end_time <= service_end_time) {
                    } else {
                        return 0;
                    }
                }
                return 1;
            }
            /*
             * This functions returns the timestamp of given date.
             */

            function toTimestamp(strDate) {
                var datum = Date.parse(strDate);
                return datum / 1000;
            }



        }
    };
})(jQuery, Drupal);
//// overriding getday for german weekday format i.e. making mon as starting day
Date.prototype.getDayOveridden = function() {
    var weekday = new Array(7);
    weekday[0] = 6;
    weekday[1] = 0;
    weekday[2] = 1;
    weekday[3] = 2;
    weekday[4] = 3;
    weekday[5] = 4;
    weekday[6] = 5;
    return weekday[this.getDay()];
};
