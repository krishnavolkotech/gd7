<?php

namespace Drupal\hzd_react\EventSubscriber;

use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to disable some resource types.
 */
class ResourceTypeBuildEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ResourceTypeBuildEvents::BUILD => [
        ['disableResourceType'],
      ],
    ];
  }

  /**
   * Disables all resource types, that are not used by known api clients.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function disableResourceType(ResourceTypeBuildEvent $event) {
    $enabledResources = [
      'node--release',
      'node--deployed_releases',
      'node--services',
      'node--non_production_environment',
    ];
    if (!in_array($event->getResourceTypeName(), $enabledResources)) {
      $event->disableResourceType();
    }
  }
}