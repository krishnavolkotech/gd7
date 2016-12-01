(function ($) {
    Drupal.behaviors.hzd = {
        attach: function (context, settings) {
//  $(document).ready(function(){
// $.fn.datepicker.defaults.language = 'de';
// $.fn.datepicker.defaults.regional = 'INDIA';
// });
            if (jQuery('.start_date').length > 0) {
                jQuery('.start_date').datepicker({dateFormat: 'dd.mm.yy'});
            }

            if (jQuery('.end_date').length > 0) {
                jQuery('.end_date').datepicker({dateFormat: 'dd.mm.yy'});
            }

            if (jQuery('.filter_startdate').length > 0) {
                jQuery('.filter_startdate').datepicker({dateFormat: 'dd.mm.yy'});
            }

            if (jQuery('.filter_enddate').length > 0) {
                jQuery('.filter_enddate').datepicker({dateFormat: 'dd.mm.yy'});
            }
        }
    }
    jQuery('.dummy_selects input[type="radio"]').click(function () {
        var checked_value = jQuery(this).attr('value');
        jQuery(".subscription_vals .form-radio[value=" + checked_value + "]").prop("checked", true);
    });
    $('document').ready(function () {

        $("#block-maintenance .downtime-hover").css('display', 'none');
        // Control hover on front page downtimes blocks
        $("#block-incidentblock .state-item").hover(handlerInIncident, handlerOutIncident);
        $("#block-maintenance .state-item").hover(handlerInMaintenance, handlerOutMaintenance);
        // Handlers for front page tool tips.
        function handlerInMaintenance() {
            $(this).parent().next('.downtime-hover').css('display', 'block');
        }

        function handlerOutMaintenance() {
            $(this).parent().next('.downtime-hover').css('display', 'none');
        }

        function handlerInIncident() {
            $(this).parent().next('.downtime-hover').css('display', 'block');
        }

        function handlerOutIncident() {
            $(this).parent().next('.downtime-hover').css('display', 'none');
        }

        //Clears from jquery indternal data cache
        $('.block-cust-group-menu-block .dropdown-toggle').removeData('toggle');
        // Removes the data-toggle atribute entirely from the parent anchor link.
        $('.block-cust-group-menu-block .dropdown-toggle').removeAttr('data-toggle');
        // Date time picker on service creation fields found at : /group/24/downtimes/create_downtimes
        $('#edit-startdate-planned, #edit-date-reported, #edit-enddate-planned')
                .prop('readonly', 'readonly')
                .css('background-color', '#fff')
                .datetimepicker({
                    format: 'DD.MM.YYYY - HH:mm',
                    useCurrent: false,
                    showTodayButton: true,
                    ignoreReadonly: true,
//                    sideBySide: true,
//                    debug: true
                })
                .on("dp.change", function (e) {
                    $(".day").on('click', function () {
                        $("a[data-action='togglePicker']").trigger('click');
                    });
                })
                .on("dp.show", function (e) {
                    $(".day").on('click', function () {
                        $("a[data-action='togglePicker']").trigger('click');
                    });
                })
                .parent().css('position', 'relative');
    });
})(jQuery);
