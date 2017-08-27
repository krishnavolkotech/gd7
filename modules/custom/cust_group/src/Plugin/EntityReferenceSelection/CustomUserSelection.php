<?php

namespace Drupal\cust_group\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\group\Entity\Group;

//use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "group:user",
 *   label = @Translation("Custom User selection"),
 *   entity_types = {"user"},
 *   group = "group",
 *   weight = 1
 * )
 */
class CustomUserSelection extends DefaultSelection {
  
  
  /**
   * {@inheritdoc}
   */
  /*protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $db = \Drupal::database();
    $target_type = $this->configuration['target_type'];
    $query = $this->entityManager->getStorage($target_type)->getQuery();
    $query->condition('mail',$match,$match_operator);
    $query->condition('status',1,'=');
    $que = $db->select('cust_profile', 'cp')
      ->fields('cp', ['uid']);
    $or = $que->orConditionGroup()
      ->condition('firstname', '%' . $match . '%', 'LIKE')
      ->condition('lastname', '%' . $match . '%', 'LIKE');
    $que = $que->condition($or)->execute()->fetchCol();
    if (!empty($que)) {
      $orp = $query->orConditionGroup()->condition('uid', (array) $que, 'IN');
      $query->condition($orp);
    }
    $query->condition('uid', [0], 'NOT IN');
    return $query;
  }*/
  
  
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 10) {
    $userQuery = \Drupal::database()->select('users_field_data', 'u')
      ->fields('u', ['uid']);
    $userQuery->join('cust_profile', 'cp', 'cp.uid = u.uid');
    $userQuery->condition($userQuery->orConditionGroup()
      ->condition('firstname', '%' . $match . '%', 'LIKE')
      ->condition('lastname', '%' . $match . '%', 'LIKE')
      ->condition('mail', '%' . $match . '%', 'LIKE')
      ->condition('name', '%' . $match . '%', 'LIKE'));
    $userQuery->addJoin('LEFT', 'inactive_users', 'iu', 'u.uid = iu.uid');
    $userQuery->condition('status', 1);
    $userQuery->range(0,$limit);
    $userQuery->isNull('iu.uid');
    $result = $userQuery->execute()->fetchCol();
    $target_type = $this->configuration['target_type'];
    $entities = $this->entityManager->getStorage($target_type)
      ->loadMultiple($result);
    $users = [];
    $group = $this->configuration['handler_settings']['hzd_group'];
    foreach ($entities as $entity) {
      if ($group->getMember($entity)) {
        continue;
      }
      $bundle = $entity->bundle();
      $users[$bundle][$entity->id()] = $entity->getDisplayName() . '(' . $entity->getEmail() . ')';
    }
    return $users;
  }
  
  
}
