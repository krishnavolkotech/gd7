<?php

namespace Drupal\cust_filebrowser\Access;

use Drupal\filebrowser\Access\FilebrowserAccessCheck;
// use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
// use Drupal\filebrowser\Services\Common;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Entity\Entity;

/**
 * Override to set CSV filename when exported.
 */
class AltFilebrowserAccessCheck extends FilebrowserAccessCheck {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param RouteMatchInterface $route_match
   * @return AccessResult
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $result = parent::access($route_match, $account);
    $nid = $route_match->getParameter('nid');
    if (isset($nid)) {
      $groupEntity = GroupContent::load($nid);
    }
    if (isset($groupEntity)) {
      $group = $groupEntity->getGroup();
    }
    if (isset($group)) {
      if ($op = $route_match->getParameter('op')) {
        if ($permission = parent::mapActionToPermission($op)) {
          return GroupAccessResult::allowedIfHasGroupPermission($group, $account, $permission);
        }
      }
    }
    return $result;
  }
}