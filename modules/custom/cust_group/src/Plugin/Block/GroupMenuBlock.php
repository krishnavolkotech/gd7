<?php

/**
 * @file
 * Contains \Drupal\cust_group\Plugin\Block\GroupMenuBlock.
 */

namespace Drupal\cust_group\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

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
  /*  public function access(\Drupal\Core\Session\AccountInterface $account, $return_as_object = FALSE) {
    $routeMatch = \Drupal::routeMatch();
    $group = $routeMatch->getParameter('group');
    if (empty($group) && $routeMatch->getRouteName() == 'entity.node.edit_form') {
    $node = $routeMatch->getParameter('node');
    $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($node->id());
    if (!empty($groupContent)) {
    $group = $groupContent->getGroup();
    }
    }
    if (!empty($group) && self::showBlock($routeMatch)) {
    return \Drupal\Core\Access\AccessResult::allowed();
    }
    return \Drupal\Core\Access\AccessResult::neutral();
    } */

  static function showBlock($routeMatch = NULL) {
    $routeToHide = [
      'downtimes.new_downtimes_controller_newDowntimes',
      'downtimes.archived_downtimes_controller',
      'problem_management.problems',
      'problem_management.archived_problems',
      'problem_management.import_history',
      'downtimes.DowntimesnotesDisplay',
    ];
    $parameters = $routeMatch->getParameters();
    if ($routeMatch->getRouteName() == 'cust_group.node_view' && $parameters->get('group')
        ->id() == INCIDENT_MANAGEMENT && $parameters->get('group_content')->entity_id->referencedEntities()[0]->getType() == 'downtimes'
    ) {
      //exception for downtimes content type
      return FALSE;
    }
    if (in_array($routeMatch->getRouteName(), $routeToHide)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $group = \Drupal\cust_group\CustGroupHelper::getGroupFromRouteMatch();
//    pr($routeMatch->getParameters());exit;
    if (!empty($group)) {
      //pr($group);exit;
      if (!is_object($group)) {
        $group = \Drupal\group\Entity\Group::load($group);
      }
      $routeMatch = \Drupal::routeMatch();
      $node = $routeMatch->getParameter('node');
      if (!empty($node)) {
        if (!is_object($node)) {
          $type = node_get_entity_property_fast([$node], 'type')[$node];
          $status = node_get_entity_property_fast([$node], 'status')[$node];
        } else {
          $type = $node->getType();
          $status = $node->isPublished() ?: 0;
        }
      }
      if ($node && $type == 'quickinfo' && $status == 1) {
        $group = \Drupal\group\Entity\Group::load(RELEASE_MANAGEMENT);
      }
      $user = \Drupal::currentUser();
      $groupMember = $group->getMember($user);
      $groupMember = (bool) ($groupMember &&  group_request_status($groupMember));
      
      //pr((bool)$groupMember);exit;
      if ($groupMember || array_intersect($user->getRoles(), [
          'admininstrator',
          'site_administrator'
        ])
      ) {
        $oldId = $group->get('field_old_reference')->value;
        $menu_name = 'menu-' . $oldId;
        $menu_tree = \Drupal::menuTree();
        // Build the typical default set of menu tree parameters.
        $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);

        // Load the tree based on this set of parameters.
        $tree = $menu_tree->load($menu_name, $parameters);
        $manipulators = [
          ['callable' => 'menu.default_tree_manipulators:checkAccess'],
          ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort']
        ];

        $tree = $menu_tree->transform($tree, $manipulators);

        // Finally, build a renderable array from the transformed tree.
        $menu = $menu_tree->build($tree);

        //    $menu_html = drupal_render($menu);
        $title = $group->label();
        return [
          '#title' => $title,
          '#markup' => \Drupal::service('renderer')->render($menu),
          '#cache' => ['max-age' => 0]
        ];
      }
      else {
        if ($group->bundle() == 'open' || $group->bundle() == 'moderate' || $group->bundle() == 'moderate_private') {
          $title = $this->t('Actions for @group', ['@group' => $group->label()]);
          $group_member_join_link = ['#type' => 'link'];
          if ($group->bundle() == 'open') {
            $group_member_join_link['#url'] = Url::fromRoute('entity.group.join', ['group' => $group->id()]);
            $group_member_join_link['#title'] = $this->t('Join Group');
          }
          elseif (in_array($group->bundle(), [
            'moderate',
            'moderate_private'
          ])) {
            $group_member_join_link['#url'] = Url::fromRoute('entity.group.request', ['group' => $group->id()]);
            $group_member_join_link['#title'] = $this->t('Request Membership');
          }
          //\Drupal::service('renderer')->render($link)
          $markup['#title'] = $title;
          $markup['link'] = $group_member_join_link;
          $groupAdmins = $group->getMembers($group->bundle() . '-admin');
          $data = [];
          $admin_uids = [];
          foreach ($groupAdmins as $groupadmin) {
              if (!hzd_user_inactive_status_check($groupadmin->getUser()->id()) && $groupadmin->getUser()->isActive()) {
                  $admin_uids[] = $groupadmin->getUser()->id();
              }
          }
          if(!empty($admin_uids)){
            $db = \Drupal::database();
            $query = $db
                ->select('users_field_data','u')
                ->fields('u', array('name','uid'))
                ->fields('s', array('abbr'))
                ->fields('cp', array('firstname','lastname'))
                ->condition('u.uid', $admin_uids, 'IN')
                ->orderBy('abbr')
                ->orderBy('lastname');
            $query->join('cust_profile', 'cp', 'u.uid = cp.uid');
            $query->join('states', 's', 's.id = cp.state_id');
            $admin_details = $query->execute()->fetchAll();
            foreach ($admin_details as $admin_user) {
                $data[] = [
                    '#type' => 'link',
                    '#title' => $admin_user->firstname . ' ' . $admin_user->lastname . ' (' . $admin_user->abbr . ')',
                    '#url' => Url::fromUri('internal:/user/' . $admin_user->uid),
                ];
            }
          }
          $markup['groupadmin_list'] = [
            '#title' => $this->t('List of Group Admin'),
            '#prefix' => '<div>',
            '#suffix' => '</div>',
            '#items' => $data,
            '#theme' => 'item_list',
            '#type' => 'ul',
            //'#attributes' => ['class' => ['incidents-home-block']]
          ];
          $markup['#cache'] = ['max-age' => 0];
          return $markup;
        }
      }
    }
    return ['#title' => '', '#markup' => '', '#cache' => ['max-age' => 0]];
  }

  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $group = \Drupal\cust_group\CustGroupHelper::getGroupFromRouteMatch();

    if (!empty($group)) {
      $access = AccessResult::allowed('Group context available.');
    }
    else {
      $access = AccessResult::forbidden('Group context not available.');
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /* public function cmp($a, $b) {
      return strcmp($a->)
  } */
}
