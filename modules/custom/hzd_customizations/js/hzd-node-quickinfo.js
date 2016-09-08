/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

(function ($) {
  $(document).ready( function (){
    $('.node-quickinfo-publish').click( function (){
      var r = confirm("Are you sure you want to publish this quickinfo?\n\n\nClick Ok to Publish");
      if (r == true) {
        return true;
      } else {
        return false;
      }
    });
    var check_all = '';
    $(".node-form #edit-field-recipients--wrapper").each(function () {
      check_all = '<div class="js-form-item form-item input-field js-form-type-checkbox form-type-checkbox checkbox">';
      check_all += '<label class="control-label option" for="edit-field-check-all"><input type="checkbox" class="form-checkall" value="" name="field_check_all" id="edit-field-check-all">Alle auswählen</label></div>';
      $(this).find(".form-item").first().before(check_all);
      $(".node-form .form-checkboxes .form-checkall").change(function () {
        if ($(this).prop("checked") == true) {
          $(this).parent().parent().parent().find(".form-checkbox").each(function () {
            if ($(this).val() != 'select_or_other') {
              $(this).prop('checked', true);
            } else {
              $(this).prop('checked', false);
            }
          });
        }

        if ($(this).prop("checked") == false) {
           $(this).parent().parent().parent().find(".form-checkbox").each(function() {
               $(this).prop('checked', false);
           });
        }
      });
      $(".node-form .form-checkboxes .form-checkbox").change(function () {
        if ($(this).prop("checked") == false) {
          $(this).parent().parent().parent().find(".form-checkall").prop('checked', false);
        }

      });
    });
  });

})(jQuery);