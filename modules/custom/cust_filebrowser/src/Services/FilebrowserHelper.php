<?php

namespace Drupal\cust_filebrowser\Services;

use Drupal\node\NodeInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\Group;
use Drupal\Core\Session\AccountInterface;

/**
 * Filebrowser Helper Class.
 */
class FilebrowserHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The FilebrowserHelper constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets the group content entity.
   *
   * @param Drupal\node\NodeInterface $node
   *   The Node used for loading the group content entity.
   *
   * @return array
   *   An array containing the group content entities.
   */
  protected function getGroupContentByEntity(NodeInterface $node) {
    return GroupContent::loadByEntity($node);
  }

  /**
   * Returns the Group object associated to the node.
   *
   * @param int $nid
   *   The Node ID.
   *
   * @return Drupal\group\Entity\Group|false
   *   The Group object or false, if the node does not belong to a group.
   */
  public function getGroupFromNodeId(int $nid) {

    $storage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $storage->load($nid);

    if (isset($node)) {
      $groupContentEntity = $this->getGroupContentByEntity($node);
    }
    // Load group, if entity belongs to a group.
    if (count($groupContentEntity) > 0) {
      /** @var \Drupal\group\Entity\Group $group */
      $group = reset($groupContentEntity)->getGroup();
    }

    if (isset($group)) {
      return $group;
    }
    else {
      return FALSE;
    }
  }

  /**
   * The group permission checker.
   *
   * @param Drupal\group\Entity\Group $group
   *   The Group object.
   * @param Drupal\Core\Session\AccountInterface $account
   *   Current User Account.
   * @param string $permission
   *   Permission for action.
   *
   * @return Drupal\Core\Routing\Access\AccessInterface
   *   The AccessResult.
   */
  public function checkGroupPermission(Group $group, AccountInterface $account, string $permission) {
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, $permission);
  }

}
