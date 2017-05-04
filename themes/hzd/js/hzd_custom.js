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

        // $("#block-maintenance .downtime-hover").css('display', 'none');
        // Control hover on front page downtimes blocks
        // $("ul.incidents-home-block>li>a").hover(handlerInIncident, handlerOutIncident);
        // $("#block-maintenance .state-item").hover(handlerInMaintenance, handlerOutMaintenance);
        // Handlers for front page tool tips.
        /*function handlerInMaintenance() {
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
         }*/

        $(".frontpage-downtime-block ul.incidents-home-block>li .service-tooltip").hover(function () {
            var ele = $(this);
            var offset = ele.offset();
            var popHeight = ele.parents('li').find('article.popup').height();
            var finalTop = offset.top - $(window).scrollTop() - popHeight / 2;
            var popWidth = ele.parents('li').find('article.popup').width();
            var finalLeft = offset.left - popWidth - 15;
            if (finalTop < 78 && $('#toolbar-administration').length) {
                finalTop = 88;
            } else if (finalTop < 0 && !$('#toolbar-administration').length) {
                finalTop = 10;
            }
            if (finalLeft < 0) {
                var newWidth = popWidth * 3 / 4;
                ele.parents('li').find('article.popup').width(newWidth);
                finalLeft = offset.left - newWidth - 15;
            }
            ele.parents('li').find('article.popup')
                .css('position', 'fixed')
                .css('top', finalTop)
                .css('left', finalLeft)
                .show();
        }, function () {
            var ele = $(this).parent('li');
            ele.find('article.popup')
                .removeAttr('position')
                .removeAttr('top')
                .hide();
        });

        $(".frontpage-downtime-block .maintenance-list ul li .service-tooltip").hover(function () {
            var ele = $(this);
            var offset = ele.offset();
            var popHeight = ele.parents('li').find('article.popup').height();
            var finalTop = offset.top - $(window).scrollTop() - popHeight / 2;
            var popWidth = ele.parents('li').find('article.popup').width();
            var finalLeft = offset.left - popWidth - 15;
            if (finalTop < 78 && $('#toolbar-administration').length) {
                finalTop = 88;
            } else if (finalTop < 0 && !$('#toolbar-administration').length) {
                finalTop = 10;
            }
            if (finalLeft < 0) {
                var newWidth = popWidth * 3 / 4;
                ele.parents('li').find('article.popup').width(newWidth);
                finalLeft = offset.left - newWidth - 15;
            }
            ele.parents('li').find('article.popup')
                .css('position', 'fixed')
                .css('top', finalTop)
                .css('left', finalLeft)
                .show();
        }, function () {
            var ele = $(this).parent('li');
            ele.find('article.popup')
                .removeAttr('position')
                .removeAttr('top')
                .hide();
        });

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
        $('div.popup-wrapper').hover(function () {
            var offset = $(this).offset();
            var popHeight = $(this).find('article.popup').height();
            var finalTop = offset.top - $(window).scrollTop() - popHeight / 2;
            if (finalTop < 78 && $('#toolbar-administration').length) {
                finalTop = 88;
            } else if (finalTop < 0 && !$('#toolbar-administration').length) {
                finalTop = 10;
            }
            var popWidth = $(this).find('article.popup').width();
            var finalLeft = offset.left - popWidth - 10;
            $(this).find('article.popup')
                .css('position', 'fixed')
                .css('top', finalTop)
                .css('left', finalLeft)
                .show();
        }, function () {
            $(this).find('article.popup')
                .removeAttr('position')
                .removeAttr('top')
                .hide();
        });

        /*jQuery('div.popup-wrapper')
         .mouseover(function () {
         $('article.popup').hide();
         $(this).find('article.popup').show();
         });

         $('article.popup').mouseout(function () {
         $(this).hide();
         });
         $(window).click(function () {
         $('article.popup').hide();
         });*/

        var options = {};
        // $('div.popup-wrapper').popover(options);
        // $('div.popup-wrapper').each(function() {
        //     var $this = $(this);
        //     $this.popover({
        //         content: $(this).find('article.popup').html(),
        //         trigger: 'hover',
        //         placement: 'left',
        //         html: true,
        //     });
        // });

        $('.frontpage-downtime-block .maintenance-home-info').find('div.maintenance-list').css('width', '47%').css('float', 'none');
        $('.frontpage-downtime-block .maintenance-home-info').isotope({
            layoutMode: 'masonry',
            itemSelector: '.frontpage-downtime-block .maintenance-home-info div.maintenance-list'
        });

    });

    $('#user-register-form .form-email,[type="password"]').bind("cut copy paste",function(e) {
        e.preventDefault();
    });
})
(jQuery);


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
