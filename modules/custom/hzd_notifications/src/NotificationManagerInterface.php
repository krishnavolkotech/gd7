<?php

namespace Drupal\hzd_notifications;

use Drupal\Core\Entity\EntityInterface;

/**
 * Manages the notification for actions performed on an entity.
 */
interface NotificationManagerInterface {

  /**
   * Gets the Service for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *  The entity on which action has been performed.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *  Returns the service entities.
   */
  public function getServicesForEntity(EntityInterface $entity);

  /**
   * Returns the user data for the provided service and entity.
   *
   * @param array $services|null
   *  The service entities.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *  The entity on which action has been performed.
   *
   * @return mixed
   *  The user data which is used to send the notifications.
   */
  public function getUserDataForServices(array $services = NULL, EntityInterface $entity);
}