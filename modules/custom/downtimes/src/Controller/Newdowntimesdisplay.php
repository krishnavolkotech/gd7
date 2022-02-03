<?php
/**
 * @file
 * Contains \Drupal\problem_management\Controller\ProblemsController
 */

namespace Drupal\problem_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\problem_management\HzdproblemmanagementHelper;

/**
 * Class CurrentProblemsController
 * @package Drupal\problem_management\Controller
 */
class ProblemsController extends ControllerBase {

/*
 * Callback function for the downtimes display
 * Display the current incidemnts and maintenances in the table format in one page.
 * provides filters for filtering the content separetly for both incidents and maitenances.
 *
 */

function new_downtimes_display() {
  $breadcrumb = array();
  $output = "";
  $breadcrumb[] = l(t('Home'), NULL);
  if (isset($_SESSION['Group_name'])) {
    $breadcrumb[] = l(t($_SESSION['Group_name']), 'node/' . $_SESSION['Group_id']);
    $breadcrumb[] = l(t('Incidents and Maintenances'), 'node/' . $_SESSION['Group_id'] . '/downtimes');
  }
  else {
    $breadcrumb[] = l(t('Incidents and Maintenances'), 'downtimes');
  }
  $breadcrumb[] = t('Current Downtimes and Maintenances');
  drupal_set_breadcrumb($breadcrumb);


  drupal_add_js(drupal_get_path('module', 'downtimes') . '/downtimes_filter.js');
  //Current Incidents

  $string = 'current';
  $output .= "<div class = 'downtime_notes'>" . variable_get('current_downtimes', ' ') . '</div>';
  $data = "<div class ='curr_incidents_form'>" . drupal_get_form('downtimes_filters', 'incidents');
  $data .= "<div class = 'reset_form'>" . \Drupal::service('renderer')->render(reset_filter_forms('incidents')) . "</div></div>";

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
  $data .= "<div class = 'reset_form'>" . \Drupal::service('renderer')->render(reset_filter_forms($string)) . "</div></div>";

  $output .= theme('current', $data, $string);
  $default_downtimes = current_incidents($sql_where, $string);
  $output .= "<div id = 'maintenance_search_results_wrapper'>" . $default_downtimes . "</div>";

  return $output;
}

}
