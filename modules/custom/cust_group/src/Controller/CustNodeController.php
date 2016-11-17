<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\group\Entity\GroupContentInterface;

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

  function groupContentView() {
    $parm = \Drupal::routeMatch()->getParameter('group_content');
    $node = $parm->get('entity_id')->referencedEntities()[0];
    $view_builder = \Drupal::entityManager()->getViewBuilder('node');
    return $view_builder->view($node);
  }
  
  function groupContentTitle(){
    $parm = \Drupal::routeMatch()->getParameter('group_content');
    return $parm->get('entity_id')->referencedEntities()[0]->label();
  }

  function groupMemberView() {
    $member = \Drupal::routeMatch()->getParameter('group_content');
    $user = $member->get('entity_id')->referencedEntities()[0];
    $view_builder = \Drupal::entityManager()->getViewBuilder('user');
    return $view_builder->view($user);
  }
  
  function groupMemberTitle(){
    $parm = \Drupal::routeMatch()->getParameter('group_content');
    return $parm->get('entity_id')->referencedEntities()[0]->label();
  }

  static function hzdGroupAccess() {
    if ($group = \Drupal::routeMatch()->getParameter('group')) {
      if (!is_object($group))
        $group = \Drupal\group\Entity\Group::load($group);
      if ($group->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1 || in_array('site_administrator', \Drupal::currentUser()->getRoles())) {
        return AccessResult::allowed();
      } else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }

  static function hzdCreateDowntimesAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
    if ($user) {
      $group = $route_match->getParameter('group');
      if ($group->id() == INCEDENT_MANAGEMENT && ($group->getMember($user) || array_intersect(['site_administrator', 'administrator'], $user->getRoles()))) {
        return AccessResult::allowed();
      } else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::forbidden();
  }

  static function hzdIncidentGroupAccess() {
    $uid = \Drupal::currentUser()->id();
    if ($uid == 0) {
      return AccessResult::allowed();
    }
    if ($group = \Drupal::routeMatch()->getParameter('group')) {
      if (!is_object($group))
        $group = \Drupal\group\Entity\Group::load($group);
      if ($group->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1 || in_array('site_administrator', \Drupal::currentUser()->getRoles())) {
        return AccessResult::allowed();
      } else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }

  static function hzdnodeConfirmAccess() {
    if ($group = \Drupal::routeMatch()->getParameter('group')) {
      if (!is_object($group))
        $group = \Drupal\group\Entity\Group::load($group);
      if ($group->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1 || in_array('site_administrator', \Drupal::currentUser()->getRoles())) {
        return AccessResult::allowed();
      } else {
        return AccessResult::forbidden();
      }
    } else if ($node = \Drupal::routeMatch()->getParameter('node')) {
      $group_id = \Drupal::database()->select('group_content_field_data', 'gcfd')
                      ->fields('gcfd', ['gid', 'id'])
                      ->condition('gcfd.entity_id', $node)
                      ->execute()->fetchAssoc();
      if ($group_id) {
        $group = \Drupal\group\Entity\Group::load($group_id['gid']);
        if ($group->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1 || in_array('site_administrator', \Drupal::currentUser()->getRoles())) {
          return AccessResult::allowed();
        } else {
          return AccessResult::forbidden();
        }
      } else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }

  ///added for drupal core views 
  static function hzdGroupViewsAccess() {
    if ($group = \Drupal::routeMatch()->getParameter('arg_0')) {
      if (!is_object($group))
        $group = \Drupal\group\Entity\Group::load($group);
      if ($group->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1) {
        return AccessResult::allowed();
      } else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }

  static function isGroupAdmin($group_id = null) {
    if (!$group_id) {
      return false;
    }
    if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
      return true;
    }
    $group = \Drupal\group\Entity\Group::load($group_id);
    $content = $group->getMember(\Drupal::currentUser());
    if ($content) {
      $contentId = $content->getGroupContent()->id();
      $adminquery = \Drupal::database()->select('group_content__group_roles', 'gcgr')
                      ->fields('gcgr', ['group_roles_target_id'])->condition('entity_id', $contentId)->execute()->fetchAll();
      return (bool) !empty($adminquery);
    }

    return false;
  }

  static function getNodeGroupId($node = null) {
    if (!$node) {
      return false;
    }
    $checkGroupNode = \Drupal::database()->select('group_content_field_data', 'gcfd')
                    ->fields('gcfd', ['gid', 'id'])
                    ->condition('gcfd.entity_id', $node->id())
                    ->execute()->fetchAssoc();
    if (!empty($checkGroupNode)) {
      return $checkGroupNode;
    }
    return false;
  }

  function groupNodeEdit() {
    //pr(\Drupal::routeMatch()->getParameter('group_content'));exit;
    $group_content = \Drupal::routeMatch()->getParameter('group_content');
    $group = \Drupal::routeMatch()->getParameter('group');
    $node = $group_content->get('entity_id')->referencedEntities()[0];
    $form = \Drupal::entityTypeManager()
            ->getFormObject('node', 'default')
            ->setEntity($node);
    $url = new \Drupal\Core\Url('entity.group_content.group_node__deployed_releases.canonical', ['group' => $group->id(), 'group_content' => $group_content->id()]);
    return \Drupal::formBuilder()->getForm($form, ['redirect' => $url]);
  }

  function groupMemberCleanup() {
    $groupContent = \Drupal::entityQuery('group_content');
    $orCondition = $groupContent->orConditionGroup()->condition('type', '%member%', 'LIKE')
            ->condition('type', ['group_content_type_b2ed3eb8d19c9', 'group_content_type_d4b06e2b6aad0', 'group_content_type_ecf0249297413'], 'IN');
    $groupContent = $groupContent->condition($orCondition)
            ->execute();
    //pr($groupContent);exit;

    foreach ($groupContent as $groupUser) {
      $gUser = \Drupal\group\Entity\GroupContent::load($groupUser);

      if ($gUser && $gUser->entity_id->referencedEntities()) {
        
      } elseif ($gUser) {
        $gUser->delete();
      }
    }
    echo 'completed';
    exit;
  }

  static public function ContactformTitle() {
    return t('Contact');
  }
}
