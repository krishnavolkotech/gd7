<?php

namespace Drupal\hzd_notifications\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the price.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the base service resolver one.
 */
interface ChainServiceResolverInterface extends ServiceResolverInterface {

  /**
   * @param \Drupal\hzd_notifications\Resolver\ServiceResolverInterface $resolver
   */
  public function addResolver(ServiceResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\hzd_notifications\Resolver\ServiceResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
