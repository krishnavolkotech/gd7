<?php

namespace Drupal\downtimes;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManager;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class MyBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    if (!$route_name) {
      return FALSE;
    }
    dpm($route_name);  
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {

    $breadcrumb = new Breadcrumb();
    // Breadcrumbs accumulate in this array, with lowest index being the root
    // (i.e., the reverse of the assigned breadcrumb trail):
    $links = array();
    $label =  t('Home');
    $links[] =  Link::createFromRoute($label, '<front>');
//    $groups = URL::fromRoute('view.all_groups.page_1');
//    // $links[] =  Link::createFromRoute(t('Groups'), $groups);
//    $links[] =  Link::fromTextAndUrl(t('Groups'), $groups); 
    $incident_management_link = URL::fromRoute('entity.group.canonical', array('group' => 24));
    $links[] =  Link::fromTextAndUrl(t('Incident Management'), $incident_management_link);
//    $node = $route_match->getParameter('node');
    $data = $route_match->getParameter('group_content');
    $incident_maintenance_link = URL::fromRoute('downtimes.new_downtimes_controller_newDowntimes', array('group' => 24));
    $links[] =  Link::fromTextAndUrl(t('Incidents and Maintenances'), $incident_maintenance_link);
    
    // $links[] =  Link::createFromRoute(t('Incident Management'), $incident_management);
  //  $links[] =  $data->toLink();
     
    return $breadcrumb->setLinks($links);
  }

}