(function ($, Drupal) {
$(document).ready(function(){
    'use strict';
    //Table Responsive
    var all_tables = jQuery('.table-responsive');
    all_tables.each(function(e){
        var headertext = [],
        headers = this.querySelectorAll("table th"),
        tablerows = this.querySelectorAll("table th"),
        tablebody = this.querySelector("table tbody");

        for(var i = 0; i < headers.length; i++) {
          var current = headers[i];
          headertext.push(current.textContent.replace(/\r?\n|\r/,""));
        } 
        if(tablebody != null) {
            for (var i = 0, row; row = tablebody.rows[i]; i++) {
              for (var j = 0, col; col = row.cells[j]; j++) {
                col.setAttribute("data-th", headertext[j]);
              } 
            }
        }
    });
});
    //End Table Responsive
})(window.jQuery, window.Drupal, window.drupalSettings);


