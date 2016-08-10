<?php

namespace Drupal\cust_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\cust_user\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach($collection->all() as $route=>$routeObj){
      if($route == "entity.node.edit_form"){
	$routeObj->setRequirement('_custom_access','\Drupal\cust_user\Controller\NSMPortalController::access');
      }
    }
  }

}
