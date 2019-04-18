<?php

namespace Drupal\cust_group\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\front\Controller\FrontPage;

/**
 * This Class is used to override front page controller from front contrib module.
 *
 *
 */
class FrontPageController extends ControllerBase {

  public static function frontPageOverride() {
    $currentUser = Drupal::currentUser();

    //Had to create object since $this used in contrib function and was giving php error    
    $front_page_obj = new FrontPage();

    // Return only anonymous front page content and nothing for authenticated user.
    if ($currentUser->isAnonymous()) {
      return $front_page_obj->view();
    } else {
      $view = Drupal\views\Views::getView('problems_front_page');
      $view->setDisplay('block_1');
      $view->execute();
      $resultall = [];
      if (empty($view->build_info['fail']) and empty($view->build_info['denied'])) {
        $result = $view->result;
        $resultall = [];
        foreach ($result as $nid) {
          $resultall[] = $nid->nid;
        }
      }
      $tempstore = \Drupal::service('user.private_tempstore')->get('problem_management');
      $tempstore->set('problem_paginations', implode(',', $resultall));
      return [
        '#type' => 'markup',
        '#markup' => '',
        '#title' => '',
      ];
    }
  }

}
