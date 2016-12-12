<?php

namespace Drupal\problem_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\problem_management\HzdproblemmanagementHelper;
use Drupal\Core\Access\AccessResult;

/**
 * Class CurrentProblemsController.
 *
 * @package Drupal\problem_management\Controller
 */
class ProblemsController extends ControllerBase {

 /**
 * Return the problems listing view
 *
 * @return renderable array 
 *   Filters form and table renders in a page
 *   No records found message displayed
 */
  public function problems_display() {
    $string = 'current';
    $response = HzdproblemmanagementHelper::problems_tabs_callback_data($string);
    return $response;
  }

  /**
   *
   */
  public function access() {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    }
    else {
      $group_id = $group;
    }

    $user = \Drupal::currentUser();
    if ($user->isAnonymous()) {
      return AccessResult::forbidden();
    }
    if ($user->getRoles()) {
      return AccessResult::allowed();
    }
  }

}
