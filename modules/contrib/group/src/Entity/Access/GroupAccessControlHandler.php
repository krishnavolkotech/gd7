<?php

namespace Drupal\group\Entity\Access;

use Drupal\group\Access\GroupAccessResult;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Group entity.
 *
 * @see \Drupal\group\Entity\Group.
 */
class GroupAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Fetch information from the group object if possible.
    $status = $entity->isPublished();
    $uid = $entity->getOwnerId();

    switch ($operation) {
      case 'view':
        if (!$status) {
          $access_result = GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view any unpublished group');
          if (!$access_result->isAllowed() && $account->isAuthenticated() && $account->id() == $uid) {
            $access_result = GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view own unpublished group');
          }
        }
        else {
          $access_result = GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view group');
        }
        return $access_result;

      case 'update':
        return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'edit group');

      case 'delete':
        return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'delete group');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = ['bypass group access', 'create ' . $entity_bundle . ' group'];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
