<?php

namespace Drupal\cust_group\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
//use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;


class CustomUserSelection extends DefaultSelection {
  
  
  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $db = \Drupal::database();
    $query = parent::buildEntityQuery($match, $match_operator);
    
    $que = $db->select('cust_profile', 'cp')
      ->fields('cp', ['uid']);
    $or = $que->orConditionGroup()
      ->condition('firstname', '%' . $match . '%', 'LIKE')
      ->condition('lastname', '%' . $match . '%', 'LIKE');
    $que = $que->condition($or)->execute()->fetchCol();
    $orp = $query->orConditionGroup()->condition('uid', (array) $que, 'IN');
    $query->condition($orp);
    return $query;
  }
  
  
}
