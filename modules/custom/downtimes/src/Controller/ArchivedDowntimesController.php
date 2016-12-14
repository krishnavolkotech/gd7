<?php

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_customizations\HzdcustomisationStorage;
use Drupal\downtimes\Form\DowntimesFilter;

if (!defined('PAGE_LIMIT')) {
  define('PAGE_LIMIT', 20);
}

/**
 * Class ArchivedDowntimesController.
 *
 * @package Drupal\downtimes\Controller
 */
class ArchivedDowntimesController extends ControllerBase {

  /**
   * Group.
   *
   * @return string
   *   Return Hello string.
   */
  public function archivedDowntimes($group) {
    $filter_value = HzdcustomisationStorage::get_downtimes_filters();
    $string = 'archived';
    $filter_enddate = \Drupal::request()->query->get('filter_enddate');
    $archived_type = \Drupal::request()->query->get('string');
    $archived_data = \Drupal::formBuilder()->getForm('\Drupal\downtimes\Form\DowntimesFilter', $string, $group);
    $sql_where = " and resolved = 1 ";
    if (isset($archived_type)) {
      $options = $this->get_form_options();
      $search_parameters = $this->get_search_parameters($options);
      $sql_where = $search_parameters['sql_where'];
      $limit = PAGE_LIMIT;
      $service = $filter_value['services_effected'];
      $state = $filter_value['states'];
      $search_string = $filter_value['string'];
      $default_downtimes = HzdcustomisationStorage::current_incidents(
          $sql_where, $string, $service, $search_string, 
          $limit, $state, $filter_value['filter_enddate']);
    }
    else {
      $default_downtimes = HzdcustomisationStorage::current_incidents(
          $sql_where, $string);
    }

    $result = array();
    $result['archive_form_render']['#prefix'] = "<div class ='curr_incidents_form'>";
    $result['archive_form_render']['archive_form'] = $archived_data;
//    $result['archive_form_render']['archive_reset_form'] = HzdcustomisationStorage::reset_form();
    $result['archive_form_render']['#suffix'] = "</div>";
    $result['archive_table_render']['#prefix'] = "<div id = 'archived_search_results_wrapper'>";
    $result['archive_table_render']['archive_table'] = $default_downtimes;
    $result['archive_table_render']['#suffix'] = '</div>';
    $result['#title'] = t('Störungen und Blockzeiten');
    $response = $result;
    return $response;
  }

  static public function get_search_parameters($options) {
    $filter_value = HzdcustomisationStorage::get_downtimes_filters();
    $string = 'archived';
    $sql_where = " and resolved = 1";

    if (isset($filter_value['string']) && $filter_value['string'] != '') {
      if ($filter_value['string'] != t('Search Reason')) {
        $sql_where .= " and description like '%" . $filter_value['string'] . "%' ";
      }
    }
    if (isset($filter_value['time_period'])) {
      $options['time_period'] = $filter_value['time_period'];
    }

    if (isset($filter_value['type']) && $filter_value['type'] != 'select'
        && $filter_value['type'] != '') {
      $type_filter = $filter_value['type'];
      $sql_where .= " and scheduled_p = $type_filter ";
    }

    $incidents_parameters = DowntimesFilter::current_incidents_search(
        $options, $string);
    if (isset($incidents_parameters['sql_where'])) {
      $sql_where .= $incidents_parameters['sql_where'];
    }
    $service = $filter_value['services_effected'];
    $state = $filter_value['states'];

    $search_parameters = array(
      'sql_where' => $sql_where,
      'service' => $service,
      'state' => $state,
      'string' => $string,
      'search_string' => $filter_value['string'],
    );

    return $search_parameters;
  }

  static public function get_form_options() {
    /*
     * to do filters for all and code optimization 
     */
    $filter_value = HzdcustomisationStorage::get_downtimes_filters();
    if (in_array($filter_value['states'], array(0, 1))) {
      $options['state_id'] = $filter_value['states'];
    }
    if (isset($filter_value['services_effected'])) {
      $options['service_id'] = $filter_value['services_effected'];
    }
    if (isset($filter_value['filter_startdate']) && 
        $filter_value['filter_startdate'] != '') {
      $start_date = $filter_value['filter_startdate'];
      $date = explode('.', $start_date);
      $day = $date[0];
      $month = $date[1];
      $year = $date[2];
      if ($start_date) {
        $filter_start_date = mktime(0, 0, 0, $month, $day, $year);
        $options['start_date'] = $filter_start_date;
      }
    }
    if (isset($filter_value['filter_enddate']) &&
        $filter_value['filter_enddate'] != '') {
      $end_date = $filter_value['filter_enddate'];
      $date = explode('.', $end_date);
      $day = $date[0];
      $month = $date[1];
      $year = $date[2];
      if ($end_date) {
        $filter_end_date = mktime(23, 59, 59, $month, $day, $year);
        $options['end_date'] = $filter_end_date;
      }
    }
    return $options;
  }

}
