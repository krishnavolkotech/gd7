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
 * Callback for  Archived service downtimes
 * Archived downtimes are resolved downtimes which will have the status resolved = 1 
 */

	function archived_page() {
	  $get_uri = explode('/', $_REQUEST['q']);
	  if ($get_uri[2] == 'archiv') {
	    unset($_SESSION['incident_sql_where']);
	    unset($_SESSION['incident_service']);
	    unset($_SESSION['incident_state']);
	    unset($_SESSION['incident_search_string']);
	    unset($_SESSION['incident_limit']);
	  }
	  $breadcrumb = array();
	  $breadcrumb[] = l(t('Home'), NULL);
	  if (isset($_SESSION['Group_name'])) {
	    $breadcrumb[] = l(t($_SESSION['Group_name']), 'node/' . $_SESSION['Group_id']);
	    $breadcrumb[] = l(t('Incidents and Maintenances'), 'node/' . $_SESSION['Group_id'] . '/downtimes');
	  }
	  else {
	    $breadcrumb[] = l(t('Incidents and Maintenances'), 'downtimes');
	  }
	  $breadcrumb[] = t('Archive');
	  drupal_set_breadcrumb($breadcrumb);

	  drupal_add_js(drupal_get_path('module', 'downtimes') . '/downtimes_filter.js');
	  global $user;
	  $current_time = time();
	  $sql_where = " and resolved = 1 ";
	  $string = 'archived';
	  $output .= "<div class = 'downtime_notes'>" . variable_get('archived_downtimes', ' ') . '</div>';
	  $output .= "<div class ='curr_incidents_form'>" . drupal_get_form('downtimes_filters', $string);
	  $output .= "<div class = 'archive_reset_form'>" . \Drupal::service('renderer')->render(reset_filter_forms($string)) . "</div></div>";
	  $sql_wheres = $_SESSION['incident_sql_where'] ? $_SESSION['incident_sql_where'] : $sql_where;
	  $service = $_SESSION['incident_service'] ? $_SESSION['incident_service'] : NULL;
	  $state = $_SESSION['incident_state'] ? $_SESSION['incident_state'] : NULL;
	  $search_string = $_SESSION['incident_search_string'] ? $_SESSION['incident_search_string'] : NULL;
	  $limit = $_SESSION['incident_limit'] ? $_SESSION['incident_limit'] : NULL;
	  $output .= theme('current', $data, $string);
	  $output .= "<div id = 'archived_maintenance_search_results_wrapper'>" . current_incidents($sql_wheres, $string, $service, $search_string, $limit, $state) . "</div>";
	  return $output;
	}
}
