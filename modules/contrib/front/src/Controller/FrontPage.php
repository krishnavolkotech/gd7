<?php

/**
 * @file
 * Contains \Drupal\front\Controller
 */

namespace Drupal\front\Controller;
use Drupal\Core\Controller\ControllerBase;

class FrontPage extends ControllerBase {

  public function view() {
    $output = $this->display_front_page_content();
    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

  function display_front_page_content() {
    if(\Drupal::currentUser()->id()) {
      $roles = user_role_names();
      krsort($roles);
      $loggedUserRole = \Drupal::currentUser()->getRoles();
      foreach($roles as $role_id => $role_name) {
        if(in_array($role_id, $loggedUserRole)) {
          $output = \Drupal::config('front.settings')->get('front_'. $role_id .'_text');
          return $output['value'];
        }
      }
    }
    else {
      $output = \Drupal::config('front.settings')->get('front_anonymous_text');
      return $output['value'];
    }
  }
}
