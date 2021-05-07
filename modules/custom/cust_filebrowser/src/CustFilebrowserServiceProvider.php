<?php

namespace Drupal\cust_filebrowser;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

class CustFilebrowserServiceProvider implements ServiceModifierInterface {
    public function alter(ContainerBuilder $container) {
      $container
        ->getDefinition('filebrowser.common')
        ->setClass('Drupal\cust_filebrowser\Services\AltCommon');
      $container
        ->getDefinition('filebrowser.action.access_checker')
        ->setClass('Drupal\cust_filebrowser\Access\AltFilebrowserAccessCheck');
      $container
        ->getDefinition('filebrowser.breadcrumb')
        ->setClass('Drupal\system\PathBasedBreadcrumbBuilder')
        ->setArguments([
          new Reference('router.request_context'),
          new Reference('access_manager'),
          new Reference('router'),
          new Reference('path_processor_manager'),
          new Reference('config.factory'),
          new Reference('title_resolver'),
          new Reference('current_user'),
          new Reference('path.current'),
          new Reference('path.matcher'),
        ]);
      // Repeat the above for as many services as you like.
  }
}
