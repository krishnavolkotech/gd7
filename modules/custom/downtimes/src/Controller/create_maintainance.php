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
	 *  Report a maintenance form
	 */

	function create_maintenance() {
	  $breadcrumb = array();
	  $breadcrumb[] = l(t('Home'), NULL);
	  include(drupal_get_path('module', 'node') . '/node.pages.inc');
	  $string = 'create';
	  $output = variable_get('report_maintenance', ' ');
	  $output .= node_add('downtimes');
	  drupal_set_breadcrumb($breadcrumb);
	  return $output;
	}
}
