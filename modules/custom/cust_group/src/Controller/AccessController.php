<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\Routing\Route;
use Drupal\group\Entity\GroupContent;

if (!defined('QUICKINFO')) {
    define('QUICKINFO', \Drupal::config('hzd_customizations.settings')->get('quickinfo_group_id'));
}
// define('RELEASE_MANAGEMENT', 32);

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
    public function groupNodeEdit() {
        // this is not necessary as groups module handles(have to confirm), just to add one more layer of access check
        $node = \Drupal::routeMatch()->getParameter('node');
        if (is_object($node)) {
            
            if ($node->getType() == 'quickinfo' && $node->isPublished()) {
                return AccessResult::forbidden();
            }
            
            if ($node->getType() == 'quickinfo' && !$node->isPublished()) {
                /**
                 * group id has to be dynamic
                 */
                $currentUser = \Drupal::currentUser();
                $group = \Drupal\group\Entity\Group::load(QUICKINFO);
                $content = $group->getMember($currentUser);
                if (array_intersect($currentUser->getRoles(), ['site_administrator', 'administrator'])) {
                    return AccessResult::allowed();
                }
                if ($content) {
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
                if (!$content) {
                    return AccessResult::forbidden();
                } else {
                    if (!$node->isPublished()) {
                        return AccessResult::allowed();
                    }
                }
            }
        }
        return AccessResult::allowed();
    }
    
    function createMaintenanceAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
        if (array_intersect(['site_administrator', 'administrator'], $user->getRoles())) {
            return AccessResult::allowed();
        }
        $loadedGroup = $route_match->getParameter('group');
        if ($group = \Drupal\group\Entity\group::load(GEPLANTE_BLOCKZEITEN)) {
            $content = $group->getMember($user);
            if ($content && $loadedGroup->id() == INCEDENT_MANAGEMENT) {
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
    
    function groupAdminAccess() {
        $user = \Drupal::currentUser();
        if ($user && array_intersect($user->getRoles(), ['admininstrator', 'site_administrator'])) {
            return AccessResult::allowed();
        }
        if ($group = \Drupal::routeMatch()->getParameter('group')) {
            if (!is_object($group)) {
                $group = \Drupal\group\Entity\Group::load($group);
            }
            $groupMember = $group->getMember(\Drupal::currentUser());
            if ($groupMember) {
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
    
}
