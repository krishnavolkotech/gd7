<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Controller\HzdMigrateNotifications
 */

namespace Drupal\hzd_notifications\Controller;
use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
define('EXEOSS', \Drupal::config('hzd_release_management.settings')->get('ex_eoss_service_term_id'));

/**
 * Class HzdNotifications
 * @package Drupal\hzd_notifications\Controller
 */
class HzdMigrateNotifications {

  // konsons notification settings
  public function migrate_notifications() {
  
    $batch = array(
      'operations' => array(),
      'title' => t('Migrate Notifications'),
      'init_message' => t('Migrating Notifications...'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message' => t('An error occurred during processing'),
    );
  
    $uids = db_query("SELECT uid FROM users ORDER BY uid ASC")->fetchCol();
    foreach($uids as $user_vals) {
      if($user_vals != 0) {
        $batch['operations'][] = array('migrate_each_user', array($user_vals, 459));
        $batch['operations'][] = array('migrate_each_user', array($user_vals, 460));
      }
    }
    
    $url = array('node/1');
    batch_set($batch);
    return batch_process($url);
  }
  
  /*function migrate_notifications_finished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One post processed.', '@count posts processed.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }*/
  
}
