<?php

namespace Drupal\hzd_customizations\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\HttpKernel\KernelEvents;

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
    // Negative Values means "late".
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -9999];
    $events[KernelEvents::REQUEST][] = array('showCurrentRoute');
    return $events;
  }
  

  /**
   * {@inheritdoc}
   */
  public static function showCurrentRoute(){
      if(isset($_GET['route'])){
      $route = \Drupal::routeMatch()->getRouteName();
      echo $route;exit;
      }
  }


  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.node.edit_form')) {
      // Added the below check in \Drupal\cust_group\Controller\AccessController::groupNodeEdit
      //           $route->setRequirement('_custom_access','\Drupal\hzd_customizations\Controller\HZDCustomizations::access');.
    }
  }

}
