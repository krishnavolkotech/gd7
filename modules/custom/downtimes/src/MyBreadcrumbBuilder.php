<?php

namespace Drupal\downtimes;


use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class MyBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    $params = $route_match->getParameters()->all();
    if(in_array($route_name,['entity.group_content.group_node__deployed_releases.canonical'])){
      return TRUE;
    }
    if($route_name == 'entity.node.edit_form' && $params['node']->getType() == 'downtimes'){
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    $params = $route_match->getParameters()->all();
    $breadcrumb = new Breadcrumb();
    $links = array();
    $links[] =  Link::createFromRoute(t('Home'), '<front>');
//    $groups = URL::fromRoute('view.all_groups.page_1');
//    // $links[] =  Link::createFromRoute(t('Groups'), $groups);
//    $links[] =  Link::fromTextAndUrl(t('Groups'), $groups); 
    $links[] =  Link::createFromRoute(t('Incident Management'), 'entity.group.canonical', array('group' => INCIDENT_MANAGEMENT));
    $links[] =  Link::createFromRoute(t('Incidents and Maintenances'), 'downtimes.new_downtimes_controller_newDowntimes', array('group' => INCIDENT_MANAGEMENT));
    if($route_name == 'entity.node.edit_form' && $params['node']->getType() == 'downtimes'){
      $node = $route_match->getParameter('node');
      $links[] =  Link::createFromRoute(t('Edit '.$node->getTitle()), 'entity.node.edit_form', array('node' => $node->id()));
    }
     
    return $breadcrumb->setLinks($links);
  }

}
