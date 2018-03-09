<?php

namespace Drupal\downtimes\Resolver;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hzd_notifications\Resolver\ServiceResolverInterface;
use Drupal\node\Entity\Node;

/**
 * Returns the service for downtimes bundle.
 */
class DowntimesServiceResolver implements ServiceResolverInterface {

  /**
   * @inheritdoc
   */
  public function resolve(EntityInterface $entity) {
    if ($entity->bundle() == 'downtimes') {
      $service_ids = \Drupal::database()->select('downtimes', 'd')
        ->fields('d', ['service_id'])
        ->condition('d.downtime_id', $entity->id(), '=')
        ->execute()
        ->fetchField();

      //@Todo use referencedEntities()->id()
      $service_ids = explode(',', $service_ids);
      $service = Node::loadMultiple($service_ids);

      return $service;
    }
  }

}
