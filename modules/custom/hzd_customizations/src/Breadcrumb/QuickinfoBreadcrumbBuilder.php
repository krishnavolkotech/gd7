<?php

namespace Drupal\hzd_customizations;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class QuickinfoBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {

    $route_name = $route_match->getRouteName();
    /**
     * to do quicinfo bread crumb debug
     * 
     */      

    //    dpm($route_name);
 //   $params = $route_match->getParameters()->all();
    if(in_array($route_name,['entity.group_content.group_node__quickinfo.create_form'])){
      //     dpm('applies appliesappliesappliesappliesapplies');
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    //    echo 'jhdgfs';  exit;
    
    $route_name = $route_match->getRouteName();
 //   $params = $route_match->getParameters()->all();
    if(in_array($route_name,['entity.group_content.group_node__quickinfo.create_form'])){
      return TRUE;
    }
    $route_name = $route_match->getRouteName();
    $params = $route_match->getParameters()->all();
    $breadcrumb = new Breadcrumb();
    $links = array();
    $links[] =  Link::createFromRoute(t('Home'), '<front>');

    $links[] =  Link::createFromRoute(t('Autoren RZ-Schnellinfo'), 'entity.group.canonical', array(
       'group' => QUICKINFO
      )
    );
    $links[] =  Link::createFromRoute(t('RZ-schnellinfo'), '<none>');    
    return $breadcrumb->setLinks($links)->addCacheableDependency(0);
  }
}