<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\Route;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\Group;

if (!defined('QUICKINFO')) {
    define('QUICKINFO', \Drupal::config('hzd_customizations.settings')->get('quickinfo_group_id'));
}


/**
 * Returns Access grants for Node edit routes.
 */
class AccessController extends ControllerBase
{
    
    /**
     * Checks access for a specific request.
     *
     * @param \Drupal\Core\Session\AccountInterface $account
     *   Run access checks for this account.
     */
    public function groupNodeEdit(Route $route, RouteMatch $route_match, AccountInterface $user) {
        // this is not necessary as groups module handles(have to confirm), just to add one more layer of access check
        $node = $route_match->getParameter('node');
        if (is_object($node)) {
            
            if ($node->getType() == 'quickinfo' && $node->isPublished()) {
                return AccessResult::forbidden();
            }
            
            if ($node->getType() == 'quickinfo' && !$node->isPublished()) {
                /**
                 * group id has to be dynamic
                 */
                
                $group = \Drupal\group\Entity\Group::load(QUICKINFO);
                $content = $group->getMember($user);
                if (array_intersect($user->getRoles(), ['site_administrator', 'administrator'])) {
                    return AccessResult::allowed();
                }
                if ($content && $content->getGroupContent()->get('request_status')->value == 1) {
                    return AccessResult::allowed();
                } else {
                    return AccessResult::forbidden();
                }
            }
            
            if ($node->getType() == 'downtimes') {
                
                if(array_intersect($user->getRoles(), ['site_administrator','administrator'])){
                    return AccessResult::allowed();
                }
                $downtime_type = \Drupal::database()->select('downtimes','d')
                    ->fields('d',['scheduled_p'])
                    ->condition('downtime_id',$node->id())
                    ->execute()
                    ->fetchField();
                $maintenance_group = \Drupal\group\Entity\Group::load(GEPLANTE_BLOCKZEITEN);
                $groupMember = $maintenance_group->getMember($user);
                $incidentManagement = \Drupal\group\Entity\Group::load(INCIDENT_MANAGEMENT);
                $incidentManagementGroupMember = $incidentManagement->getMember($user);
                if($downtime_type == 1){
                    if (($groupMember && $groupMember->getGroupContent()
                            ->get('request_status')->value == 1 && $incidentManagementGroupMember) || array_intersect($user->getRoles(), [
                        'site_administrator',
                        'administrator'
                        ])
                    ) {
                        return AccessResult::allowed();
                    }
                }else{
                    $states = \Drupal::database()->select('downtimes','d')
                    ->fields('d',['state_id'])
                    ->condition('downtime_id',$node->id())
                    ->execute()
                    ->fetchField();
                    $states = explode(',', $states);
                    $show_resolve = \Drupal\hzd_customizations\HzdcustomisationStorage::resolve_link_display($states, $node->getOwnerId());
                    if($show_resolve){
                        return AccessResult::allowed();
                    }

                }

                return AccessResult::forbidden();
            }
            if ($node->getType() == 'im_upload_page') {
                if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
                    return AccessResult::allowed();
                }
                $group = \Drupal\group\Entity\Group::load(INCIDENT_MANAGEMENT);
                $groupMember = $group->getMember(\Drupal::currentUser());
                if ($groupMember && $groupMember->getGroupContent()->get('request_status')->value == 1) {
                    $roles = $groupMember->getRoles();
                    if (!empty($roles) && (in_array($group->bundle() . '-admin', array_keys($roles)))) {
                        return AccessResult::allowed();
                    }
                    return AccessResult::forbidden();
                }
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

  public function nodeRevisionsAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!empty($node)) {
      if (!is_object($node)) {
        $type = node_get_entity_property_fast([$node], 'type')[$node];
        $status = node_get_entity_property_fast([$node], 'status')[$node];
      } else {
        $type = $node->getType();
        $status = $node->isPublished() ?: 0;
      }
    }
    if (!empty($type) && !empty($status)) {
      if ($type == 'quickinfo' && $status == 1) {
        return AccessResult::forbidden();
      }
      if ($type == 'quickinfo' && $status == 0) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::allowed();
  }

    public function groupAdministratorValidation(Route $route, RouteMatch $route_match, AccountInterface $user) {
        // this is not necessary as groups module handles(have to confirm), just to add one more layer of access check
       // $group_content = $route_match->getRawParameter('group_content');

        $user = \Drupal::currentUser();
//        if ($user && array_intersect($user->getRoles(), ['admininstrator', 'site_administrator'])) {
//            return AccessResult::allowed();
//        }
        if ($group = $route_match->getRawParameter('group')) {
            if (!is_object($group)) {
                $group = Group::load($group);
            }
            $groupMembersCount = count($group->getMembers($group->bundle() . '-admin'));
            if($groupMembersCount < 2) {
                drupal_set_message(t('You cannot remove admin for this group. There should be at least one admin in the group.'), 'error');
                return AccessResult::forbidden();
            }
        }
        //return AccessResult::neutral();
        return AccessResult::allowed();
    }

    /**
     * By default all user can access all node view page
     * quickinfo group member of rz-schnellinfos and administrator can only access the quickinfo node view pages
     * @param Route $route
     * @param RouteMatch $route_match
     * @param AccountInterface $user
     * @return type AccountInterface object returns allowed or forbidden
     */
    function downtimeAcces(Route $route, RouteMatch $route_match, AccountInterface $user) {
        if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
            return AccessResult::allowed();
        }
//    if ($route_match->getParameter('node')->getType() == 'downtimes') {
//      return AccessResult::allowed();
//    }
        $node = $route_match->getParameter('node');
        if ($node->getType() == 'quickinfo') {
            $node = $route_match->getParameter('node');
            $groupQuickInfoContent = \Drupal::entityQuery('group_content')
                ->condition('type', 'moderate-group_node-quickinfo')
                ->condition('entity_id', $node->id())
                ->execute();
            if ($groupQuickInfoContent) {
                $groupQuickInfoContentEntity = GroupContent::load(reset($groupQuickInfoContent));
                $group = $groupQuickInfoContentEntity->getGroup();
                $content = $group->getMember($user);
                $releaseGroup = Group::load(RELEASE_MANAGEMENT);
                $releaseMember = $releaseGroup->getMember($user);
//                pr($releaseMember->id());exit;
                if (!$content && !$releaseMember) {
                    return AccessResult::forbidden();
                } else {
                    if (($content !== FALSE) && !$node->isPublished() && $content->getGroupContent()->get('request_status')->value == 1) {
                        return AccessResult::allowed();
                    }
                }
            }
        }elseif($node->getType() == 'downtimes'){
          return AccessResult::allowed();
        }elseif($node->getType() == 'im_upload_page'){
          if($user->isAnonymous()){
            return AccessResult::forbidden();
          }
        }
//        echo 12312;exit;
        return AccessResult::allowed();
    }
    
    
    function userEditAcces(Route $route, RouteMatch $route_match, AccountInterface $user) {
    if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
      return AccessResult::allowed();
    }

