<?php

namespace Drupal\downtimes;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * {@inheritdoc}
 */
class GroupMemberAddBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  
  use StringTranslationTrait;
  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    if ($route_name == 'entity.group_content.add_form') {
      return TRUE;
    }
    return FALSE;
  }
  
  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $routeMatch) {
    $breadcrumb = new Breadcrumb();
    $links = array();
    $links[] = Link::createFromRoute(t('Home'), '<front>');
    $group = $routeMatch->getParameter('group');
//    pr($group->id());exit;
    $links[] = $group->toLink($group->label());
//    $links[] = Link::createFromRoute($this->t('Add Member'), '<none>');
  
    $request = \Drupal::request();
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', 'Add Member');
    }
    
    return $breadcrumb->setLinks($links)->addCacheableDependency(0);
  }
  
}
