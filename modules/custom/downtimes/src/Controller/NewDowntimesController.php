<?php

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_customizations\HzdcustomisationStorage;
use Drupal\downtimes\Form\DowntimesFilter;

if (!defined('PAGE_LIMIT')) {
    define('PAGE_LIMIT', 20);
}

/**
 * Class NewDowntimesController.
 *
 * @package Drupal\downtimes\Controller
 */
class NewDowntimesController extends ControllerBase {
  /**
   * Newdowntimes.
   *
   * @return string
   *   Return Hello string.
   */
  public function newDowntimes($group) {
    if (!is_object($group)) {
      $group = \Drupal\group\Entity\Group::load($group);
    }
    $user = \Drupal::service('current_user');
    $groupMember = $group->getMember($user);
    if (($groupMember && $groupMember->getGroupContent()
          ->get('request_status')->value == 1) || $user->id() == 1 || in_array('site_administrator', $user->getRoles())
    ) {
      $incidents_data = \Drupal::formBuilder()->getForm(
        '\Drupal\downtimes\Form\DowntimesFilter', 'incidents', $group);
      $result['incidents_form_render']['#prefix'] = "<div class ='curr_incidents_form'>";
      $result['incidents_form_render']['incidents_form'] = $incidents_data;
      $result['incidents_table_render']['#prefix'] = "<div id = 'incidents_search_results_wrapper'>";
      $result['incidents_table_render']['incidents_table'] = HzdcustomisationStorage::current_incidents('incident');
      $result['incidents_table_render']['#suffix'] = "</div>";
      $result['maintenance_table_render']['#prefix'] = "<div id = 'maintenance_search_results_wrapper'>";
      $result['maintenance_table_render']['maintenance_table'] = HzdcustomisationStorage::current_incidents(
        'maintenance');
      $result['maintenance_table_render']['#suffix'] = "</div>";
      $result['maintenance_table_render']['#cache'] = ['tags' => ['node_list']];
      $response = $result;
      return $response;
    } else {
      return ['#markup' => t('You are not the member of this group.')];
    }
  }

  function getDependentServices($service = null) {
    $data = HzdcustomisationStorage::getDependantServices($service);
    echo json_encode($data);
    exit;
  }
}
