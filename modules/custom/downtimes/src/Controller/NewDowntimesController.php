<?php

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;

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
  public function newDowntimes($node) {

    //Current Incidents
    $string = 'current';
    $output .= "<div class = 'downtime_notes'>" . \Drupal::config('downtimes.settings')->get('current_downtimes') . '</div>';
    $data = "<div class ='curr_incidents_form'>" . drupal_get_form('downtimes_filters', 'incidents');
    $data .= "<div class = 'reset_form'>" . drupal_render(reset_filter_forms('incidents')) . "</div></div>";

    $output .= theme('current', $data, $string);
    $current_time = time();
    $sql_where = " and scheduled_p = 0 and resolved = 0 and sd.startdate_planned <= $current_time";
    $string = 'incidents';
    $default_downtimes = current_incidents($sql_where, $string);
    $output .= "<div id = 'incidents_search_results_wrapper'>" . $default_downtimes . "</div>";

    //Planned Maintenence
    $sql_where = "  and scheduled_p = 1 and resolved = 0 ";
    $string = 'maintenance';
    $data = "<div class ='curr_incidents_form maintenance_filters'>" . drupal_get_form('downtimes_filters', $string);
    $data .= "<div class = 'reset_form'>" . drupal_render(reset_filter_forms($string)) . "</div></div>";

    $output .= theme('current', $data, $string);
    $default_downtimes = current_incidents($sql_where, $string);
    $output .= "<div id = 'maintenance_search_results_wrapper'>" . $default_downtimes . "</div>";

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: newDowntimes with parameter(s): $node'),
      '#attached' => array(
        'library' => array(
          'downtimes.newdowntimes',
        ),
      ),
    ];
  }

}
