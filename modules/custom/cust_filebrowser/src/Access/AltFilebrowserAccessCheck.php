<?php

namespace Drupal\cust_filebrowser\Access;

use Drupal\filebrowser\Access\FilebrowserAccessCheck;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\node\NodeInterface;
use Drupal\cust_filebrowser\Services\FilebrowserHelper;

/**
 * Overrides Filebrowser Access Check for actions.
 */
class AltFilebrowserAccessCheck extends FilebrowserAccessCheck {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Filebrowser Helper Service.
   *
   * @var Drupal\cust_filebrowser\Services\FilebrowserHelper
   */
  protected $filebrowserHelper;

  /**
   * Constructs a new AltFilebrowserAccessCheck.
   *
   * @param Drupal\cust_filebrowser\Services\FilebrowserHelper $filebrowserHelper
   *   The Helper Service.
   */
  public function __construct(FilebrowserHelper $filebrowserHelper) {
    $this->filebrowserHelper = $filebrowserHelper;
  }

  /**
   * A custom access check.
   *
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return Drupal\Core\Routing\Access\AccessInterface
   *   Returns the AccessResultInterface.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $result = parent::access($route_match, $account);
    // Load group from node id.
    $nid = $route_match->getParameter('nid');
    $group = $this->filebrowserHelper->getGroupFromNodeId($nid);
    if (isset($group)) {
      if ($op = $route_match->getParameter('op')) {
        if ($permission = parent::mapActionToPermission($op)) {
          return $this->filebrowserHelper->checkGroupPermission($group, $account, $permission);
        }
      }
    }
    return $result;
  }

}
