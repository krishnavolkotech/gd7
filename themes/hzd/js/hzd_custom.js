(function ($) {
Drupal.behaviors.hzd = {
    attach: function (context, settings) {

	function tog(v){return v?'addClass':'removeClass';} 
	$(document).on('input', '#edit-fulltext', function() {
	    $(this)[tog(this.value)]('hascontent');
	}).on('mousemove', '.hascontent', function( e ){
	    $(this)[tog(this.offsetWidth-18 < e.clientX-this.getBoundingClientRect().left)]('onremove');
	}).on('touchstart click', '.onremove', function( ev ){
	    ev.preventDefault();
	    $(this).removeClass('hascontent onremove').val('').change();
	});

	
	if($('.view-empty').length >= 1) {
	    $('.view-solr-search .pager-nav').addClass('hidden');
	}
	else {
	    $('.view-solr-search .pager-nav').removeClass('hidden');
	}

	$(document).click(function(e) {
	    $('.search-limited-content').addClass('hidden');
	    $('.search-time-filters-content').addClass('hidden');
	});

	$('.search-limited-content').once('.all-groups-filter').click(function(e){
	    e.stopPropagation();
	});
	$('.search-time-filters-content').once('.search-time-filters').click(function(e){
	    e.stopPropagation();
	});
	
	$('.all-groups-filter').once('.all-groups-filter').click(function(e){
	    e.stopPropagation();
	    $('.search-limited-content').toggleClass('hidden');
	    $('.search-time-filters-content').addClass('hidden');
	});

	$('.search-time-filters').once('.search-time-filters').click(function(e){
	    e.stopPropagation();
	    $('.search-limited-content').addClass('hidden');
	    $('.search-time-filters-content').toggleClass('hidden');
	});

        //Trigger checkbox click on clicking label as well.
        jQuery('.custom-search-facet li label').click(function(){console.log(jQuery(this).parent().children('input').click());});
	$('.custom-facets-checkbox').change(function() {
          var search_txt = $('#edit-fulltext').val();
          //if($(this).is(":checked")) {
          //}
          var href = $(this).next().next().attr('href');
          var base_url = window.location.protocol + '//' + window.location.host;
          var url = new URL(base_url + '/' + href);
          var search_params = url.searchParams;
          //Updating the search string if user has types a new keyword in the search field.
          search_params.set('fulltext', search_txt);
          var query = search_params.toString();
          var target_url = window.location.pathname + "?" + query;
          //window.location.href = href;
          window.location.href = target_url;
          jQuery('.custom-facets-checkbox').attr("disabled", true);
        });

	
	
    $.fn.admin_toolbar = function () {
      if($('#toolbar-administration').length) {
          return 80;
      } else {
          return 0;
      }
    }
    $.tablesorter.addParser({
      // set a unique id
      id: 'date_sorting',
      is: function(s) {
        // return false so this parser is not auto detected
        return false;
      },
      format: function(s) {
        // format your data for normalization 
        if (s) {
          var date_info = s.split(' ');
                var dateele = date_info[0].split('.');
                //adding 20 if date is formatted in only YY format.
                if (dateele[2].length == 2) {
                  dateele[2] = '20' + dateele[2];
                }
                var date = dateele[2] + dateele[1] + dateele[0];
                //console.log(date);
                return parseInt(date,10);	     
              }
      },
      // set type, either numeric or text
      type: 'numeric'
    });
    $("#problem_search_results_wrapper table").tablesorter({
      textExtraction: {
          2: function (node, table, cellIndex) {
              // only keep the first 5 letters of the alphanumeric value
              return $(node).text().substring(0, 5);
          }
      },
      headers: {
        6: {sorter: 'date_sorting'}
      },
        showProcessing: true,
        headerTemplate : '{content} {icon}',
        widgets: ['zebra','stickyHeaders'],
        widgetOptions: {
            stickyHeaders: 'sticky-header',
            stickyHeaders_offset: $.fn.admin_toolbar(),
            stickyHeaders_cloneId: '-sticky',
            stickyHeaders_addResizeEvent: true,
            stickyHeaders_includeCaption: false,
            stickyHeaders_zIndex: 2,
            stickyHeaders_attachTo: null,
            stickyHeaders_xScroll: null,
            stickyHeaders_yScroll: null,
            stickyHeaders_filteredToTop: true
        }
    });
        
    
    $('div.saved-rz-schnellinfo table').tablesorter({
      headers: {
        4: {sorter: 'quickinfo_date_sorting'}
      },
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
    
    $('div.view-group-members-lists table').tablesorter({
        headers: {
            2: { sorter: false },
            3: { sorter: false },
            4: { sorter: false },
            6: { sorter: false }
        },
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
    
    $('div.view-hzd-group-members table').tablesorter({
        headers: {
            2: { sorter: false },
            3: { sorter: false },
            5: { sorter: false },
            6: { sorter: false }
        },
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
    
    $('#im-attachment-files-list table').tablesorter({
        headers: {
            1: { sorter: false },
            2: { sorter: false },
            5: { sorter: false },
            6: { sorter: false },
            7: { sorter: false },
            4: { sorter: 'date_sorting' },
        },
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
    
// group node tables sortable
jQuery("div.group-nodes-sortable > div.view-content > div > table.table").tablesorter({
        headers: {
	    1: { 
                sorter: false 
            }, 
            2: { 
                sorter: false 
            },            3: { 
                sorter: false 
            },            4: { 
                sorter: false 
            },
	      5: {
	        sorter: false
     	   },
              6: {
                sorter:false
           },
        },
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
// mitglieder page sorting functionality
jQuery("div.sort-user-list > div.view-content > div > table.table").tablesorter({
        headers: { 
            2: { 
                sorter: false 
            }, 3: { 
                sorter: false 
            }, 
           5: { 
                sorter: false 
            },
        },
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
// all group page sorting functionality
jQuery("div.all-groups-sortable > div.view-content > div > table.table").tablesorter({
        headers: {
	    1: { 
                sorter: false 
            }, 
            2: { 
                sorter: false 
            }, 3: { 
                sorter: false 
            }, 
           4: { 
                sorter: false 
            },
        },
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

 jQuery.tablesorter.addParser({
          // set a unique id
          id: 'quickinfo_date_sorting',
          is: function(s) {
            // return false so this parser is not auto detected
            return false;
          },
           format: function(s,table) { 
                      s = s.replace(/\-/g,"/"); 
                      s = s.replace(/(\d{1,2})[\/\.](\d{1,2})[\/\.](\d{4})/, "$3/$2/$1");                            
                      return jQuery.tablesorter.formatFloat(new Date(s).getTime()); 
                  },
          // set type, either numeric or text
          type: 'numeric'
        });


jQuery("div.rz-schnellinfos-sortable > div.view-content > div > table.table").tablesorter({
        headers: {
          5: { sorter: 'quickinfo_date_sorting'}
        },
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
jQuery("div.measures-list > div.view-content > div > table.table").tablesorter({
        headers: {
          4: { sorter: 'quickinfo_date_sorting'},
          5: { sorter: 'quickinfo_date_sorting'},
	  9: { sorter: false}
        },
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
jQuery("div.risk-list > div.view-content > div > table.table").tablesorter({
        headers: {
          6: { sorter: 'quickinfo_date_sorting'},
          9: { sorter: false}
        },
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
jQuery("div.riskcluster-list > div.view-content > div > table.table").tablesorter({
	headers: {
          2: { sorter: false},
          3: { sorter: false}
        },
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

//  $(document).ready(function(){
// $.fn.datepicker.d
// $.fn.datepicker.defaults.regional = 'INDIA';
// });

      if (jQuery('.start_date').length > 0) {
        jQuery('.start_date').datepicker({dateFormat: 'dd.mm.yy'});
      }

      if (jQuery('.end_date').length > 0) {
        jQuery('.end_date').datepicker({dateFormat: 'dd.mm.yy'});
      }

      if (jQuery('.filter_startdate').length > 0) {
        jQuery('.filter_startdate').datepicker({dateFormat: 'dd.mm.yy'});
      }

      if (jQuery('.filter_enddate').length > 0) {
        jQuery('.filter_enddate').datepicker({dateFormat: 'dd.mm.yy'});
      }

      jQuery('#block-primarylinks > ul > li.dropdown').each(function () {
        jQuery(this).mouseenter(function () {
          jQuery('#block-primarylinks li.dropdown').removeClass('open');
          jQuery(this).addClass('open');

        });
        jQuery(this).mouseleave(function () {
          jQuery('#block-primarylinks li.dropdown').removeClass('open');
        });

      });

  jQuery('.dummy_selects input[type="radio"]').click(function () {
    var checked_value = jQuery(this).attr('value');
    jQuery(".subscription_vals .form-radio[value=" + checked_value + "]")
      .prop("checked", true);
  });
  $('document').ready(function () {

    // This fix is for getting handles for images and tables for node edit.
    // The handles around doesn't appear on webkit browsers(chrome).
    // Uses code from: http://www.editorboost.net/Webkitresize/Demos
    // Remove once https://www.drupal.org/node/2909023 lands.
    if(window.CKEDITOR && typeof window.CKEDITOR != 'undefined') {
      CKEDITOR.on('instanceReady', function (evt) {
        /*simple initialization*/
        $("iframe.cke_wysiwyg_frame")
          .webkitimageresize()
          .webkittableresize()
          .webkittdresize();
      });
    }
    // $("#block-maintenance .downtime-hover").css('display', 'none');
    // Control hover on front page downtimes blocks
    // $("ul.incidents-home-block>li>a").hover(handlerInIncident, handlerOutIncident);
    // $("#block-maintenance .state-item").hover(handlerInMaintenance, handlerOutMaintenance);
    // Handlers for front page tool tips.
    /*function handlerInMaintenance() {
     $(this).parent().next('.downtime-hover').css('display', 'block');
     }

     function handlerOutMaintenance() {
     $(this).parent().next('.downtime-hover').css('display', 'none');
     }

     function handlerInIncident() {
     $(this).next('.downtime-hover').css('display', 'block');
     }

     function handlerOutIncident() {
     $(this).next('.downtime-hover').css('display', 'none');
     }*/
     $('section#block-incidentblock div ul.incidents-home-block li div.service-tooltip', context).popover({
          trigger: 'hover',
          container: 'body',
          placement: 'left',
          html: true,
          content: function () {
              var current_wrapper = $(this).parent().find('.downtime-popover-wrapper');
              var nodeid = current_wrapper.attr('id').replace('incident-', '');
              if (current_wrapper.html() == '') {
                  if (typeof nodeid !== "undefined") {
                      var endpoint = Drupal.url('ajaxnode/archive/' + nodeid);
                      var current_element = $(current_wrapper, context);
                      $.ajax({
                          async: false,
                          type: 'POST',
                          url: endpoint,
                          dataType: 'json',
                          success: function (data) {
                              var node_data = data[0].data;
                              current_element.html(node_data);
                          },
                          error: function (jqXHR, exception) {
                              return false;
                          }
                      });
                  }

              }
              return current_wrapper.html();

          }
      });



      $('.deployed-info-icon').click(function() {
	  var ele = $(this);
	  // Close previous popovers
	  title = "Einsatzmeldung";
	  $('.popover').popover('destroy');
	  $(this).popover({
	      placement: 'left',
	      html: true,
	      template: '<div class="popover margin-popover" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
	      // 'X' Button to close popover
	      title: title + '<button type="button" class="close" aria-label="Close" onclick="jQuery(&quot;.popover&quot;).popover(&quot;hide&quot;);">&times;</button>',
	      content: function () {
		  var nodeid = ele.next().attr('id').replace('deployed-', '');;
		  var endpoint = Drupal.url('ajaxnode/deployed/' + nodeid);
		  var node_data = '';
		  $.ajax({
                      async: false,
                      type: 'POST',
                      url: endpoint,
                      dataType: 'json',
                      success: function (data) {
                          node_data = data[1].data;
			  return node_data;
                      },
                      error: function (jqXHR, exception) {
                          return false;
                      }
                  });
		  return node_data;
	      },
	  }).popover('show');
	  return false;
      });
				     
      /**
       *  Close popover, if clicked outside.
       */
      $('body').on('click', function (e) {
	  if ($(e.target).data('toggle') !== 'popover'
	      && $(e.target).parents('.popover.in').length === 0) {
	      $('.popover').popover('destroy');
	  }
      });
      
      
//        $(".frontpage-downtime-block ul.incidents-home-block>li .service-tooltip").hover(function () {
//            var ele = $(this);
//            var offset = ele.offset();
//            var popHeight = ele.parents('li').find('article.popup').height();
//            var finalTop = offset.top - $(window).scrollTop() - popHeight / 2;
//            var popWidth = ele.parents('li').find('article.popup').width();
//            var finalLeft = offset.left - popWidth - 15;
//            if (finalTop < 78 && $('#toolbar-administration').length) {
//                finalTop = 88;
//            } else if (finalTop < 0 && !$('#toolbar-administration').length) {
//                finalTop = 10;
//            }
//            if (finalLeft < 0) {
//                var newWidth = popWidth * 3 / 4;
//                ele.parents('li').find('article.popup').width(newWidth);
//                finalLeft = offset.left - newWidth - 15;
//            }
//            ele.parents('li').find('article.popup')
//                .css('position', 'fixed')
//                .css('top', finalTop)
//                .css('left', finalLeft)
//                .show();
//        }, function () {
//            var ele = $(this).parent('li');
//            ele.find('article.popup')
//                .removeAttr('position')
//                .removeAttr('top')
//                .hide();
//        });

     $('section#block-maintenance div.maintenance-home-info div.maintenance-list ul li div.service-tooltip', context).popover({
          trigger: 'hover',
          container: 'body',
          placement: 'left',
          html: true,
          content: function () {
              var current_wrapper = $(this).parent().find('.downtime-popover-wrapper');
              var nodeid = current_wrapper.attr('id').replace('maintenance-', '');
              if (current_wrapper.html() == '') {
                  if (typeof nodeid !== "undefined") {
                      var endpoint = Drupal.url('ajaxnode/archive/' + nodeid);
                      var current_element = $(current_wrapper, context);
                      $.ajax({
                          async: false,
                          type: 'POST',
                          url: endpoint,
                          dataType: 'json',
                          success: function (data) {
                              var node_data = data[0].data;
                              current_element.html(node_data);
                          },
                          error: function (jqXHR, exception) {
                              return false;
                          }
                      });
                  }

              }
              return current_wrapper.html();

          }
      });

//        $(".frontpage-downtime-block .maintenance-list ul li .service-tooltip").hover(function () {
//            var ele = $(this);
//            var offset = ele.offset();
//            var popHeight = ele.parents('li').find('article.popup').height();
//            var finalTop = offset.top - $(window).scrollTop() - popHeight / 2;
//            var popWidth = ele.parents('li').find('article.popup').width();
//            var finalLeft = offset.left - popWidth - 15;
//            if (finalTop < 78 && $('#toolbar-administration').length) {
//                finalTop = 88;
//            } else if (finalTop < 0 && !$('#toolbar-administration').length) {
//                finalTop = 10;
//            }
//            if (finalLeft < 0) {
//                var newWidth = popWidth * 3 / 4;
//                ele.parents('li').find('article.popup').width(newWidth);
//                finalLeft = offset.left - newWidth - 15;
//            }
//            ele.parents('li').find('article.popup')
//                .css('position', 'fixed')
//                .css('top', finalTop)
//                .css('left', finalLeft)
//                .show();
//        }, function () {
//            var ele = $(this).parent('li');
//            ele.find('article.popup')
//                .removeAttr('position')
//                .removeAttr('top')
//                .hide();
//        });
//
    //Clears from jquery indternal data cache
    $('.block-cust-group-menu-block .dropdown-toggle').removeData('toggle');
    // Removes the data-toggle atribute entirely from the parent anchor link.
    $('.block-cust-group-menu-block .dropdown-toggle')
      .removeAttr('data-toggle');
    // Date time picker on service creation fields found at : /group/24/downtimes/create_downtimes
    $('#edit-startdate-planned, #edit-date-reported, #edit-enddate-planned')
      .prop('readonly', 'readonly')
      .css('background-color', '#fff')
      .datetimepicker({
        format: 'DD.MM.YYYY - HH:mm',
        useCurrent: true,
        showTodayButton: true,
        ignoreReadonly: true,
        sideBySide: true,
        stepping: 5,
        toolbarPlacement: 'bottom',
        showClear: true,
        // debug: true
      })
      .parent().css('position', 'relative');

      $('div.popup-wrapper div.details-wrapper a.downtimes_details_link', context).popover({
          trigger: 'hover',
          container: 'body',
          placement: 'left',
          html: true,
          content: function () {
              var current_wrapper = $(this).parent().parent().find('.downtime-popover-wrapper');
              var nodeid = current_wrapper.attr('id');
              if (current_wrapper.html() == '') {
                  if (typeof nodeid !== "undefined") {
                      var endpoint = Drupal.url('ajaxnode/archive/' + nodeid);
                      var current_element = $(current_wrapper, context);
                      $.ajax({
                          async: false,
                          type: 'POST',
                          url: endpoint,
                          dataType: 'json',
                          success: function (data) {
                              var node_data = data[0].data;
                              current_element.html(node_data);
                          },
                          error: function (jqXHR, exception) {
                              return false;
                          }
                      });
                  }

              }
              return current_wrapper.html();
          }
      });

//        $('div.popup-wrapper').hover(function () {
//            var offset = $(this).offset();
//            var popHeight = $(this).find('article.popup').height();
//            var finalTop = offset.top - $(window).scrollTop() - popHeight / 2;
//            if (finalTop < 78 && $('#toolbar-administration').length) {
//                finalTop = 88;
//            } else if (finalTop < 0 && !$('#toolbar-administration').length) {
//                finalTop = 10;
//            }
//            var popWidth = $(this).find('article.popup').width();
//            var finalLeft = offset.left - popWidth - 10;
//            $(this).find('article.popup')
//                .css('position', 'fixed')
//                .css('top', finalTop)
//                .css('left', finalLeft)
//                .show();
//        }, function () {
//            $(this).find('article.popup')
//                .removeAttr('position')
//                .removeAttr('top')
//                .hide();
//        });

    /*jQuery('div.popup-wrapper')
     .mouseover(function () {
     $('article.popup').hide();
     $(this).find('article.popup').show();
     });

     $('article.popup').mouseout(function () {
     $(this).hide();
     });
     $(window).click(function () {
     $('article.popup').hide();
     });*/

    var options = {};
    // $('div.popup-wrapper').popover(options);
    // $('div.popup-wrapper').each(function() {
    //     var $this = $(this);
    //     $this.popover({
    //         content: $(this).find('article.popup').html(),
    //         trigger: 'hover',
    //         placement: 'left',
    //         html: true,
    //     });
    // });

    /*        $('.frontpage-downtime-block .maintenance-home-info').find('div.maintenance-list').css('width', '47%').css('float', 'none');
     $('.frontpage-downtime-block .maintenance-home-info').isotope({
     layoutMode: 'masonry',
     itemSelector: '.frontpage-downtime-block .maintenance-home-info div.maintenance-list'
     });
     */
    // $('.maintenance-home-info').css('-webkit-column-count',2).css('-moz-column-count',2).css('column-count',2);

    window.onload = imAttachmentScrooltop;
  });

//  $('#user-register-form .form-email,[type="password"]')
//    .bind("cut copy paste", function (e) {
//      e.preventDefault();
//    });

  $("div.ckeditor-custom-wrapper table").each(function (index, element) {
    //if (!$( element ).hasClass( "ckeditor-table-responsive" )) {
    $(element).wrap("<div class='table-responsive'></div>");
    //}
  });

	
    }
}
})
(jQuery);


function reset_form_elements() {
//  alert('hi');
  url = window.location.href;
  res = url.split('?');
//  window.location.assign(res['0']);
// alert(res['0']);
//  window.history.pushState( {}, null, res['0']);
  window.location = res['0'];
//  window.history.pushState( {}, null, res['0']);
  return false;
}

function imAttachmentScrooltop() {
  if (GetURLParameter('state')) {
    jQuery('html, body').animate({
      scrollTop: (jQuery('#im-attachment-files-list').offset().top)
    }, 2000);
  }
}
function GetURLParameter(sParam) {
  var sPageURL = window.location.search.substring(1);
  var sURLVariables = sPageURL.split('&');
  for (var i = 0; i < sURLVariables.length; i++) {
    var sParameterName = sURLVariables[i].split('=');
    if (sParameterName[0] == sParam) {
      return sParameterName[1];
    }
  }
}
