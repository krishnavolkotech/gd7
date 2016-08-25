<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Returns responses for Node routes.
 */
class CustNodeController extends ControllerBase {

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type entity for the node.
   *
   * @return array
   *   A node submission form.
   */
  public function add($group_id, NodeTypeInterface $node_type) {
    $maintainance_id = \Drupal::config('downtimes.settings')->get('maintenance_group_id');
    $quickinfo_id = \Drupal::config('quickinfo.settings')->get('quickinfo_group_id');

    $node = $this->entityManager()->getStorage('node')->create(array(
      'type' => $node_type->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }
  
  
  function groupContentView(){
    $parm = \Drupal::routeMatch()->getParameter('group_content');
    $node = \Drupal\node\Entity\Node::load($parm->get('entity_id')->referencedEntities()[0]->id());
    $view_builder = \Drupal::entityManager()->getViewBuilder('node');
    return $view_builder->view($node);
  }

  function groupMemberView(){
    $member = \Drupal::routeMatch()->getParameter('group_content');
    $user = \Drupal\user\Entity\User::load($member->get('entity_id')->referencedEntities()[0]->id());
    $view_builder = \Drupal::entityManager()->getViewBuilder('user');
    return $view_builder->view($user);;
  }

  function hzdGroupAccess(){
    if($group = \Drupal::routeMatch()->getParameter('group')){
      if($group->getMember(\Drupal::currentUser())){
	return AccessResult::allowed();
      }else{
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }
  
  static function isGroupAdmin($group_id = null){
    if(!$group_id){
      return false;
    }
    $group = \Drupal\group\Entity\Group::load($group_id);
    $contentId = $group->getMember(\Drupal::currentUser())->getGroupContent()->id();
    $adminquery = \Drupal::database()->select('group_content__group_roles','gcgr')
      ->fields('gcgr',['group_roles_target_id'])->condition('entity_id',$contentId)->execute()->fetchAll();
    return (bool)!empty($adminquery);
  }
}
