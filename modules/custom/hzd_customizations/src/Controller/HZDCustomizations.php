<?php
/**
 * @file
 * Contains \Drupal\hzd_customizations\Controller\Inactiveusers.
 */
namespace Drupal\hzd_customizations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Class Inactiveusers
 * @package Drupal\hzd_customizations\Controller
 */
class HZDCustomizations extends ControllerBase {
    function access(AccountInterface $account){
        if($node = \Drupal::routeMatch()->getParameter('node')){
            if ($node->getType() == 'quickinfo' && $node->isPublished()) {
                return \Drupal\Core\Access\AccessResult::forbidden();
            }return \Drupal\Core\Access\AccessResult::neutral();
        }
        return \Drupal\Core\Access\AccessResult::neutral();
    }
}