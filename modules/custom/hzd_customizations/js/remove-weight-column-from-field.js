(function ($) {
  Drupal.behaviors.NoTabledragWeights = {
    attach: function (context, settings) {
      // Again, get more specific if desired.
      $('.field--type-file td:nth-child(3),.field--type-file th:nth-child(3)').hide();
    }
  };
}(jQuery));