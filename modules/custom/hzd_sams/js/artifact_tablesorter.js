(function ($, Drupal) {

  'use strict';

  /**
   * Adds a tablesorter to the artifact tables.
   *
   * @type {Drupal~behavior}
   *   #sortable_artifact_table
   */
  Drupal.behaviors.sams_table_sorter = {
    attach: function (context) {

      $.fn.admin_toolbar = function () {
        if($('#toolbar-administration').length) {
            return 80;
        } else {
            return 0;
        }
      }

      // custom date parser for tablesorter, based on release_management.js
      $.tablesorter.addParser({
        // set a unique id
        id: 'artifact-date',
        is: function(s) {
            // return false so this parser is not auto detected
            return false;
        },
        format: function(s) {
          // format your data for normalization 
          if (s) {
            var mydatetime = s.split(' ');
            if (mydatetime[0] && mydatetime[1]) {
              var dateele = mydatetime[0].split('.');
              var timeele = mydatetime[1].split(':');
       
              //adding 20 if date is formatted in only YY format.
              if (dateele[2].length == 2) {
                dateele[2] = '20' + dateele[2];
              }
              var date = dateele[2] + dateele[1] + dateele[0] + timeele[0] + timeele[1] + timeele[2];
              return parseInt(date,10);
            }
          }
        },
        // set type, either numeric or text
        type: 'numeric'
      });

      $('#sortable_artifact_table').tablesorter({
        showProcessing: true,
        headerTemplate : '{content} {icon}',
        widgets: ['zebra','stickyHeaders'],
        widgetOptions: {
          stickyHeaders: 'sticky-header',
          stickyHeaders_offset: $.fn.admin_toolbar(),
          stickyHeaders_cloneId: '-sticky',
          stickyHeaders_addResizeEvent: true,
          stickyHeaders_includeCaption: true,
          stickyHeaders_zIndex: 2,
          stickyHeaders_attachTo: null,
          stickyHeaders_xScroll: null,
          stickyHeaders_yScroll: null,
          stickyHeaders_filteredToTop: true
        }
      });
      
    }
  };
  
})(jQuery, Drupal);
