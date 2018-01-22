(function ($, Drupal) {

  'use strict';

  /**
   * Adds summaries to the book outline form.
   *
   * @type {Drupal~behavior}
   *#quickinfo-sortable
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior to book outline forms.
   */
  Drupal.behaviors.release_management_sticky_header = {
    attach: function (context) {

      $('.js-deployed-date').datepicker({dateFormat: 'dd.mm.yy'});

      if (ie && ie === 7) {
        $('#edit-deployed-date-wrapper').css("width", "110px");
        $('#edit-deployed-date-wrapper').css("border", "none");
        $('#deployedreleases_posting input.form-submit')
          .css("margin-left", "0px");
        $('#deployedreleases_posting .reset_form').css("margin-left", "50px");
        $('#deployedreleases_posting .reset_form input.form-submit')
          .css("margin-left", "-40px");
      }

      /*$("#sortable").tablesorter({
       headers: {
       3: {sorter: 'archived_date'}
       },
       widgets: ['zebra']
       });*/

      $.tablesorter.addParser({
        // set a unique id
        id: 'deployed_date',
        is: function (s) {
          // return false so this parser is not auto detected
          return false;
        },
        format: function (s) {
          // format your data for normalization
          if (s) {
            var dateele = s.split('.');
            //adding 20 if date is formatted in only YY format.
//                        if (dateele[2].length == 2) {
//                            dateele[2] = '20' + dateele[2];
//                        }
            var date = dateele[2] + dateele[1] + dateele[0];
            return parseInt(date, 10);

          }
        },
        // set type, either numeric or text
        type: 'numeric'
      });
      $.fn.admin_toolbar = function () {
        if($('#toolbar-administration').length) {
            return 80;
        } else {
            return 0;
        }
      }
      $(context).find("#current_deploysortable").tablesorter({
        headers: {
          4: {sorter: 'deployed_date'},
          5: {sorter: false}
        },
        showProcessing: true,
        headerTemplate : '{content} {icon}',
        widgets: ['zebra',"pager", 'stickyHeaders'],
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

      $(context).find("#archived_deploysortable").tablesorter({
        headers: {
          4: {sorter: false},
          3: {sorter: 'deployed_date'}
        },
        showProcessing: true,
        headerTemplate : '{content} {icon}',
        widgets: ['zebra',"pager", 'stickyHeaders'],
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


      /*
       * archive deployed releases
       */

      $(context).find('.archive_deployedRelease').click(function () {
        var is_archive = confirm("Do you really want to archive?");
        if (is_archive == true) {
          var nid = $(this).attr('nid');
          $(this).next().prepend('Archivierung, bitte warten.');
          var url = drupalSettings.deploy_release.basePath + '/archive_deployedreleases';

          $.post(url, {'nid': nid}, function (data) {
            $('.loader').html(' ');
            window.location.reload();
            return false;
          });
        }
        return false;
      });


      $(context)
        .find('.public_deployed_releses_output .pager li a')
        .click(function () {
          var ele = $(this);
          var url = ele.attr('href');

          var params = url.split('?')[1];
          var type = drupalSettings.deploy_release.type;
          var base_path = drupalSettings.deploy_release.basePath;
          url = base_path + 'releases_search_results/' + type + '?' + params;
          // url = window.location +'?'+params;
          $.post(url, {}, function (data) {
            if (data.status == true) {
              $('#released_results_wrapper').html(data.data);
              $(window).scrollTop(0);
              Drupal.attachBehaviors('#released_results_wrapper');
            }
          }, 'json');
          return false;
        });

      var ie = (function () {

        var undef,
          v = 3,
          div = document.createElement('div'),
          all = div.getElementsByTagName('i');

        while (
          div.innerHTML = '<!--[if gt IE ' + (++v) + ']><i></i><![endif]-->',
            all[0]
          ) {
          ;
        }

        return v > 4 ? v : undef;

      }());


      $(context)
        .find('.deployed_releses_output .pager li a')
        .click(function () {
          var ele = $(this);
          var url = ele.attr('href');

          var params = url.split('?')[1];
          var group_id = drupalSettings.deploy_release.group_id;
          var type = drupalSettings.deploy_release.type;
          var base_path = drupalSettings.deploy_release.basePath;

          //url = '/' + path + '/releases_search_results?'+params;
          url = base_path + 'node/' + group_id + '/releases_search_results/' + type + '?' + params;
          //url = window.location +'?'+params;
          $.post(url, {}, function (data) {
            if (data.status == true) {
              $('#released_results_wrapper').html(data.data);
              $(window).scrollTop(0);
              Drupal.attachBehaviors('#released_results_wrapper');
            }
          }, 'json');
          return false;
        });

    }
  };

})(jQuery, Drupal);

function reset_form_elements() {
  var type = drupalSettings.deploy_release.type;
  jQuery('#edit-deployed-services').val(0);
  jQuery('#edit-deployed-releases').val(0);
  jQuery('#edit-deployed-date').val('');
  jQuery('#edit-deployed-environment').val(0);
  jQuery('#edit-deployed-type').val('current')

  jQuery('.state_search_dropdown select').val(0);
  jQuery('.service_search_dropdown select').val(0);
  jQuery('.releases_search_dropdown select').val(0);
  jQuery('.filter_start_date input').val('');
  jQuery('.filter_end_date input').val('');
  jQuery('.limit_search_dropdown select').val(0);

  if (type !== undefined) {
    window.location = window.location;
  }
}

