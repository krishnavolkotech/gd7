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

            jQuery('#block-primarylinks > ul > li.dropdown').each(function () {
                jQuery(this).mouseenter(function () {
                    jQuery('#block-primarylinks li.dropdown').removeClass('open');
                    jQuery(this).addClass('open');

                });
                jQuery(this).mouseleave(function () {
                    jQuery('#block-primarylinks li.dropdown').removeClass('open');
                });

            });
        }
    }
    jQuery('.dummy_selects input[type="radio"]').click(function () {
        var checked_value = jQuery(this).attr('value');
        jQuery(".subscription_vals .form-radio[value=" + checked_value + "]").prop("checked", true);
    });
    $('document').ready(function () {

        $("#block-maintenance .downtime-hover").css('display', 'none');
        // Control hover on front page downtimes blocks
        $("ul.incidents-home-block>li>a").hover(handlerInIncident, handlerOutIncident);
        $("#block-maintenance .state-item").hover(handlerInMaintenance, handlerOutMaintenance);
        // Handlers for front page tool tips.
        function handlerInMaintenance() {
            $(this).parent().next('.downtime-hover').css('display', 'block');
        }

        function handlerOutMaintenance() {
            $(this).parent().next('.downtime-hover').css('display', 'none');
        }

        function handlerInIncident() {
            $(this).next('.downtime-hover').css('display', 'block');
        }

        function handlerOutIncident() {
            $(this).next('.downtime-hover').css('display', 'none');
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
                useCurrent: true,
                showTodayButton: true,
                ignoreReadonly: true,
                sideBySide: true,
                stepping: 5,
		toolbarPlacement: 'bottom',
		showClear: true,
        // debug: true
            })
            .parent().css('position', 'relative');
        // $('popup-wrapper').hover(function () {
        //     $(this).parent().find('article.popup').show();
        // }, function () {
        //     $(this).parent().find('article.popup').hide();
        // });

        jQuery('div.popup-wrapper')
            .mouseover(function () {
                $('article.popup').hide();
                $(this).find('article.popup').show();
            });

        $('article.popup').mouseout(function () {
            $(this).hide();
        });
        $(window).click(function () {
            $('article.popup').hide();
        });

        $('.frontpage-downtime-block .maintenance-home-info').find('div.maintenance-list').css('width', '47%').css('float', 'none');
        $('.frontpage-downtime-block .maintenance-home-info').isotope({
            layoutMode: 'masonry',
            itemSelector: '.frontpage-downtime-block .maintenance-home-info div.maintenance-list'
        });

    });


})(jQuery);


function reset_form_elements() {
//  alert('hi');
    url = window.location.href;
    res = url.split('?');
//  window.location.assign(res['0']);
// alert(res['0']);
//  window.history.pushState( {}, null, res['0']);
    window.location = res['0'];
//  window.history.pushState( {}, null, res['0']);
    return false;
}
