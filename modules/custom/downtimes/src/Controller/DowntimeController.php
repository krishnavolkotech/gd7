<?php

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\hzd_customizations\HzdcustomisationStorage;

/**
 * Class DowntimeController.
 *
 * @package Drupal\downtimes\Controller
 */
class DowntimeController extends ControllerBase {

  /**
   * Callback for service_profiles.
   */
  function service_profiles($node) {
    // $service_data['content'] = HzdcustomisationStorage::service_profiles();
    $result['content'] = HzdcustomisationStorage::service_profiles();
    // Echo '<pre>';  print_r($result);  exit;.
    return $result;
  }

}
