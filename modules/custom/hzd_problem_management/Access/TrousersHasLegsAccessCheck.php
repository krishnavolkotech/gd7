<?php

namespace Drupal\trousers\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
class TrousersHasLegsAccessCheck implements StaticAccessCheckInterface {

  public function appliesTo() {
    return '_access_trousers_has_legs';
  }
  
  public function access(Route $route, Request $request, AccountInterface $account) {
    if (!$account->hasLegs()) { // check if a user has legs
      return static::DENY; // denied! No legs.
    }
    return static::ALLOW; // OK to access trousers
  }
}
