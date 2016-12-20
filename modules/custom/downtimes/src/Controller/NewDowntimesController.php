<?php

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_customizations\HzdcustomisationStorage;
use Drupal\downtimes\Form\DowntimesFilter;

if (!defined('PAGE_LIMIT')) {
  define('PAGE_LIMIT', 20);
}
/**
 * Class NewDowntimesController.
 *
 * @package Drupal\downtimes\Controller
 */
class NewDowntimesController extends ControllerBase {

  /**
   * Newdowntimes.
   *
   * @return string
   *   Return Hello string.
   */
  public function newDowntimes($group) {
      
    //Current Incidents
//    $_SESSION['downtime_type'] = 'incident';
//    $filter_value = HzdcustomisationStorage::get_downtimes_filters();
    $type = $filter_value['downtime_type'];    
    $incidents_data = \Drupal::formBuilder()->getForm(
        '\Drupal\downtimes\Form\DowntimesFilter', 'incidents', $group);
//    $current_time = REQUEST_TIME;
//    $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
    $string = 'incidents';
//    if (isset($type) && $type == 'incidents') {
//      $options = DowntimesFilter::get_form_options();
////      $search_parameters = DowntimesFilter::get_search_parameters(
////              $type, $options);
////      $sql_where = $search_parameters['sql_where'];
////
////      $limit = $filter_value['limit'] ?
////          $filter_value['limit'] : PAGE_LIMIT;
////      $string = $search_parameters['string'];
////      if (isset($search_parameters['service'])) {
////        $service = $search_parameters['service'];
////      } else {
////        $service = NULL;
////      }
//
////      $state = $search_parameters['state'];
//
//      if (isset($search_parameters['search_string'])) {
//        $search_string = $search_parameters['search_string'];
//      }  else {
//        $search_string = NULL;
//      }
//      $incident_downtimes = HzdcustomisationStorage::current_incidents('incident');
//    } else {
//      $incident_downtimes = HzdcustomisationStorage::current_incidents('incident');
//    }
    // $_SESSION['downtime_type'] = 'maintenance';
    //Planned Maintenence
//    $maintenance_data = \Drupal::formBuilder()->getForm('Drupal\downtimes\Form\DowntimesFilter', 'maintenance', $group);
//    $sql_where = "  and sd.scheduled_p = 1 and sd.resolved = 0 ";
//    $string = 'maintenance';
//    unset($_SESSION['downtime_type']);
//    if (isset($type) && $type == 'maintenance') {
//
//      $options = DowntimesFilter::get_form_options();
//      $search_parameters = DowntimesFilter::get_search_parameters(
//          $type, $options);
//      $sql_where = $search_parameters['sql_where'];
//
//      $limit = $filter_value['limit'] ?
//          $filter_value['limit'] : $this->PAGE_LIMIT;
//      $string = $search_parameters['string'];
//      $service = $search_parameters['service'];
//      $state = $search_parameters['state'];
//      $search_string = $search_parameters['search_string'];
//      $maintenance_downtimes =
//        HzdcustomisationStorage::current_incidents('maintenance', $string,
//      $service,$search_string, $limit, $state, $filter_value['filter_enddate']);
//    } else {
//      $maintenance_downtimes = HzdcustomisationStorage::current_incidents(
//        'maintenance', $string);
//    }
//    $result = array();
//
     $result['incidents_form_render']['#prefix'] = "<div class ='curr_incidents_form'>";
    $result['incidents_form_render']['incidents_form'] = $incidents_data;
//    $result['incidents_form_render']['incidents_reset_form'] = HzdcustomisationStorage::reset_form();
    // $result['incidents_form_render']['#suffix'] = "</div>";

    $result['incidents_table_render']['#prefix'] = "<div id = 'incidents_search_results_wrapper'>";
    $result['incidents_table_render']['incidents_table'] = HzdcustomisationStorage::current_incidents('incident');
    $result['incidents_table_render']['#suffix'] = "</div>";
    //  $result['maintenance_form_render']['#prefix'] = "<div class ='curr_incidents_form maintenance_filters'>"; 
//    $result['maintenance_form_render']['maintenance_form'] = $maintenance_data;
//    $result['maintenance_form_render']['maintenance_reset_form'] = HzdcustomisationStorage::reset_form();
    //  $result['maintenance_form_render']['#suffix'] = "</div>";
    $result['maintenance_table_render']['#prefix'] = "<div id = 'maintenance_search_results_wrapper'>";
    $result['maintenance_table_render']['maintenance_table'] = HzdcustomisationStorage::current_incidents(
        'maintenance');
    $result['maintenance_table_render']['#suffix'] = "</div>";
    $response = $result;
    return $response;
  }
  
  function getDependentServices($service = null){
    $data = HzdcustomisationStorage::getDependantServices($service);
    echo json_encode($data);exit;
//    return new \Symfony\Component\HttpFoundation\JsonResponse((array)$data);
  }

}
