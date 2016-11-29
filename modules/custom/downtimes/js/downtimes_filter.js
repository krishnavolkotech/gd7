(function ($, Drupal) {
    Drupal.behaviors.service_data = {
        attach: function (context, settings) {            
            $('.reset_all').click(function () {
                /*var form_id = $(this).attr('reset_form_name');
                 $("." + form_id).resetForm();*/
                /*jQuery('#edit-type').val('select');
                jQuery('#edit-states').val(1);
                jQuery('#edit-services-effected').val(1);
                jQuery('#edit-filter-startdate').val('');
                jQuery('#edit-filter-enddate').val('');
                jQuery('#edit-time-period').val(0);
                jQuery('#edit-string').val('');*/
                window.location.reload();
            });
    //    alert('test');
        var anchor = $("#archived_search_results_wrapper #pagination > nav > ul > li > a");
        anchor.each(function (index) {
        var links = $(this).attr('href');
                var new_href = links.replace('ajax_form=1&_wrapper_format=drupal_ajax', '');
                $(this).attr('href', new_href);
        });
      }
    };
})(jQuery, Drupal);