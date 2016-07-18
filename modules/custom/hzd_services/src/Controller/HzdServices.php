<?php

/**
 * @file
 * Contains \Drupal\hzd_services\Controller\HzdServices.
 *
 */

namespace Drupal\hzd_services\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_services\HzdservicesStorage;

/**
 * Class HzdServices
 * @package Drupal\hzd_services\Controller
 */
class HzdServices extends ControllerBase {

  function display_services() {
    // TODO: Need to wrote Breadcrumb
    $output[]['#attached']['library'] = array('hzd_services/hzd_services');
    $output[] = HzdservicesStorage::service_info();
    $output[] = HzdservicesStorage::service_list();
    return $output;
  }

  function service_notifications_update_downtime() {
    $current_path = \Drupal::service('path.current')->getPath();
    $get_uri = explode('/', $current_path);
    $node_id = $get_uri[2];
    $node = node_load($node_id);
    $node->field_enable_downtime->value = 1;
    $field_value = 1;

    if($get_uri[3] == 'delete') {
      $field_value = '';
      $node->field_enable_downtime->value = 0;
    }
    db_update('node__field_enable_downtime')->fields(array('field_enable_downtime_value' => $field_value))
	    ->condition('entity_id', $node_id)
      ->execute();
    //HzdservicesStorage::default_services_insert($node);
    HzdservicesStorage::update_downtime_notifications($node, $node->release_type->target_id);
    $output[] = array('#markup' => 'updated');
    return $output;
  }

}
