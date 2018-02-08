<?php

namespace Drupal\hzd_notifications\Resolver;

use Drupal\Core\Entity\EntityInterface;

/**
 * Returns the service for planning files bundle.
 */
class PlanningFilesServiceResolver implements ServiceResolverInterface {

  /**
   * @inheritdoc
   */
  public function resolve(EntityInterface $entity) {
    // Planning files doesn't require any service, this is for legacy and
    // uniformity purpose.
    if ($entity->bundle() == 'planning_files') {
      return [];
    }
  }

}
