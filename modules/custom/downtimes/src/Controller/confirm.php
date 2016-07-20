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
 * callback for the resolve form confirmation
 */

function confirm() {
  $form['nodes'] = array('#prefix' => '<ul>', '#suffix' => '</ul>', '#tree' => TRUE);
  $form['operation'] = array('#type' => 'hidden', '#value' => $edit);
  if (isset($_SESSION['Group_name'])) {
    $path = 'node/' . $_SESSION['Group_id'] . '/' . $_SESSION['form_values']['type'];
  }
  else {
    $path = 'downtimes';
  }
  return confirm_form($form, t('Are you sure you want to confirm to resolve these items?'), $path, t('This action cannot be undone.'), t('Submit'), t('Cancel'));

  $path = isset($_SESSION['Group_name']) ? 'node/' . $_SESSION['Group_id'] . '/' . 'downtimes' : 'downtimes';
  return confirm_form($form, t('Are you sure you want to confirm to resolve these items?'), $path, t('This action cannot be undone.'), t('Submit'), t('Cancel'));
}

}
