<?php

namespace Drupal\hzd_release_management\Resolver;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hzd_notifications\Resolver\ServiceResolverInterface;
use Drupal\node\Entity\Node;

/**
 * Returns the service for Release bundle.
 */
class ReleaseServiceResolver implements ServiceResolverInterface {

  /**
   * @inheritdoc
   */
  public function resolve(EntityInterface $entity) {
    if ($entity->bundle() == 'release') {
      $services = $entity->get('field_relese_services')->referencedEntities();
      //$service_id = $service_field[0]['target_id'];
      //@Todo use referencedEntities()->id()
      //$service = Node::load($service_id);

      return $services;
    }
  }

}
