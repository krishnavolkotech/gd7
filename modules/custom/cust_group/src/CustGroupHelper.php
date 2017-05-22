<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\cust_group;

use Drupal\group\Entity\GroupContent;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Description of CustGroupHelper
 *
 * @author sandeep
 */
class CustGroupHelper {

  //returns the group content id from the node id.
  static function getGroupNodeFromNodeId($nodeId) {
    $groupContentIds = \Drupal::entityQuery('group_content')
            ->condition('type', '%group_node%', 'LIKE')
            ->condition('entity_id', $nodeId)
            ->execute();

    if (!empty($groupContentIds))
      return GroupContent::load(reset($groupContentIds));
    return null;
  }

  static function getGroupFromRouteMatch() {
    $routeMatch = \Drupal::routeMatch();
    $group = $routeMatch->getParameter('group');
    $node = $routeMatch->getParameter('node');
    if (!empty($node) && empty($group)) {
      $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($node->id());
      if (!empty($groupContent)) {
        $group = $groupContent->getGroup();
      }
    } elseif ($term = $routeMatch->getParameter('taxonomy_term')) {
      $storage = \Drupal::service('entity_type.manager')
                ->getStorage('taxonomy_term');
        $parents = $storage->loadParents($term->id());
//        pr($parents);exit;
      if ($routeMatch->getRouteName() == 'forum.page') {
        if(empty($parents)){
          $parents = $term;
        }else{
          $parents = reset($parents);
        }
        $group = \Drupal::service('entity_type.manager')
                ->getStorage('group')
                ->loadByProperties(['field_forum_containers' => $parents->id()]);
      } elseif ($routeMatch->getRouteName() == 'entity.taxonomy_term.canonical') {
        $group = \Drupal::service('entity_type.manager')
                ->getStorage('group')
                ->loadByProperties(['label' => reset($parents)->label()]);
      }
      $group = reset($group);
    }
    return $group;
  }

}
