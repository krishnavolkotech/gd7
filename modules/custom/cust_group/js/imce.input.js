(function ($, Drupal) {
  "use strict";

  /**
   * @file
   * Provides methods for integrating Imce into text fields.
   */

  /**
   * Drupal behavior to handle url input integration.
   */
  Drupal.behaviors.imceUrlInput = {
    attach: function (context, settings) {
      $('.imce-url-input', context).not('.imce-url-processed').addClass('imce-url-processed').each(imceInput.processUrlInput);
      if (window.drupalSettings.group_context === undefined) {
          $("a.imce-url-button").remove();
      }
    }
  };
  
  /**
   * Global container for integration helpers.
   */
  var imceInput = window.imceInput = window.imceInput || {

    /**
     * Processes an url input.
     */
    processUrlInput: function(i, el) {
      var button = imceInput.createUrlButton(el.id, el.getAttribute('data-imce-type'));
      el.parentNode.insertBefore(button, el);
    },

    /**
     * Creates an url input button.
     */
    createUrlButton: function(inputId, inputType) {
      var button = document.createElement('a');
      button.href = '#';
      button.className = 'imce-url-button';
      button.innerHTML = '<span>' + Drupal.t('Open File Browser') + '</span>';
      button.onclick = imceInput.urlButtonClick;
      button.InputId = inputId || 'imce-url-input-' + (Math.random() + '').substr(2);
      button.InputType = inputType || 'link';
      return button;
    },

    /**
     * Click event of an url button.
     */
    urlButtonClick: function(e) {
      var groupContext = window.drupalSettings.group_context;
      var url = Drupal.url('files/'+groupContext);
      url += (url.indexOf('?') === -1 ? '?' : '&') + 'sendto=imceInput.urlSendto&inputId=' + this.InputId + '&type=' + this.InputType;
      // Focus on input before opening the window
      $('#' + this.InputId).focus();
      window.open(url, '', 'width=' + Math.min(1000, parseInt(screen.availWidth * 0.8, 10)) + ',height=' + Math.min(800, parseInt(screen.availHeight * 0.8, 10)) + ',resizable=1');
      return false;
    },

    /**
     * Sendto handler for an url input.
     */
    urlSendto: function(File, win) {
      var url = File.getUrl();
      var el = $('#' + win.imce.getQuery('inputId'))[0];
      win.close();
      if (el) {
        $(el).val(url).change().focus();
        // Auto-update image alt input with image name.
        var name = el.name;
        var re = /(^|[^a-z])src([^a-z]|$)/i;
        if (name && re.test(name)) {
          var altEl = el.form && el.form.elements[name.replace(re, '$1alt$2')];
          if (altEl && !altEl.value) {
            altEl.value = el.value.split('/').pop();
          }
        }
      }
    }
  
  };

})(jQuery, Drupal);
