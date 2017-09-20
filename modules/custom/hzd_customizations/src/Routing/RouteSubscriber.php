<?php

namespace Drupal\hzd_customizations\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
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
    
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setRequirement('_custom_access', "\Drupal\cust_group\Controller\AccessController::userEditAcces");
    }
  }

  /**
   * Code that should be triggered on event $events[KernelEvents::RESPONSE].
   */
  public function onRespond(FilterResponseEvent $event) {
    $key = "node";
    $response = $event->getResponse();
    $request = $event->getRequest();
    $node = $request->get($key);
    $route = \Drupal::routeMatch()->getRouteName();
    if(in_array($route,['downtimes.new_downtimes_controller_newDowntimes','downtimes.archived_downtimes_controller'])) {
	$response->headers->set('X-Frame-Options', 'ALLOWALL');
    }elseif ($node && $node instanceof \Drupal\node\NodeInterface && $node->getType() == "downtimes") {
      $response->headers->set('X-Frame-Options', 'ALLOWALL');
    }
  }
}
