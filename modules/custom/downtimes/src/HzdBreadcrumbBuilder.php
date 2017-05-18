<?php

namespace Drupal\downtimes;


use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * {@inheritdoc}
 */
class HzdBreadcrumbBuilder implements BreadcrumbBuilderInterface
{
    
    /**
     * {@inheritdoc}
     */
    public function applies(RouteMatchInterface $route_match) {
      return FALSE;
        $route_name = $route_match->getRouteName();
        $params = $route_match->getParameters()->all();
        if (in_array($route_name, ['entity.group_content.canonical']) && in_array($params['group_content']->getEntity()->bundle(), ['early_warnings'])) {
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * {@inheritdoc}
     */
    public function build(RouteMatchInterface $route_match) {
//        $route_name = $route_match->getRouteName();
        $params = $route_match->getParameters()->all();
//        $type = $params['group_content']->getEntity()->bundle();
        $breadcrumb = new Breadcrumb();
        $links = array();
        $links[] = Link::createFromRoute(t('Home'), '<front>');
        $group = $params['group'];
//        $listItems = self::getBreadcrumbConfigList($type,$group);
        $links[] = Link::createFromRoute($group->label(), 'entity.group.canonical', array('group' => $group->id()));
//        if ($listItems)
            $links[] = Link::createFromRoute(t('Early Warnings'), 'hzd_earlywarnings.early-warnings', ['group'=>RELEASE_MANAGEMENT]);
        
        return $breadcrumb->setLinks($links)->addCacheableDependency(0);
    }
    
    /**
     * {@inheritdoc}
     */
    static function getBreadcrumbConfigList($type,$group) {
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
