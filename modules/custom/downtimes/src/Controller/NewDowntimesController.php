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

    //Planned Maintenence

    $maintenance_data = \Drupal::formBuilder()->getForm('Drupal\downtimes\Form\DowntimesFilter', 'maintenance', $group);
    //$data .= "<div class = 'reset_form'>" . drupal_render(reset_filter_forms($string)) . "</div></div>";


    $output = array($incidents_data, $maintenance_data);
    return $output;
  }

}
