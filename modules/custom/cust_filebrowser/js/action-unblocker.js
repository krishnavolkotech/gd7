(function ($, Drupal) {
  Drupal.behaviors.actionUnblocker = {
    attach: function (context, settings) {
      $('div.cust-filebrowser-actions > .button', context).once('myActionUnblocker').each(function () {
        $(this).removeAttr('disabled');
        $(this).removeClass('disabled');
      });
    }
  };
})(jQuery, Drupal);
