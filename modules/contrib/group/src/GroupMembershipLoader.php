<?php

namespace Drupal\group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Loader for wrapped GroupContent entities using the 'group_membership' plugin.
 *
 * Seeing as this class is part of the main module, we could have easily put its
 * functionality in GroupContentStorage. We chose not to because other modules
 * won't have that power and we should provide them with an example of how to
 * write such a plugin-specific GroupContent loader.
 *
 * Also note that we don't simply return GroupContent entities, but wrapped
 * copies of said entities, namely \Drupal\group\GroupMembership. In a future
 * version we should investigate the feasibility of extending GroupContent
 * entities rather than wrapping them.
 */
class GroupMembershipLoader implements GroupMembershipLoaderInterface {

  /**
   * Static cache of group access of node types.
   *
   * @var array
   */
  protected $groupAccessNodeTypes = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new GroupTypeController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Gets the group content storage.
   *
   * @return \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected function groupContentStorage() {
    return $this->entityTypeManager->getStorage('group_content');
  }

  /**
   * Wraps GroupContent entities in a GroupMembership object.
   *
   * @param \Drupal\group\Entity\GroupContentInterface[] $entities
   *   An array of GroupContent entities to wrap.
   *
   * @return \Drupal\group\GroupMembership[]
   *   A list of GroupMembership wrapper objects.
   */
  protected function wrapGroupContentEntities($entities) {
    $group_memberships = [];
    foreach ($entities as $group_content) {
      $group_memberships[] = new GroupMembership($group_content);
    }
    return $group_memberships;
  }

  /**
   * {@inheritdoc}
   */
  public function load(GroupInterface $group, AccountInterface $account) {
    $filters = ['entity_id' => $account->id(),'request_status'=>1];
    $group_contents = $this->groupContentStorage()->loadByGroup($group, 'group_membership', $filters);
    $group_memberships = $this->wrapGroupContentEntities($group_contents);
    return $group_memberships ? reset($group_memberships) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $roles = NULL) {
    $filters = [];

    if (isset($roles)) {
      $filters['group_roles'] = (array) $roles;
    }

    $group_contents = $this->groupContentStorage()->loadByGroup($group, 'group_membership', $filters);
    return $this->wrapGroupContentEntities($group_contents);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUser(AccountInterface $account = NULL, $roles = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    // Load all group content types for the membership content enabler plugin.
    $group_content_types = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->loadByProperties(['content_plugin' => 'group_membership']);

    // If none were found, there can be no memberships either.
    if (empty($group_content_types)) {
      return [];
    }

    // Try to load all possible membership group content for the user.
    $group_content_type_ids = [];
    foreach ($group_content_types as $group_content_type) {
      $group_content_type_ids[] = $group_content_type->id();
    }

    $properties = ['type' => $group_content_type_ids, 'entity_id' => $account->id()];
    if (isset($roles)) {
      $properties['group_roles'] = (array) $roles;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $this->groupContentStorage()->loadByProperties($properties);
    return $this->wrapGroupContentEntities($group_contents);
  }

  /**
   * {@inheritdoc}
   */
  public function groupAccessNodeTypes(AccountInterface $account, $node_type_ids, $op) {
    $uid = $account->id();
    $tags = array('grant_permission_all_cached', 'grantpermission_view:' . $uid);
    $tags_update = array('grant_permission_all_cached', 'grantpermission_update_delete:' . $uid);
    $grants_m = [];

    if($op == 'view') {
      $grants_m_cid = 'cust_group:grantpermission_view' . $uid;
    } else {
      $grants_m_cid = 'cust_group:grantpermission_update' . $uid;
    }
    if ($grants_m_cache = \Drupal::cache()->get($grants_m_cid)) {
      $grants_m_cache_data = $grants_m_cache->data;
    }

    if(isset($grants_m_cache_data)) {
      return $grants_m_cache_data;
    }

    if(!isset($grants_m_cache_data)) {
      $membership_loader = \Drupal::service('group.membership_loader');
      foreach ($membership_loader->loadByUser($account) as $group_membership) {
        $group = $group_membership->getGroup();

        // Add the groups the user is a member of to use later on.
        $member_gids[] = $gid = $group->id();

        foreach ($node_type_ids as $node_type_id) {
          $plugin_id = "group_node:$node_type_id";

          switch ($op) {
            case 'view':
              if ($group->hasPermission("view $plugin_id entity", $account)) {
                $grants_m["gnode:$node_type_id"][] = $gid;
              }
              if ($group->hasPermission("view unpublished $plugin_id entity", $account)) {
                $grants_m["gnode_unpublished:$node_type_id"][] = $gid;
              }
              break;

            case 'update':
            case 'delete':
              // If you can act on any node, there's no need for the author grant.
              if ($group->hasPermission("$op any $plugin_id entity", $account)) {
                $grants_m["gnode:$node_type_id"][] = $gid;
              }
              elseif ($group->hasPermission("$op own $plugin_id entity", $account)) {
                $grants_m["gnode_author:$uid:$node_type_id"][] = $gid;
              }
              break;
          }
        }
      }

      //$this->groupAccessNodeTypes[$account->id()][$cache_id][$cache_id.'grants_m'] =  $grants_m;
      if($op == 'view') {
        \Drupal::cache()->set($grants_m_cid, $grants_m, \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT, $tags);
      } else {
        \Drupal::cache()->set($grants_m_cid, $grants_m, \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT, $tags_update);
      }
    }

    return $grants_m;
  }

}
