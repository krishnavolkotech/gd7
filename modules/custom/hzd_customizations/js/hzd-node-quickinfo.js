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
  });

})(jQuery);