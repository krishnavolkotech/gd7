<?php

namespace Drupal\cust_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\cust_group\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change render content '/group/{group}/node/{group_node_id}' to '/node/{node}'.// as previous one just renders node title as content
    if ($route = $collection->get('entity.group_content.group_node__deployed_releases.canonical')) {
      if($route->getPath() == '/group/{group}/node/{group_content}'){
	$route->setDefault('_controller','\Drupal\cust_group\Controller\CustNodeController::groupContentView');
      }
    }
    if ($route = $collection->get('entity.group_content.group_membership.canonical')) {
      if($route->getPath() == '/group/{group}/members/{group_content}'){
        $route->setDefault('_controller','\Drupal\cust_group\Controller\CustNodeController::groupMemberView');
      }
    }
    if ($route = $collection->get('entity.node.edit_form')){
      $route->setRequirement('_custom_access','\Drupal\cust_group\Controller\AccessController::groupNodeEdit');
    }
  }
}
