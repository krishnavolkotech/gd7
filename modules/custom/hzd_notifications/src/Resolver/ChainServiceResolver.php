<?php

namespace Drupal\hzd_notifications\Resolver;

use Drupal\Core\Entity\EntityInterface;

/**
 * Default implementation of the chain base service resolver.
 */
class ChainServiceResolver implements ChainServiceResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\hzd_notifications\Resolver\ServiceResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainBaseServiceResolver object.
   *
   * @param \Drupal\hzd_notifications\Resolver\ServiceResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(ServiceResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolvers() {
    return $this->resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(EntityInterface $entity) {
    foreach ($this->resolvers as $resolver) {
      $result = $resolver->resolve($entity);
      if ($result) {
        return $result;
      }
    }
  }

}
