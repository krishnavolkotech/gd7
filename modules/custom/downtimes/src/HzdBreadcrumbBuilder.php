<?php

namespace Drupal\downtimes;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * {@inheritdoc}
 */
class HzdBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
//    return FALSE;
    $route_name = $route_match->getRouteName();
    $params = $route_match->getParameters()->all();
    if (in_array($route_name, ['entity.taxonomy_term.canonical'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $routeMatch) {
//    exit;
    $term = $routeMatch->getParameter('taxonomy_term');
    $label = $term->label();
    $storage = \Drupal::service('entity_type.manager')
            ->getStorage('taxonomy_term');
    $parents = $storage->loadParents($term->id());
    $group = \Drupal::service('entity_type.manager')
            ->getStorage('group')
            ->loadByProperties(['label' => reset($parents)->label()]);
    $group = reset($group);
    $links = array();
    $links[] = Link::createFromRoute(t('Home'), '<front>');
    $links[] = $group->toLink();
    $links[] = Link::createFromRoute('FAQ-Einträge', '<current>');
    $breadcrumb = new Breadcrumb();
    return $breadcrumb->setLinks($links)->addCacheableDependency(0);
  }

  /**
   * {@inheritdoc}
   */
  static function getBreadcrumbConfigList($type, $group) {
    $listItems = [
        'downtimes' => [
            'route' => 'downtimes.new_downtimes_controller_newDowntimes',
            'params' => ['group' => $group->id()],
            'title' => t('Incidents and Maintenances'),
        ],
        'quickinfo' => [
            'route' => 'view.rz_schnellinfo.page_2',
            'params' => ['group' => RELEASE_MANAGEMENT],
            'title' => t('RZ-Schnellinfo'),
        ]
    ];
    return $listItems[$type] ? $listItems[$type] : null;
  }

}
