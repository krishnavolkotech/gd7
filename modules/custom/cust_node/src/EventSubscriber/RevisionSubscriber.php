<?php
namespace Drupal\cust_node\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RevisionSubscriber extends RouteSubscriberBase {

  /**
    * MyModule
    *
    * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
    *   The event to process.
    */
  protected function alterRoutes(RouteCollection $collection) {
      
    foreach ($collection->all() as $route) {
        if($route = $collection->get('node.revision_revert_confirm')) {
          $route->setDefaults(array(
            '_controller' => '\Drupal\cust_node\Controller\RevisionSkipConfirm::skipConfirm',
          ));
        }
      }
  }

}
