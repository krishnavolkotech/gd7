<?php

namespace Drupal\downtimes\Plugin\search_api\processor;

use Drupal\comment\CommentInterface;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds content access checks for nodes and comments.
 *
 * @SearchApiProcessor(
 *   id = "group_access",
 *   label = @Translation("Group access"),
 *   description = @Translation("Adds content access checks for nodes and comments."),
 *   stages = {
 *     "preprocess_query" = -30,
 *   },
 * )
 */
class GroupAccess extends ProcessorPluginBase {
  
  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    $user = \Drupal::currentUser();
    if(array_intersect($user->getRoles(),['administrator','site_administrator'])){
      return ;
    }
    $groupMembershipService = \Drupal::service('group.membership_loader');
    $userGroups = [];
    $groupMemberships = $groupMembershipService->loadByUser($user);
    foreach ($groupMemberships as $groupMembership) {
      $group = $groupMembership->getGroup();
      $userGroups[] = $group->id();
    }
    $text = $query->getKeys();
    $val = isset($text[0])?$text[0]:'';
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('gid', $userGroups, 'IN')
      ->addCondition('field_group_body', db_like($val) . '%', 'LIKE')
      ->addCondition('label_1', db_like($val) . '%', 'LIKE')
      ->addCondition('field_einfuehrung', db_like($val) . '%', 'LIKE')
      ->addCondition('field_description', db_like($val) . '%', 'LIKE');
    $query->addConditionGroup($conditions);
//    dump($query->getKeys());
//    pr($userGroups);exit;
  }
  
}
