<?php

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_customizations\HzdcustomisationStorage;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

/**
 * Class DowntimeController.
 *
 * @package Drupal\downtimes\Controller
 */
class DowntimeController extends ControllerBase {

  /**
   * Callback for service_profiles.
   */
  function service_profiles($group) {
    // $service_data['content'] = HzdcustomisationStorage::service_profiles();
    $result['content'] = HzdcustomisationStorage::service_profiles();
    // Echo '<pre>';  print_r($result);  exit;.
    return $result;
  }

  function create_downtime($group) {
    $type = node_type_load("downtimes"); // replace this with the node type in which we need to display the form for
    $samplenode = $this->entityManager()->getStorage('node')->create(array(
      'type' => $type->id(),
    ));
    $node_create_form = $this->entityFormBuilder()->getForm($samplenode);

    return array(
      '#type' => 'markup',
      '#markup' => render($node_create_form),
    );
  }

  function getTitle() {
    $current_path = \Drupal::service('path.current')->getPath();
    $downtime_type = pathinfo($current_path, PATHINFO_BASENAME);
    if ($downtime_type == 'create_maintenance') {
      return 'Create Maintenance';
    }
    else {
      return 'Report an Incident';
    }
  }

}
