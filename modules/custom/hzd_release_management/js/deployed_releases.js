(function ($) {
    Drupal.behaviors.deployedrelease = {
        attach: function (context, settings) {
            if ( ie && ie === 7) {
                $('#edit-deployed-date-wrapper').css("width", "110px");
                $('#edit-deployed-date-wrapper').css("border", "none");
                $('#deployedreleases_posting input.form-submit').css("margin-left", "0px");
                $('#deployedreleases_posting .reset_form').css("margin-left", "50px");
                $('#deployedreleases_posting .reset_form input.form-submit').css("margin-left", "-40px");
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
                        if (dateele[2].length == 2) {
                            dateele[2] = '20' + dateele[2];
                        }
                        var date = dateele[2] + dateele[1] + dateele[0];
                        return parseInt(date, 10);

                    }
                },
                // set type, either numeric or text
                type: 'numeric'
            });

            $("#current_deploysortable").tablesorter({
                headers: {
                    4: {sorter: false},
                    3: {sorter: 'deployed_date'}
                },
                widgets: ['zebra']
            });

            $("#archived_deploysortable").tablesorter({
                headers: {
                    4: {sorter: false},
                    3: {sorter: 'deployed_date'}
                },
                widgets: ['zebra']
            });


            /*	
             * archive deployed releases	
             */

            $('.archive_deployedRelease').click(function () {
                var is_archive = confirm("Do you really want to archive?");
                if (is_archive == true) {
                    var nid = $(this).attr('nid');
                    $(this).next().prepend('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');
                    var url = drupalSettings.deploy_release.basePath + '/archive_deployedreleases';
                    
                    $.post(url, {'nid': nid}, function (data) {
                        $('.loader').html(' ');
                        window.location.reload();
                        return false;
                    });
                }
                return false;
            });


            $('.public_deployed_releses_output .pager li a').click(function () {
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

            var ie = (function(){

                var undef,
                    v = 3,
                    div = document.createElement('div'),
                    all = div.getElementsByTagName('i');

                while (
                    div.innerHTML = '<!--[if gt IE ' + (++v) + ']><i></i><![endif]-->',
                    all[0]
                );

                return v > 4 ? v : undef;

            }());



            $('.deployed_releses_output .pager li a').click(function () {
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
})(jQuery);

jQuery('#edit-deployed-date').datepicker({ dateFormat: 'dd.mm.yy' });

function reset_form_elements() {
   // var type = drupalSettings.deploy_release.type;
    $('#edit-deployed-services').val(0);
    $('#edit-deployed-releases').val(0);
    $('#edit-deployed-date').val('');
    $('#edit-deployed-environment').val(0);
    $('#edit-deployed-type').val('current')
    
    $('.state_search_dropdown select').val(0);
    $('.service_search_dropdown select').val(0);
    $('.releases_search_dropdown select').val(0);
    $('.filter_start_date input').val('');
    $('.filter_end_date input').val('');
    $('.limit_search_dropdown select').val(0);
    
    if (type !== undefined) {
        window.location = window.location;
    }
}

