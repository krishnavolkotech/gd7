<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_release_management\Controller;

if (!defined('QUICKINFO')) {
  define('QUICKINFO', \Drupal::config('quickinfo.settings')->get('quickinfo_group_id'));
}
if (!defined('RELEASE_MANAGEMENT')) {
  define('RELEASE_MANAGEMENT', 32);
}


/**
 *
 */
class QuickinfopublishedviewController extends ControllerBase {

  /**
   *  TO do : Check with shiva sir 
   */
  public function quickinfo_published_view() {
      
      $is_group_member = DisplaysavedquickinfoController::CheckuserisquickinfoGroupMember();
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
      
   public function CheckuserisquickinfoGroupMember($group_id = null) {
        $group = \Drupal::routeMatch()->getParameter('group');
        if (is_object($group)) {
            $group_id = $group->id();
        }
        else {
            $group_id = $group;
        }

        $allowed_group = array(3, RELEASE_MANAGEMENT);
        if (!$group_id && !in_array($group_id, $allowed_group)) {
            return false;
        }
        if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
            return true;
        }
        $group = \Drupal\group\Entity\Group::load($group_id);
        $content = $group->getMember(\Drupal::currentUser());
        if ($content) {
            return true;
        }
        return false;
    }
}
