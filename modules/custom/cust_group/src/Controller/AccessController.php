<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;

define('QUICKINFO', \Drupal::config('quickinfo.settings')->get('quickinfo_group_id'));
// define('RELEASE_MANAGEMENT', 32);

/**
 * Returns Access grants for Node edit routes.
 */
class AccessController extends ControllerBase {

  public function groupNodeEdit() {
//this is not necessary as groups module handles(have to confirm), just to add one more layer of access check
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {

      if ($node->getType() == 'quickinfo' && $node->isPublished()) {
        return AccessResult::forbidden();
      }
      
      if ($node->getType() == 'quickinfo' && !$node->isPublished()) {
          /**
           * group id has to be dynamic 
           */
          $group = \Drupal\group\Entity\Group::load(3);
          $content = $group->getMember(\Drupal::currentUser());
          if($content){
            return AccessResult::allowed();
          } else {
            return AccessResult::forbidden();  
          }
      }
      
      if ($node->getType() == 'downtimes') {
        return AccessResult::allowed();
      }
      //$checkGroupNode = \Drupal::database()->select('group_content_field_data','gcfd')
      //    ->fields('gcfd',['gid'])
      //    ->condition('gcfd.entity_id',$node->id())
      //    ->execute()->fetchField();
      //if(\Drupal::currentUser()->id() == 1){
      //  return AccessResult::allowed();
      //}
      //if($checkGroupNode || \Drupal::currentUser()->id() == 1){
      //  return \Drupal\cust_group\Controller\CustNodeController::hzdGroupAccess($checkGroupNode);
      //}
      //pr($node->id());exit;
    }
    return AccessResult::allowed();
  }

  function createMaintenanceAccess() {
    if ($group = \Drupal\group\Entity\group::load(19)) {
      if ($group->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::forbidden();
  }

  function groupTitle() {
    $group = \Drupal::routeMatch()->getParameter('arg_0');
    if (!is_object($group)) {
      $group = \Drupal\group\Entity\Group::load($group);
    }
    $request = \Drupal::request();
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', $group->label());
    }
    return 'Members of ' . $this->t($group->label());
  }

  function groupAdminAccess() {
    if ($group = \Drupal::routeMatch()->getParameter('group')) {
      if (!is_object($group)) {
        $group = \Drupal\group\Entity\Group::load($group);
      }
      $groupMember = $group->getMember(\Drupal::currentUser());
      if ($groupMember) {
        $roles = $groupMember->getRoles();
        if (!empty($roles) && (in_array($group->bundle() . '-admin', array_keys($roles)) || \Drupal::currentUser()->id() == 1 || in_array('site_administrator',\Drupal::currentUser()->getRoles()))) {
          return AccessResult::allowed();
        }
      }
      //pr($roles);exit;

      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

  function isGroupAdminAccess() {
    $user = \Drupal::currentUser();
    $uid = $user->id();
    $user_role = $user->getRoles();
    if (!in_array(SITE_ADMIN_ROLE, $user_role)) {
      $group_members_query = db_query("SELECT gcfd.* FROM group_content_field_data gcfd, group_content__group_roles gcgr WHERE gcgr.entity_id = gcfd.id AND gcgr.group_roles_target_id like '%admin%' AND gcfd.entity_id = $uid")->fetchAllKeyed();
      if (empty($group_members_query)) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::allowed();
  }

  static public function CheckQuickinfoviewAccess() {
        $group = \Drupal::routeMatch()->getParameter('group');
        if (is_object($group)) {
            $group_id = $group->id();
        }
        else {
            $group_id = $group;
        }
        $allowed_group = array(QUICKINFO, RELEASE_MANAGEMENT);
        if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
            return AccessResult::allowed();
        }
        if (!$group_id && !in_array($group_id, $allowed_group)) {
            return AccessResult::forbidden();
        }
        $group = \Drupal\group\Entity\Group::load($group_id);
        $content = $group->getMember(\Drupal::currentUser());
        if ($content) {
            return AccessResult::allowed();
        }
        return AccessResult::forbidden();
    }

    static public function CheckQuickinfonodeviewAccess() {
        $node = \Drupal::routeMatch()->getParameter('node');
        if (is_object($node) && $node->getType() == 'quickinfo') {
            $group = \Drupal::routeMatch()->getParameter('group');
            if (is_object($group)) {
                $group_id = $group->id();
            }
            else {
                $group_id = $group;
            }
            $allowed_group = array(QUICKINFO);
            if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
                return AccessResult::allowed();
            }
            if (!$group_id && !in_array($group_id, $allowed_group)) {
                return AccessResult::forbidden();
            }
            $group = \Drupal\group\Entity\Group::load($group_id);
            $content = $group->getMember(\Drupal::currentUser());
            if ($content) {
                return AccessResult::allowed();
            }
            return AccessResult::forbidden();
        }
    }
    
}
