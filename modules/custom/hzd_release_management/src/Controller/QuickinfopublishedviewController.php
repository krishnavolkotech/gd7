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
      $is_group_admin = CustNodeController::isGroupAdmin();
      $is_group_member = $this::CheckisGroupMember();
      if ($is_group_admin || $is_group_member) {
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
       } else {
          return $build = array(
              '#prefix' => '<div id="no-result">',
              '#markup' => t("You are not authorized to view this page"),
              '#suffix' => '</div>',
              );
      }
      
    }
      
   public function CheckisGroupMember($group_id = null){
    $group = \Drupal::routeMatch()->getParameter('group');
          if (is_object($group)) {
              $group_id = $group->id();
          } else {
              $group_id = $group;
          }
    if(!$group_id){
      return false;
    }
    $group = \Drupal\group\Entity\Group::load($group_id);
      $content = $group->getMember(\Drupal::currentUser());
      if($content){
        return true;
      }
    return false;
  }
}
