<?php

namespace Drupal\problem_management\Resolver;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hzd_notifications\Resolver\ServiceResolverInterface;
use Drupal\node\Entity\Node;

/**
 * Returns the service for Problem bundle.
 */
class ProblemServiceResolver implements ServiceResolverInterface {

  /**
   * @inheritdoc
   */
  public function resolve(EntityInterface $entity) {
    if ($entity->bundle() == 'problem') {
      $services = $entity->get('field_services')->referencedEntities();
      //$service_ids = $service_field[0]['target_id'];
      //@Todo use ->id()
      //$service = Node::loadMultiple($service_ids);

      return $services;
    }
  }

}
