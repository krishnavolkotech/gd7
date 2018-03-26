<?php

namespace Drupal\hzd_notifications;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\hzd_notifications\Resolver\ChainServiceResolverInterface;
use Drupal\cust_group\CustGroupHelper;

class NotificationManager implements NotificationManagerInterface {

  public static $service_bundles = [
    'deployed_releases',
    'release',
    'downtimes',
    'problem',
    'early_warnings',
  ];

  public static $groupBundles = [
    'event',
    'forum',
    'page',
    'faqs'
  ];

  public static $special_bundles = [
    'planning_files',
  ];

  public static $quickinfo = 'quickinfo';

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The database connection to use.
   *
   * @var \Drupal\hzd_notifications\Resolver\ChainServiceResolverInterface
   */
  protected $chainServiceResolver;

  /**
   * Constructs a NotificationScheduler object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(Connection $connection, ChainServiceResolverInterface $chainServiceResolver) {
    $this->connection = $connection;
    $this->chainServiceResolver = $chainServiceResolver;
  }

  /**
   * @inheritdoc
   */
  public function getServicesForEntity(EntityInterface $entity) {
    $services = $this->chainServiceResolver->resolve($entity);

    return $services;
  }

  /**
   * @inheritdoc
   */
  public function getSubscribedUsers(EntityInterface $entity) {
    
    // @Todo check if the services can be empty.
    $user_data = [];

    // Bundle categories are divided here just because of source to get user data,
    // no other special logic.

    $bundle = $entity->bundle();

    if($entity->getEntityTypeId() == 'group'){
      return $this->getUsersForGroup($entity);
    }
    elseif($entity->getEntityTypeId() == 'group_content' && in_array($entity->getEntity()->bundle(), self::$groupBundles)){
      return $this->getUsersForGroup($entity->getGroup());
    }
    // elseif(in_array($bundle, $groupBundles)){
    //   $groupContent = CustGroupHelper::getGroupNodeFromNodeId($entity->id());

    //   return $this->getUsersForGroup($groupContent->getGroup());
    // }
    elseif (in_array($bundle, self::$service_bundles)) {
      $services = $this->getServicesForEntity($entity);
      $service_ids = EntityHelper::extractIds($services);
      return $this->hzd_get_immediate_notification_user_mails($service_ids, $entity->bundle());
    }
    elseif (in_array($bundle, self::$special_bundles)) {
      return $this->hzd_get_immediate_pf_notification_user_mails();
    }elseif($bundle == self::$quickinfo){
      $services = $this->getServicesForEntity($entity);
      return hzd_get_immediate_qi_notification_user_mails($services);
    }
    return [];
  }


  public function getUsersForGroup($group){
    $uids = $this->connection->select('group_notifications', 'gnudi')
      ->fields('gnudi', ['uids'])
      ->condition('gnudi.group_id', $group->id(), '=')
      ->condition('gnudi.send_interval', '0', '=')
      ->execute()->fetchField();
    $users = [];
    if (!empty($uids)) {
      $users = unserialize($uids);
    }
    return $users;
    /* $userMails = [];
    if (!empty($users)) {

      $userEntities = User::loadMultiple($users);

      foreach ($userEntities as $user) {
        $groupMember = $group->getMember($user);
        if ($user->get('field_notifications_status')->value !== 'Disable' && $user->isActive()
          && $groupMember && $groupMember->getGroupContent()
            ->get('request_status')->value == 1
          && !hzd_user_inactive_status_check($user->id())
        ) {
          $userMails[$user->id()] = $user->getEmail();
        }
      }
    } */
  }

  /**
   * Get user mail ids for given service id and type.
   */
  public function hzd_get_immediate_notification_user_mails(array $service_ids, $type) {
    //$service_id = explode(',', $service_ids);

    $query = $this->connection->select('service_notifications', 'sn');
    $query->addField('sn', 'uids');
    $query->condition('sn.service_id', $service_ids, 'IN');
    $query->condition('sn.send_interval', 0, '=');
    $query->condition('sn.type', $type, 'like');
    $get_immediate_notifications_users = $query->execute()->fetchAll();

    $user_ids = array();
    foreach ($get_immediate_notifications_users as $key => $serialized_uids) {
      $unserialized_user_array = unserialize($serialized_uids->uids);
      $user_ids = array_unique(array_merge($user_ids, $unserialized_user_array), SORT_REGULAR);
    }

    if (is_array($user_ids) && count($user_ids) > 0) {
      return $user_ids;
    }

    return [];
  }

  /**
   * Get user mail ids for planning files bundle.
   */
  public function hzd_get_immediate_pf_notification_user_mails() {
    // Previous query, delete this if new query works fine.
    // $query = "SELECT uids FROM {planning_files_notifications} WHERE send_interval = :intval";

    $query = $this->connection->select('planning_files_notifications', 'pfn');
    $query->addField('pfn', 'uids');
    $query->condition('pfn.send_interval', 0, '=');
    $get_pf_immediate_notifications_users = $query->execute()->fetchField();

    $unserialized_user_array = unserialize($get_pf_immediate_notifications_users);
    if (is_array($unserialized_user_array) && (count($unserialized_user_array) > 0)) {
      return $unserialized_user_array;
    }

    return [];
  }

}