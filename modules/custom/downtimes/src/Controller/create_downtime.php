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
	function create_downtime() {
	  $breadcrumb = array();
	  $breadcrumb[] = l(t('Home'), NULL);
	  if (isset($_SESSION['Group_name'])) {
	    $breadcrumb[] = l(t($_SESSION['Group_name']), 'node/' . $_SESSION['Group_id']);
	    $breadcrumb[] = l(t('Incidents and Maintenances'), 'node/' . $_SESSION['Group_id'] . '/downtimes');
	  }
	  else {
	    $breadcrumb[] = l(t('Incidents and Maintenances'), 'downtimes');
	  }
	  $breadcrumb[] = t('Report an Incident');


	  include(drupal_get_path('module', 'node') . '/node.pages.inc');
	  $string = 'create';
	  $output = variable_get('report_downtimes', ' ');
	  $output .= node_add('downtimes');
	  drupal_set_breadcrumb($breadcrumb);
	  return $output;
	}
}