    if ($route_match->getParameter('user')->id() == \Drupal::currentUser()->id()) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  function createMaintenanceAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
        if (array_intersect(['site_administrator', 'administrator'], $user->getRoles())) {
            return AccessResult::allowed();
        }
        $loadedGroup = $route_match->getParameter('group');
        if ($group = \Drupal\group\Entity\group::load(GEPLANTE_BLOCKZEITEN)) {
            $content = $group->getMember($user);
            $incidentGroupMember = \Drupal\group\Entity\group::load(INCIDENT_MANAGEMENT)->getMember($user);
            if ($content && $loadedGroup->id() == INCIDENT_MANAGEMENT && $content->getGroupContent()->get('request_status')->value == 1 && $incidentGroupMember && $incidentGroupMember->getGroupContent()->get('request_status')->value == 1) {
                return AccessResult::allowed();
            } else {
                return AccessResult::forbidden();
            }
        }
        return AccessResult::forbidden();
    }
    
    function groupTitle() {
        $group = \Drupal::routeMatch()->getParameter('group');
        if (!is_object($group)) {
            $group = \Drupal\group\Entity\Group::load($group);
        }
        $request = \Drupal::request();
        if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
            $route->setDefault('_title', $group->label());
        }
        return 'Members of ' . $this->t($group->label());
    }
    
    static function groupAdminAccess() {
        $user = \Drupal::currentUser();
        if ($user && array_intersect($user->getRoles(), ['admininstrator', 'site_administrator'])) {
            return AccessResult::allowed();
        }
        if ($group = \Drupal::routeMatch()->getParameter('group')) {
            if (!is_object($group)) {
                $group = \Drupal\group\Entity\Group::load($group);
            }
            $groupMember = $group->getMember(\Drupal::currentUser());
            if ($groupMember && $groupMember->getGroupContent()->get('request_status')->value == 1) {
                $roles = $groupMember->getRoles();
                if (!empty($roles) && (in_array($group->bundle() . '-admin', array_keys($roles)))) {
                    return AccessResult::allowed();
                }
            }
            //pr($roles);exit;
            
            return AccessResult::forbidden();
        }
        return AccessResult::neutral();
    }

  /**
   * @param $fid
   * @param $nid
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  function userFileDeleteAccess($fid, $nid) {
    $currentUser = \Drupal::currentUser();

    if($currentUser->isAnonymous()) {
      return AccessResult::forbidden();
    }

    if ($currentUser && array_intersect($currentUser->getRoles(), ['admininstrator', 'site_administrator'])) {
      return AccessResult::allowed();
    }

    //Checking permission for Group Admin
    $incidentManagement = \Drupal\group\Entity\Group::load(INCIDENT_MANAGEMENT);
    $incidentManagementGroupMember = $incidentManagement->getMember($currentUser);
    if ($incidentManagementGroupMember && $incidentManagementGroupMember->getGroupContent()
        ->get('request_status')->value == 1) {
      $roles = $incidentManagementGroupMember->getRoles();
      if (in_array($incidentManagement->getGroupType()->id() . '-admin', array_keys($roles))) {
        return AccessResult::allowed();
      }
    }

    //Checking Permission for owner
    $file = \Drupal\file\Entity\File::load($fid);
    if ($file) {
      if($file->getOwnerId() == $currentUser->id()) {
        return AccessResult::allowed();
      }
    }

    //Checking States
    $userstateid = \Drupal::database()->select('cust_profile', 'cp')
      ->fields('cp', array('state_id'))
      ->condition('cp.uid', $currentUser->id())
      ->execute()->fetchField();

    $nodeData = \Drupal\node\Entity\Node::load($nid);
    $field_state = $nodeData->get('field_state')->value;
    if($field_state == $userstateid) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
    
    function userCreateAccess() {
        $user = \Drupal::currentUser();
        if ($user && array_intersect($user->getRoles(), ['admininstrator', 'site_administrator'])) {
            return AccessResult::allowed();
        }
        return AccessResult::forbidden();
    }
    
    function isGroupAdminAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
      if (array_intersect(['site_administrator', 'administrator'], $user->getRoles())) {
        return AccessResult::allowed();
      }
//        $user = \Drupal::currentUser();
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
    
    public function groupContentAccess(Route $route, RouteMatch $route_match, AccountInterface $user){
      $groupContent = $route_match->getParameter('group_content');
      $group = $route_match->getParameter('group');
      $userEntity = User::load($user->id());
      if($groupContent->getEntity()->getEntityTypeId() == 'node'){
        return AccessResult::forbiddenIf(!$userEntity->hasRole('administrator'));
      }
      // @var $group = Group
      return AccessResult::allowedIf($group->hasPermission('administer members',$userEntity));
    }
    
    public function deployedReleasesAccess(Route $route, RouteMatch $route_match, AccountInterface $user){
      $group = $route_match->getParameter('group');
      $member = $group->getMember($user);
      $releaseManagementGroup = Group::load(RELEASE_MANAGEMENT);
      $releaseManagementMember = $releaseManagementGroup->getMember($user);
      if($member && $releaseManagementMember){
        return AccessResult::allowed();
      }
      return AccessResult::neutral();
    }
    
    function pendingMembersAccess(Route $route, RouteMatch $route_match, AccountInterface $user){
//        exit;
        $group = $route_match->getParameter('group');
        $groupTypeId = $group->getGroupType()->id();
        if(in_array($groupTypeId, ['moderate','moderate_private'])){
            return AccessResult::allowed();
        }
        return AccessResult::forbidden();
    }
}
