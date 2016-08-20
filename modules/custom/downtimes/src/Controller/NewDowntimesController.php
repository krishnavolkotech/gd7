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
    $incidents_data = \Drupal::formBuilder()->getForm('\Drupal\downtimes\Form\DowntimesFilter', 'incidents', $group);
    $current_time = time();
    $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
    $string = 'incidents';
    $incident_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string);

    //Planned Maintenence
    $maintenance_data = \Drupal::formBuilder()->getForm('Drupal\downtimes\Form\DowntimesFilter', 'maintenance', $group);
    $sql_where = "  and sd.scheduled_p = 1 and sd.resolved = 0 ";
    $string = 'maintenance';
    $maintenance_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string);

    $result = array();

    $result['incidents_form'] = $incidents_data;
    $result['incidents_reset_form'] = HzdcustomisationStorage::reset_form();
    $result['incidents_table'] = $incident_downtimes;
    $result['maintenance_form'] = $maintenance_data;
    $result['maintenance_reset_form'] = HzdcustomisationStorage::reset_form();
    $result['maintenance_table'] = $maintenance_downtimes;

    $response = $result;
    return $response;
  }

}
