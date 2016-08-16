<?php
/**
 * @file
 * Contains \Drupal\problem_management\Controller\ProblemsController
 */

namespace Drupal\problem_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\problem_management\HzdproblemmanagementHelper;
use Drupal\Core\Access\AccessResult;

/**
 * Class CurrentProblemsController
 * @package Drupal\problem_management\Controller
 */
class ProblemsController extends ControllerBase {
/*
 *callback for problems display
*/
function problems_display() {
  $current_path = \Drupal::service('path.current')->getPath();
  $get_uri = explode('/', $current_path);
  if ($get_uri['3'] == 'problems') {
    unset($_SESSION['sql_where']);
    unset($_SESSION['limit']);
  }
  $string = 'current';

  $request = \Drupal::request();
  $page = $request->get('page');
  if (!$page) {
    unset($_SESSION['problems_query']);
    unset($_SESSION['sql_where']);
    unset($_SESSION['limit']);
  }

  HzdproblemmanagementHelper::set_breabcrumbs_problems($string);
  $response = HzdproblemmanagementHelper::problems_tabs_callback_data($string);
  return $response;
 }

function access(){
  $group = \Drupal::routeMatch()->getParameter('group');
  $user = \Drupal::currentUser();
  if($user->isAnonymous()){
    return AccessResult::forbidden();
  }
  if($user->getRoles()){
    return AccessResult::allowed();
  }
}

}
