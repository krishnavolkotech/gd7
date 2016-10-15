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
    $node_id = $get_uri['3'];
    
    $node =  \Drupal\node\Entity\Node::load($node_id);
    $node->field_enable_downtime->value = 1;
    $field_value = 1;

    if($get_uri['4'] == 'delete') {
      $field_value = 0;
      $node->field_enable_downtime->value = 0;
    }

      $update_result = \Drupal::database()->update('node__field_enable_downtime');
			$update_result->fields([
			  'field_enable_downtime_value' => $field_value
			]);
			$update_result->condition('entity_id', $node_id);
			$update_result->execute();
      
    //HzdservicesStorage::default_services_insert($node);
   //  HzdservicesStorage::update_downtime_notifications($node, $node->release_type->target_id);

    $batch = array(
      'operations' => array(),
      'title' => t('Updating Downtimes Notifications'),
      'init_message' => t('Updating Downtimes Notifications...'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message' => t('An error occurred during processing'),
    );

    if ($node->field_enable_downtime->value) {
      //hzd_notifications_delete_subscriptions(array('type' => 'service'), array('service' => $node->nid, 'type' => 'downtimes'));
      //$batch['operations'][] = array('notifications_insert', array($node->nid, "downtimes", $rel_type));
      
      $batch['operations'][] = array(
        'notifications_insert',
        array(
            $node->id(), "downtimes", $node->release_type->target_id
          )
        );
    }
    elseif ($node->field_enable_downtime->value == '') {
      //hzd_notifications_delete_subscriptions(array('type' => 'service'), array('service' => $node->nid, 'type' => 'downtimes'));
    }
 //   $url = array('/manage_services');
    batch_set($batch);

    return batch_process('manage_services');
  }
  

}
