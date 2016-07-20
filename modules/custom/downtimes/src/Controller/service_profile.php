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
	 * Display published services
	 */

	function service_profiles() {
	  drupal_set_title(t("Service Profile"));
	  $service_names = "SELECT n.nid, n.title as service
		            FROM {content_field_enable_downtime} cfed , {node} n, {content_type_services} cts 
		            WHERE n.nid = cfed.nid and cfed.nid = cts.nid and cts.field_downtime_type_value = 'Publish' and field_enable_downtime_value = 1 order by service";

	  $sql = db_query($service_names);
	  $services = "<p>" . t("Please select a Service") . "</p>";
	  $services .= "<div class='service-profile'><ul>";
	  while ($service_names = db_fetch_array($sql)) {
	    $id = db_result(db_query("SELECT nid FROM {content_type_service_profile} WHERE field_dependent_service_nid = %d", $service_names['nid']));
	    if ($id) {
	      $path_alias = db_result(db_query("SELECT dst FROM {url_alias} WHERE src = '%s'", 'node/' . $id));
	      $services .= "<li>" . l($service_names['service'], $path_alias . '/edit') . "</li>";
	    }
	    else {
	      $services .= "<li>" . l($service_names['service'], 'node/' . MAINTENANCE_GROUP_ID . '/add/service-profile', array('query' => array('service' => $service_names['nid']))) . "</li>";
	    }
	  }
	  $services .= "</ul></div>";
	  return $services;
	}
}
