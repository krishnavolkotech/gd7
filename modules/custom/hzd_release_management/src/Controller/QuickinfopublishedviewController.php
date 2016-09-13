<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class QuickinfopublishedviewController extends ControllerBase {

  /**
   *  TO do : Check with shiva sir 
   */
  public function quickinfo_published_view() {
        $group = \Drupal::routeMatch()->getParameter('group');
        if(is_object($group)){
          $group_id = $group->id();
        } else {
          $group_id = $group;
        } 
        
      $node = \Drupal\node\Entity\Node::load($group_id);
      $output['#title'] = $node->title;
      $output = node_view($node, $view_mode = 'full', $langcode = NULL);
      return $output;
    }
}
