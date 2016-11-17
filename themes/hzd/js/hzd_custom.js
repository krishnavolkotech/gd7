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


    });


})(jQuery);