<?php

namespace Drupal\downtimes;


use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * {@inheritdoc}
 */
class NodeEditBreadcrumbBuilder implements BreadcrumbBuilderInterface
{
    
    /**
     * {@inheritdoc}
     */
    public function applies(RouteMatchInterface $route_match) {
        $route_name = $route_match->getRouteName();
        $params = $route_match->getParameters()->all();
        
        if ($route_name == 'entity.node.edit_form' && in_array($params['node']->getType(), ['downtimes', 'quickinfo'])) {
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * {@inheritdoc}
     */
    public function build(RouteMatchInterface $route_match) {
        $params = $route_match->getParameters()->all();
        $breadcrumb = new Breadcrumb();
        $links = array();
        $links[] = Link::createFromRoute(t('Home'), '<front>');
        $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($params['node']->id());
        $group = $groupContent->getGroup();
        
        $listItems = \Drupal\downtimes\HzdBreadcrumbBuilder::getBreadcrumbConfigList($group->id());
        $links[] = Link::createFromRoute($group->label(), 'entity.group.canonical', array('group' => $group->id()));
        if ($listItems)
            $links[] = Link::createFromRoute($listItems['title'], $listItems['route'], $listItems['params']);
        $node = $route_match->getParameter('node');
        $links[] = Link::createFromRoute(t('Edit ' . $node->getTitle()), 'entity.node.edit_form', array('node' => $node->id()));
        
        return $breadcrumb->setLinks($links)->addCacheableDependency(0);
    }
    

    
}
