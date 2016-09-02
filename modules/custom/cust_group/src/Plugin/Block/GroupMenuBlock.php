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
 *   id = "cust_group_menu_block",
 *   admin_label = @Translation("Custom Group Menu block"),
 *   category = @Translation("Custom Group")
 * )
 */
class GroupMenuBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_tree = \Drupal::menuTree();
    $group = \Drupal::routeMatch()->getParameter('group');
		if(!empty($group)){
		//pr($group);exit;
			if(!is_object($group)){
				$group = \Drupal\group\Entity\Group::load($group);
			}
			$oldId = $group->get('field_old_reference')->value;
			$menu_name = 'menu-'.$oldId;
			// Build the typical default set of menu tree parameters.
			$parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
	
			// Load the tree based on this set of parameters.
			$tree = $menu_tree->load($menu_name, $parameters);
			$manipulators = [];
			$tree = $menu_tree->transform($tree, $manipulators);
	
			// Finally, build a renderable array from the transformed tree.
			$menu = $menu_tree->build($tree);
	
			//    $menu_html = drupal_render($menu);
			$title = $group->label();
			return ['#title'=>$title,'#markup'=>\Drupal::service('renderer')->render($menu),'#cache'=>['max-age'=>0]];
		}
  }
}
