<?php

namespace Drupal\hzd_release_inprogress_comments\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserData;
use Drupal\group\Entity\Group;

/**
 * Defines a cache context for "has release commenter role or not" caching.
 *
 * Calculated cache context ID: 'user.release_comments_permissions:%bool'.
 *
 */
class HasReleaseCommentRoleCacheContext implements CacheContextInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * Constructs a new HasReleaseCommentRoleCacheContext class.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserData $userData
   *   The user data service.
   */
  public function __construct(AccountProxyInterface $current_user, UserData $userData) {
    $this->currentUser = $current_user;
    $this->userData = $userData;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Is release commentator");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $group = Group::load(RELEASE_MANAGEMENT);
    $groupMember = $group->getMember($this->currentUser);
    if (array_intersect(['site_administrator', 'administrator'], $this->currentUser->getRoles())) {
      // Site Admin has the permission.
      return '1';
    }
    if (!$groupMember) {
      return '0';
    }
    $roles = $groupMember->getRoles();
    if (!empty($roles) && (in_array($group->bundle() . '-admin', array_keys($roles)))) {
      // Group admin has the permission.
      return '1';
    }
    $rw_comments_permission = $this->userData->get('cust_group', $this->currentUser->id(), 'rw_comments_permission');

    if (!$rw_comments_permission) {
      // Has no permission.
      return '0';
    }
    // Has the permission.
    return '1';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheability = new CacheableMetadata();
    return $cacheability;
  }

}
