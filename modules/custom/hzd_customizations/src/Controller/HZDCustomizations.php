<?php

namespace Drupal\hzd_customizations\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Class Inactiveusers.
 *
 * @package Drupal\hzd_customizations\Controller
 */
class HZDCustomizations extends ControllerBase {

  /**
   *
   */
  public function access(AccountInterface $account) {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      if ($node->getType() == 'quickinfo' && $node->isPublished()) {
        return AccessResult::forbidden();
      }return AccessResult::allowed();
    }
    return AccessResult::allowed();
  }

}
