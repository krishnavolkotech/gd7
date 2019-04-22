<?php

namespace Drupal\hzd_earlywarnings\Resolver;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hzd_notifications\Resolver\ServiceResolverInterface;
use Drupal\node\Entity\Node;

/**
 * Returns the service for early_warnings bundle.
 */
class EarlyWarningsServiceResolver implements ServiceResolverInterface {

  /**
   * @inheritdoc
   */
  public function resolve(EntityInterface $entity) {
    if ($entity->bundle() == 'early_warnings' || $entity->bundle() == 'release_comments') {
      $service_id = (array)$entity->get('field_release_service')->value;
      //@Todo use referencedEntities()->id()
      $services = Node::loadMultiple($service_id);

      return $services;
    }
  }

}
