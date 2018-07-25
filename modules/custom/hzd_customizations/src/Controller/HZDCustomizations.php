<?php

namespace Drupal\hzd_customizations\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Class Inactiveusers.
 *
 * @package Drupal\hzd_customizations\Controller
 */
class HZDCustomizations extends ControllerBase {

  /**
   *
   */
  public function access(AccountInterface $account) {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      if ($node->getType() == 'quickinfo' && $node->isPublished()) {
        return AccessResult::forbidden();
      }return AccessResult::allowed();
    }
    return AccessResult::allowed();
  }

    /**
     * Adds all users to the following groups:
     * Incident Managent, Problem Management, Release Management, Service Level Management, Kapazitaetsmanagement, Betriebposrtal KONSENS
     *
     * All users should be members of these groups and are not allowed to leave them.
     *
     */
  public function add_users_to_system_groups() {
      $system_groups = array("1", "2", "6", "15", "21", "39");
      //Excluding user 0
      $all_user_ids = \Drupal::entityQuery('user')->condition('uid', [0], 'NOT IN')->execute();
      $all_users = User::loadMultiple($all_user_ids);
      $user_count = 0;
      foreach ($system_groups as $sysgroup) {
          $group = \Drupal\group\Entity\Group::load($sysgroup);
          foreach ($all_users as $user) {
              if (!$group->getMember($user)) {
                  $group->addMember($user);
                  $user_count++;
              }
          }
/* 
 *    Changed 2018/07/06 by Robin Schomburg <Robin.Schomburg@hzd.hessen.de>
 *    The function $group->save() is not needed and triggers unnecessary notifications.
 *       $group->save();
 */
      }
      return array(
          '#type' => 'markup',
          '#markup' => t('Users added to system groups:') . ' ' . $user_count,
      );
  }
}
