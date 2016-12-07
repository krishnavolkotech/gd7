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
    $selected_type = \Drupal::request()->query->get('downtimes_type');
    $time_period = \Drupal::request()->query->get('time_period');
    $selected_state = \Drupal::request()->query->get('states');
    $services_effected = \Drupal::request()->query->get('services_effected');
    $filter_startdate = \Drupal::request()->query->get('filter_startdate');
    $search_string = \Drupal::request()->query->get('search_string');

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
      $service = $services_effected;
      $state = $selected_state;
      $search_string = $search_string;
      $default_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string, $service, $search_string, $limit, $state, $filter_enddate);
    }
    else {
      $default_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string);
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
    $selected_type = \Drupal::request()->query->get('downtimes_type');
    $time_period = \Drupal::request()->query->get('time_period');
    $selected_state = \Drupal::request()->query->get('states');
    $services_effected = \Drupal::request()->query->get('services_effected');
    $filter_startdate = \Drupal::request()->query->get('filter_startdate');
    $filter_enddate = \Drupal::request()->query->get('filter_enddate');
    $search_string = \Drupal::request()->query->get('search_string');
    $archived_type = \Drupal::request()->query->get('string');

    $string = 'archived';
    $sql_where = " and resolved = 1";

    if (isset($search_string) && $search_string != '') {
      if ($search_string != t('Search Reason')) {
        $sql_where .= " and description like '%$search_string%' ";
      }
    }
    if (isset($time_period)) {
      $options['time_period'] = $time_period;
    }

    if (isset($selected_type) && $selected_type != 'select' && $selected_type != '') {
      $type_filter = $selected_type;
      $sql_where .= " and scheduled_p = $type_filter ";
    }

    $incidents_parameters = DowntimesFilter::current_incidents_search($options, $string);
    if (isset($incidents_parameters['sql_where'])) {
      $sql_where .= $incidents_parameters['sql_where'];
    }
    $service = $services_effected;
    $state = $selected_state;

    $search_parameters = array(
      'sql_where' => $sql_where,
      'service' => $service,
      'state' => $state,
      'string' => $string,
      'search_string' => $search_string
    );

    return $search_parameters;
  }

  static public function get_form_options() {
    /*
     * to do filters for all and code optimization 
     */
    $selected_type = \Drupal::request()->query->get('downtimes_type');
    $time_period = \Drupal::request()->query->get('time_period');
    $selected_state = \Drupal::request()->query->get('states');
    $services_effected = \Drupal::request()->query->get('services_effected');
    $filter_startdate = \Drupal::request()->query->get('filter_startdate');
    $filter_enddate = \Drupal::request()->query->get('filter_enddate');
    $search_string = \Drupal::request()->query->get('search_string');
    $archived_type = \Drupal::request()->query->get('string');

    if (in_array($selected_state, array(0, 1))) {
      $options['state_id'] = $selected_state;
    }
    if (isset($services_effected)) {
      $options['service_id'] = $services_effected;
    }
    if (isset($filter_startdate) && $filter_startdate != '') {
      $start_date = $filter_startdate;
      $date = explode('.', $start_date);
      $day = $date[0];
      $month = $date[1];
      $year = $date[2];
      if ($start_date) {
        $filter_start_date = mktime(0, 0, 0, $month, $day, $year);
        $options['start_date'] = $filter_start_date;
      }
    }
    if (isset($filter_enddate) && $filter_enddate != '') {
      $end_date = $filter_enddate;
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
