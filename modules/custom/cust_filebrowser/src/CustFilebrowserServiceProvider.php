<?php

namespace Drupal\cust_filebrowser;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

class CustFilebrowserServiceProvider implements ServiceModifierInterface {
    public function alter(ContainerBuilder $container) {
      $container
        ->getDefinition('filebrowser.common')
        ->setClass('Drupal\cust_filebrowser\Services\AltCommon');
      $container
        ->getDefinition('filebrowser.action.access_checker')
        ->setClass('Drupal\cust_filebrowser\Access\AltFilebrowserAccessCheck');
    // Repeat the above for as many services as you like.
  }
}
