<?php

namespace Drupal\hzd_notifications;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class NotificationScheduler implements NotificationSchedulerInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a NotificationScheduler object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function schedule(EntityInterface $entity, $action, array $userData) {
    $connection = $this->connection;

    $table = 'notifications_scheduled';
    $fields = [
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
      'action' => $action,
      'user_data' => serialize($userData),
    ];

    //@todo Check need for try catch block here, transaction already happens in insert.
    $connection->insert($table)->fields($fields)->execute();

  }

}