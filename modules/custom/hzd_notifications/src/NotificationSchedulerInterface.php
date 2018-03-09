<?php

namespace Drupal\hzd_notifications;

use Drupal\Core\Entity\EntityInterface;

/**
 * Schedules the notification for actions performed on an entity.
 */
interface NotificationSchedulerInterface {

  /**
   * Inserts a notification record into notifications scheduled table when an
   * action is performed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *  The entity on which the action is performed.
   * @param String $action
   *  The action performed on the entity.
   * @param array $userData
   *  The user data to send the notifications.
   */
  public function schedule(EntityInterface $entity, $action, array $userData);
}