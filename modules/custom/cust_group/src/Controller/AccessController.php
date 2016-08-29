<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Returns Access grants for Node edit routes.
 */
class AccessController extends ControllerBase {
  public function groupNodeEdit(){
//this is not necessary as groups module handles(have to confirm), just to add one more layer of access check
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node->getType() == 'quickinfo' && $node->isPublished()) {
      return \Drupal\Core\Access\AccessResult::forbidden();
    }
    $checkGroupNode = \Drupal::database()->select('group_content_field_data','gcfd')
        ->fields('gcfd',['gid'])
        ->condition('gcfd.entity_id',$node->id())
        ->execute()->fetchField();
    if(\Drupal::currentUser()->id() == 1){
      return AccessResult::allowed();
    }
    if($checkGroupNode || \Drupal::currentUser()->id() == 1){
      return \Drupal\cust_group\Controller\CustNodeController::hzdGroupAccess($checkGroupNode);
    }
    return AccessResult::neutral();
  }
}
