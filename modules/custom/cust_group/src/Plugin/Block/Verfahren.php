<?php

/**
 * @file
 * Contains \Drupal\cust_group\Plugin\Block\GroupMenuBlock.
 */

namespace Drupal\cust_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\group\Entity\Group;

/**
 * Provides a 'cust_group' block.
 *
 * @Block(
 *   id = "verfahren_menu_side",
 *   admin_label = @Translation("Verfahren"),
 *   category = @Translation("Custom")
 * )
 */
class Verfahren extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    //loading the Verfahren menu child link from primary-links menu
    $parent = \Drupal::entityQuery('menu_link_content')->condition('title', 'Verfahren')->execute();
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
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
//      array('callable' => 'toolbar_menu_navigation_links'),
    );
    $tree = $menu_tree->transform($tree, $manipulators);

    // Finally, build a renderable array from the transformed tree.
    $menu = $menu_tree->build($tree);
    //    $menu_html = drupal_render($menu);
    $title = 'Hello';
    return $menu;
  }

}
