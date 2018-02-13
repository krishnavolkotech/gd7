<?php

namespace Drupal\hzd_notifications\Resolver;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface for base service resolvers.
 */
interface ServiceResolverInterface {

  /**
   * Resolve the service for given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *  The entity on which action is performed.
   *
   * @return array
   *  The resolved service entities.
   */
  public function resolve(EntityInterface $entity);

}
