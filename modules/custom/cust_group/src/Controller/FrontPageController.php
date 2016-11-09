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
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => '',
        '#title' => '',
      ];
    }
    
  }

}
