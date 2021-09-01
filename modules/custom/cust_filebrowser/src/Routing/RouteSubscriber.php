<?php

namespace Drupal\cust_filebrowser\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Custom Access Check for filebrowser downloads.
    if ($route = $collection->get('filebrowser.page_download')) {
      $route->setRequirements(['_cust_filebrowser_access_check' => 'TRUE']);
    }
  }

}
