<?php
/**
* @file
* Contains \Drupal\hzd_customizations\Routing\RouteSubscriber.
*/

namespace Drupal\hzd_customizations\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
* Listens to the dynamic route events.
*/
class RouteSubscriber extends RouteSubscriberBase {

    /**
    * {@inheritdoc}
    */
    public function alterRoutes(RouteCollection $collection) {
        dsm('hello');
        if ($route = $collection->get('entity.node.edit_form')) {
            dpm($route);
        }
    }

}