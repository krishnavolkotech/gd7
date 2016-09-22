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
//        $group = \Drupal::routeMatch()->getParameter('group');
//        if(is_object($group)){
//          $group_id = $group->id();
//        } else {
//          $group_id = $group;
//        } 
//      node/id/
      $current_path = \Drupal::service('path.current')->getPath();
      $arg = explode('/', $current_path);
//      $result = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
      $nid = $arg['4'];
      $node = \Drupal\node\Entity\Node::load($nid);
      $output = array();
      if ($node) {
        $output['#title'] = $node->title;
        $output = node_view($node, $view_mode = 'full', $langcode = NULL);          
      }
      return $output;
    }
}
