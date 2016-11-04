<?php
/**
 * @file
 * Contains \Drupal\cust_group\Plugin\Block\ITSMProzesseMenuBlock.
 */
namespace Drupal\cust_group\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\group\Entity\Group;
/**
 * Provides a 'cust_group' block.
 *
 * @Block(
 *   id = "itsmprozesse_menu_block",
 *   admin_label = @Translation("ITSM Prozesse Menu block"),
 *   category = @Translation("Custom Group")
 * )
 */
class ITSMProzesseMenuBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $parent = \Drupal::entityQuery('menu_link_content')->condition('title', 'ITSM-Prozesse')->execute();
    $menuLink = \Drupal\menu_link_content\Entity\MenuLinkContent::load(reset($parent));
    $menu_name = 'primary-links';
    $menu_tree = \Drupal::menuTree();
    // Build the typical default set of menu tree parameters.
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setRoot($menuLink->getPluginId())->excludeRoot();
    // Load the tree based on this set of parameters.
    $tree = $menu_tree->load($menu_name, $parameters);
    $manipulators = array(
//      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
//      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
//      array('callable' => 'toolbar_menu_navigation_links'),
    );
    $tree = $menu_tree->transform($tree, $manipulators);

    // Finally, build a renderable array from the transformed tree.
    $menu = $menu_tree->build($tree);
    //    $menu_html = drupal_render($menu);
    return $menu;
    
    
    
//    $groups = [24,26,31,32,34,4];
//    $groupEntities = Group::loadMultiple($groups);
//    foreach($groupEntities as $group){
//      $menuItems[] = $group->toLink();
//    }
//    //$menuItems[] = \Drupal\Core\Link::createFromRoute(t('Contents'),'view.group_content.page_1',['arg_0'=>$groupId]);
//    //$menuHtml = '<ul class="menu nav">
//    //<li><a href="/group/24">Incident Management</a></li>
//    //<li><a href="/group/26">Capacity Management</a></li>
//    //<li><a href="/group/31">Problem Management</a></li>
//    //<li><a href="/group/32">Release Management</a></li>
//    //<li><a href="/group/34">Service Level Management</a></li>
//    //<li><a href="/group/4">Betriebsportal KONSENS</a></li>
//    //</ul>';
//    $menu = [
//            //'#title'=>$this->t("ITSM-Prozesse"),
//            '#items'=>$menuItems,
//            '#theme'=>'item_list',
//            '#list_type'=>'ul',
//            '#attributes'=>['class'=>['menu nav']],
//            '#cache'=>['max-age'=>0]
//            ];
//    return $menu;
  }
}
