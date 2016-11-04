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
  jQuery('.dummy_selects input[type="radio"]').click(function(){
    var checked_value = jQuery(this).attr('value');
    jQuery(".subscription_vals .form-radio[value="+checked_value+"]").prop("checked", true);
  });

})(jQuery);