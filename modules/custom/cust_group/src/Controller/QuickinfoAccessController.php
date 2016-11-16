<?php

namespace Drupal\cust_group\Controller;

/**
 * @file
 * Contains \Drupal\cust_group\src\Controller\QuickinfoAccessController.
 */

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\Routing\Route;



if (!defined('QUICKINFO')) {
  define('QUICKINFO', \Drupal::config('hzd_customizations.settings')->get('quickinfo_group_id'));
}
if (!defined('RELEASE_MANAGEMENT')) {
  define('RELEASE_MANAGEMENT', 32);
}

/**
 * Returns Access grants for Node edit routes.
 */
class QuickinfoAccessController {

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */

    static public function CheckQuickinfoviewAccess(RouteMatch $route_match, AccountInterface $account) {
        $group = $route_match->getParameter('group');
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
        if (!$group_id || !in_array($group_id, $allowed_group)) {
            return AccessResult::forbidden();
        }
        
        $group = \Drupal\group\Entity\Group::load($group_id);
        $content = $group->getMember(\Drupal::currentUser());
        if ($content) {
            return AccessResult::allowed();
        }
        
        return AccessResult::forbidden();
    }

  static public function CheckQuickinfoviewonlyAccess(RouteMatch $route_match, AccountInterface $account) {
        $group = $route_match->getParameter('group');
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
        if (!$group_id || !in_array($group_id, $allowed_group)) {
            return AccessResult::forbidden();
        }
        
        $group = \Drupal\group\Entity\Group::load($group_id);
        $content = $group->getMember(\Drupal::currentUser());
        if ($content) {
            return AccessResult::allowed();
        }
        
        return AccessResult::forbidden();
    }
    
    
    
    static public function CheckQuickinfonodeviewAccess(RouteMatch $route_match, AccountInterface $account) {
        $node = $route_match->getParameter('node');
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
            if (!$group_id || !in_array($group_id, $allowed_group)) {
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

    static public function CheckQuickinfonodecreateAccess(RouteMatch $route_match, AccountInterface $account) {
        $group = $route_match->getParameter('group');
        if (is_object($group)) {
            $group_id = $group->id();
        }
        else {
            $group_id = $group;
        }
        // to do group id dynamic
        $allowed_group = array(QUICKINFO);
        if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
            return AccessResult::allowed();
        }

        if (!$group_id || !in_array($group_id, $allowed_group)) {
           return AccessResult::forbidden();
        }
      
        $group = \Drupal\group\Entity\Group::load($group_id);
        $content = $group->getMember(\Drupal::currentUser());
        if ($content) {
            return AccessResult::allowed();
        } else {
            return AccessResult::forbidden();
        }
        return AccessResult::forbidden();
    }
    
     static public function CheckQuickinfonodedeleteAccess(RouteMatch $route_match, AccountInterface $account) {
      // this is not necessary as groups module handles(have to confirm), just to add one more layer of access check
        $node = $route_match->getParameter('node');
        if (is_object($node)) {
            if ($node->getType() == 'quickinfo' && $node->isPublished()) {
                return AccessResult::forbidden();
            }
        }
        return AccessResult::neutral();
     }
}
