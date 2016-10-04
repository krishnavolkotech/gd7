<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;

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
    return $view_builder->view($user);
  }

  static function hzdGroupAccess(){
    if($group = \Drupal::routeMatch()->getParameter('group')){
			if(!is_object($group))
				$group = \Drupal\group\Entity\Group::load($group);
      if($group->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1 || in_array('site_administrator',\Drupal::currentUser()->getRoles())){
				return AccessResult::allowed();
      }else{
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }
	
	
	///added for drupal core views 
	static function hzdGroupViewsAccess(){
    if($group = \Drupal::routeMatch()->getParameter('arg_0')){
			if(!is_object($group))
				$group = \Drupal\group\Entity\Group::load($group);
      if($group->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1){
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
		if(in_array('site_administrator',\Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1){
			return true;
		}
    $group = \Drupal\group\Entity\Group::load($group_id);
      $content = $group->getMember(\Drupal::currentUser());
      if($content){
          $contentId = $content->getGroupContent()->id();
          $adminquery = \Drupal::database()->select('group_content__group_roles','gcgr')
              ->fields('gcgr',['group_roles_target_id'])->condition('entity_id',$contentId)->execute()->fetchAll();
          return (bool)!empty($adminquery);
      }
			
    return false;
  }
	
	
	
	static function getNodeGroupId($node = null){
		if(!$node){
			return false;
		}
		$checkGroupNode = \Drupal::database()->select('group_content_field_data','gcfd')
          ->fields('gcfd',['gid','id'])
          ->condition('gcfd.entity_id',$node->id())
          ->execute()->fetchAssoc();
		if(!empty($checkGroupNode)){
			return $checkGroupNode;
		}
		return false;
	}
	
	function groupNodeEdit(){
		//pr(\Drupal::routeMatch()->getParameter('group_content'));exit;
		$group_content = \Drupal::routeMatch()->getParameter('group_content');
		$group = \Drupal::routeMatch()->getParameter('group');
		$node = $group_content->get('entity_id')->referencedEntities()[0];
		$form = \Drupal::entityTypeManager()
			->getFormObject('node', 'default')
			->setEntity($node);
		$url = new \Drupal\Core\Url('entity.group_content.group_node__deployed_releases.canonical',['group'=>$group->id(),'group_content'=>$group_content->id()]);
		return \Drupal::formBuilder()->getForm($form,['redirect'=>$url]);
	}
	
	function groupMemberCleanup(){
    $groupContent = \Drupal::entityQuery('group_content')
        ->condition('type','%member%','LIKE')
        ->execute();
        //pr($groupContent);exit;
    
    foreach($groupContent as $groupUser){
      $gUser = \Drupal\group\Entity\GroupContent::load($groupUser);
      
        if($gUser && $gUser->entity_id->referencedEntities()){
            
        }elseif($gUser){
          $gUser->delete();
        }
    }
  }
	
	
}
