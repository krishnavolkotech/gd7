<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\cust_group;

<<<<<<< HEAD
use Drupal\group\Entity\GroupContent;
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

}
