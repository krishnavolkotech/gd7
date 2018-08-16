(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.cust_group = {
    attach: function (context, settings) {
	$('.edit-ticket-id').click(function() {
	    $('.show-ticket-value', $(this).parents('tr')).hide();
	    $('.ticket-update-form', $(this).parents('tr')).removeClass('hide');
	    return false;
	});
    }
  }
})(jQuery, Drupal, drupalSettings);



