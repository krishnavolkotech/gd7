<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;

if (!defined('QUICKINFO')) {
  define('QUICKINFO', \Drupal::config('hzd_customizations.settings')->get('quickinfo_group_id'));
}



/**
 *
 */
class QuickinfopublishedviewController extends ControllerBase {

  /**
   * TO do : Check with shiva sir .
   */
  public function quickinfo_published_view() {
    $quickinfo_group_id = \Drupal::config('hzd_customizations.settings')->get('quickinfo_group_id');
    $is_group_member = $this->CheckuserisquickinfoGroupMember();
    if ($is_group_member) {
                     $output[]['#attached']['library'] = array(
        //    'locale.libraries/translations',
        //    'locale.libraries/drupal.locale.datepicker',
            'hzd_release_management/hzd_release_management',
//            'hzd_customizations/hzd_customizations',
           // 'hzd_release_management/hzd_release_management_sort',
          //  'downtimes/downtimes',
          );

      // $group = \Drupal::routeMatch()->getParameter('group');
      //        if(is_object($group)){
      //          $group_id = $group->id();
      //        } else {
      //          $group_id = $group;
      //        }
      //      node/id/.
      $current_path = \Drupal::service('path.current')->getPath();
      $arg = explode('/', $current_path);
      // $result = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
      $nid = $arg['4'];
      $node = Node::load($nid);
      $output = array();
      if ($node) {
        $output['#title'] = $node->title;
        $output = node_view($node, $view_mode = 'full', $langcode = NULL);
      }
      return $output;
    }
    else {
      return $build = array(
        '#prefix' => '<div id="no-result">',
        '#markup' => t("You are not authorized to view this page"),
        '#suffix' => '</div>',
      );
    }

  }

  /**
   *
   */
   public function CheckuserisquickinfoGroupMember($group_id = null) {
        $group = \Drupal::routeMatch()->getParameter('group');
        if (is_object($group)) {
            $group_id = $group->id();
        }
        else {
            $group_id = $group;
        }

        if (!$group_id && $group_id != QUICKINFO) {
            return false;
        }
        if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
            return true;
        }
        $group = \Drupal\group\Entity\Group::load($group_id);
        $content = $group->getMember(\Drupal::currentUser());
        if ($content && group_request_status($content)) {
            return true;
        }
        return false;
    }
}
