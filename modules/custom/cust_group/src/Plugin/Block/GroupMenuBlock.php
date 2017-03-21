<?php

/**
 * @file
 * Contains \Drupal\cust_group\Plugin\Block\GroupMenuBlock.
 */

namespace Drupal\cust_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\group\Entity\Group;
use Drupal\Core\Url;

/**
 * Provides a 'cust_group' block.
 *
 * @Block(
 *   id = "cust_group_menu_block",
 *   admin_label = @Translation("Custom Group Menu block"),
 *   category = @Translation("Custom Group")
 * )
 */
class GroupMenuBlock extends BlockBase
{
    
    public function access(\Drupal\Core\Session\AccountInterface $account, $return_as_object = false) {
        $routeMatch = \Drupal::routeMatch();
        $group = $routeMatch->getParameter('group');
        if (empty($group)) {
            $group = $routeMatch->getParameter('arg_0');
        }
        if (empty($group) && $routeMatch->getRouteName() == 'entity.node.edit_form') {
            $node = $routeMatch->getParameter('node');
            $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($node->id());
            if (!empty($groupContent))
                $group = $groupContent->getGroup();
        }
        if (!empty($group) && self::showBlock($routeMatch)) {
            return \Drupal\Core\Access\AccessResult::allowed();
        }
        return \Drupal\Core\Access\AccessResult::neutral();
    }
    
    static function showBlock($routeMatch = null) {
        $routeToHide = [
            'downtimes.new_downtimes_controller_newDowntimes',
            'downtimes.archived_downtimes_controller',
            'problem_management.problems',
            'problem_management.archived_problems',
            'problem_management.import_history',
        ];
        $parameters = $routeMatch->getParameters();
        if ($routeMatch->getRouteName() == 'cust_group.node_view'
            && $parameters->get('group')->id() == INCIDENT_MANAGEMENT && $parameters->get('group_content')->entity_id->referencedEntities()[0]->getType() == 'downtimes'
        ) {
            //exception for downtimes content type
            return false;
        }
        if (in_array($routeMatch->getRouteName(), $routeToHide)) {
            return false;
        }
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function build() {
        $routeMatch = \Drupal::routeMatch();
        $group = $routeMatch->getParameter('group');
        if (empty($group)) {
            $group = $routeMatch->getParameter('arg_0');
        }
        if (empty($group) && $routeMatch->getRouteName() == 'entity.node.edit_form') {
            $node = $routeMatch->getParameter('node');
            $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($node->id());
            if (!empty($groupContent))
                $group = $groupContent->getGroup();
        }
        //pr($group->id());exit;
        if (!empty($group)) {
            //pr($group);exit;
            if (!is_object($group)) {
                $group = \Drupal\group\Entity\Group::load($group);
            }
            $user = \Drupal::currentUser();
          $groupMember = $group->getMember($user);
            $groupMember = (bool)($groupMember && $groupMember->getGroupContent()->get('request_status')->value == 1);
            //pr((bool)$groupMember);exit;
            if ($groupMember || array_intersect($user->getRoles(), ['admininstrator', 'site_administrator'])) {
                $oldId = $group->get('field_old_reference')->value;
                $menu_name = 'menu-' . $oldId;
                $menu_tree = \Drupal::menuTree();
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
                return ['#title' => $title, '#markup' => \Drupal::service('renderer')->render($menu), '#cache' => ['max-age' => 0]];
            }
      else {
        if ($group->bundle() == 'open' || $group->bundle() == 'moderate' || $group->bundle() == 'moderate_private') {
          $title = $this->t('Actions for @group', ['@group' => $group->label()]);
          if ($group->bundle() == 'open') {
            $url = Url::fromRoute('entity.group.join', ['group' => $group->id()]);
            $link = \Drupal::service('link_generator')->generate($this->t('Join Group'), $url);
          }
          elseif (in_array($group->bundle(), ['moderate', 'moderate_private'])) {
            $url = Url::fromRoute('entity.group.request', ['group' => $group->id()]);
            $link = \Drupal::service('link_generator')->generate($this->t('Request Membership'), $url);
          }
          //\Drupal::service('renderer')->render($link)
          return ['#title' => $title, '#markup' => $link, '#cache' => ['max-age' => 0]];
        }
      }
    }
        return ['#title' => '', '#markup' => '', '#cache' => ['max-age' => 0]];
    }
    
}
