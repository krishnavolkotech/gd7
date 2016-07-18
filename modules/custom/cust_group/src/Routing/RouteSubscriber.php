<?php

namespace Drupal\cust_group\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for cust_group routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    /**
 * foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
 * if ($route = $this->getcust_groupLoadRoute($entity_type)) {
 * $collection->add("entity.$entity_type_id.cust_group_load", $route);
 * }
 * if ($route = $this->getcust_groupRenderRoute($entity_type)) {
 * $collection->add("entity.$entity_type_id.cust_group_render", $route);
 * }
 * }
*/
  }

  /**
   * Gets the cust_group load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getcust_groupLoadRoute(EntityTypeInterface $entity_type) {
    if ($cust_group_load = $entity_type->getLinkTemplate('cust_group-load')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($cust_group_load);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\cust_group\Controller\cust_groupController::entityLoad',
          '_title' => 'cust_group Load',
        ])
        ->addRequirements([
          '_permission' => 'access cust_group information',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_cust_group_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

  /**
   * Gets the cust_group render route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getcust_groupRenderRoute(EntityTypeInterface $entity_type) {
    if ($cust_group_render = $entity_type->getLinkTemplate('cust_group-render')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($cust_group_render);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\cust_group\Controller\cust_groupController::entityRender',
          '_title' => 'cust_group Render',
        ])
        ->addRequirements([
          '_permission' => 'access cust_group information',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_cust_group_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = 'onAlterRoutes';
    return $events;
  }

}
