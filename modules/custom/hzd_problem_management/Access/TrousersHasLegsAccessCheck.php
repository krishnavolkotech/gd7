<?php

namespace Drupal\trousers\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class TrousersHasLegsAccessCheck implements StaticAccessCheckInterface {

  /**
   *
   */
  public function appliesTo() {
    return '_access_trousers_has_legs';
  }

  /**
   *
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // Check if a user has legs.
    if (!$account->hasLegs()) {
      // denied! No legs.
      return static::DENY;
    }
    // OK to access trousers.
    return static::ALLOW;
  }

}
