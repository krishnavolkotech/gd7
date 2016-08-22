<?php

namespace Drupal\hzd_customizations\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\hzd_customizations\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        $events[RoutingEvents::ALTER] = ['onAlterRoutes',-9999];  // negative Values means "late"
        return $events;
    }
    /**
     * {@inheritdoc}
    */
    protected function alterRoutes(RouteCollection $collection) {
        if ($route = $collection->get('entity.node.edit_form')) {
	//added the below check in \Drupal\cust_group\Controller\AccessController::groupNodeEdit
 //           $route->setRequirement('_custom_access','\Drupal\hzd_customizations\Controller\HZDCustomizations::access');
        }
    }

}
