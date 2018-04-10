<?php

namespace Drupal\hzd_customizations\Resolver;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hzd_notifications\Resolver\ServiceResolverInterface;
use Drupal\node\Entity\Node;

/**
 * Returns the services for quickinfo bundle.
 */
class QuickinfoResolver implements ServiceResolverInterface {

  /**
   * @inheritdoc
   */
  public function resolve(EntityInterface $entity) {
    if ($entity->bundle() == 'quickinfo') {
      return $entity->get('field_other_services')->getValue();
    }
    return [];
  }

}
