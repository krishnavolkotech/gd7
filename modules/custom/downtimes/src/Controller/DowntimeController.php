<?php
/**
 * @file
 * Contains \Drupal\downtimes\Controller\DowntimeController
 */

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\hzd_customizations\HzdcustomisationStorage;

/**
 * Class DowntimeController
 * @package Drupal\downtimes\Controller
 */
class DowntimeController extends ControllerBase {
  /*
   * callback for service_profiles
   */
   function service_profiles($node) {
       // $service_data['content'] = HzdcustomisationStorage::service_profiles();
       $result['content'] = HzdcustomisationStorage::service_profiles();
       // echo '<pre>';  print_r($result);  exit;

       return  $result;
   }
}
