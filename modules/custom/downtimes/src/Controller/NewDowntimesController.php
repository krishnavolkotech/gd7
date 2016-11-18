<?php

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_customizations\HzdcustomisationStorage;

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
    $_SESSION['downtime_type'] = 'incident';
    $incidents_data = \Drupal::formBuilder()->getForm('\Drupal\downtimes\Form\DowntimesFilter', 'incidents', $group);
    $current_time = time();
    $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
    $string = 'incidents';
    $incident_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string);

    $_SESSION['downtime_type'] = 'maintenance';
    //Planned Maintenence
    $maintenance_data = \Drupal::formBuilder()->getForm('Drupal\downtimes\Form\DowntimesFilter', 'maintenance', $group);
    $sql_where = "  and sd.scheduled_p = 1 and sd.resolved = 0 ";
    $string = 'maintenance';
    unset($_SESSION['downtime_type']);
    $maintenance_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string);

    $result = array();

    // $result['incidents_form_render']['#prefix'] = "<div class ='curr_incidents_form'>";
    $result['incidents_form_render']['incidents_form'] = $incidents_data;
    $result['incidents_form_render']['incidents_reset_form'] = HzdcustomisationStorage::reset_form();
    // $result['incidents_form_render']['#suffix'] = "</div>";

    $result['incidents_table_render']['#prefix'] = "<div id = 'incidents_search_results_wrapper'>";
    $result['incidents_table_render']['incidents_table'] = $incident_downtimes;
    $result['incidents_table_render']['#suffix'] = "</div>";
    //  $result['maintenance_form_render']['#prefix'] = "<div class ='curr_incidents_form maintenance_filters'>"; 
    $result['maintenance_form_render']['maintenance_form'] = $maintenance_data;
    $result['maintenance_form_render']['maintenance_reset_form'] = HzdcustomisationStorage::reset_form();
    //  $result['maintenance_form_render']['#suffix'] = "</div>";
    $result['maintenance_table_render']['#prefix'] = "<div id = 'maintenance_search_results_wrapper'>";
    $result['maintenance_table_render']['maintenance_table'] = $maintenance_downtimes;
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
