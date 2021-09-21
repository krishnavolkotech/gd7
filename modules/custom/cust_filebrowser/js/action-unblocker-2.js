(function ($, Drupal) {
  Drupal.behaviors.actionUnblocker = {
    attach: function (context, settings) {
      $('div.cust-filebrowser-actions > .button', context).once('myActionUnblocker').each(function () {
        $(this).removeAttr('disabled');
        $(this).removeClass('disabled');
      });
    }
  };

  Drupal.behaviors.filebrowserFilter = {
    attach: function (context, settings) {
      // Moved this to module hook.
      // $("#form-action-actions-wrapper").once('cust_filebrowser').after('<input class="form-control" id="myInput" type="text" placeholder="Suche...">');
      $("#search-files").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        $("#edit-table tbody tr").filter(function () {
          if ($(this).text().toLowerCase().indexOf('zurück') > -1) {
            return;
          }
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
      });
    }
  };

  Drupal.behaviors.filebrowserTablesorter = {
    attach: function (context, settings) {
      // Your custom JavaScript goes inside this function ...

      // Without this, headers are not sortable for regular users. Why?
      $.fn.admin_toolbar = function () {
        if ($('#toolbar-administration').length) {
          return 80;
        } else {
          return 0;
        }
      }

      // custom date parser for tablesorter, based on release_management.js
      $.tablesorter.addParser({
        // set a unique id
        id: 'date-sorter',
        is: function (s) {
          // return false so this parser is not auto detected
          return false;
        },
        format: function (s, table) {
          // format your data for normalization 
          if (s) {
            var mydatetime = s.split(' - ');
            if (mydatetime[0] && mydatetime[1]) {
              var dateele = mydatetime[0].split('.');
              var timeele = mydatetime[1].split(':');
              
              //adding 20 if date is formatted in only YY format.
              if (dateele[2].length == 2) {
                dateele[2] = '20' + dateele[2];
              }
              // new Date(year, monthIndex [, day [, hour [, minutes [, seconds [, milliseconds]]]]]);
              let unixtime = new Date(
                parseInt(dateele[2]), // Year
                parseInt(dateele[1]), // Month
                parseInt(dateele[0]), // Day
                parseInt(timeele[0]), // Hour
                parseInt(timeele[1])  // Minutes
              )
              .getTime() / 1000;
              return unixtime;
            }
          }
        },
        // set type, either numeric or text
        type: 'numeric'
      });

      // Remove standard tablesorter icon.
      $(".glyphicon-chevron-up").remove();

      // @todo Add ignore row zu 'go up" row.
      // Not supported by current version of tablesorter?
      let row = $(".folder-parent-icon").closest("tr");
      // $(".folder-parent-icon").prependTo(".table-responsive");
      // $("td a:contains('Zurück')").prependTo(".table-responsive");
      row.remove();

      $('#edit-table').tablesorter({
        headers: {
          0: { sorter: false },
          3: { sorter: 'date-sorter' },
        },
        showProcessing: true,
        headerTemplate: '{content} {icon}',
        widgets: ['zebra', 'stickyHeaders'],
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
