<?php

namespace Drupal\hzd_notifications\Resolver;

use Drupal\Core\Entity\EntityInterface;

/**
 * Returns the default service, in case no resolver returns anything.
 */
class DefaultServiceResolver implements ServiceResolverInterface {

  /**
   * @inheritdoc
   */
  public function resolve(EntityInterface $entity) {
    // TODO: Implement resolve() method.

    return [];
  }

}
