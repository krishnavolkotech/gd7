<?php

namespace Drupal\inactive_user;

use Drupal\user\Entity\User;

/**
 *
 */
class InactiveuserStorage {

  /**
   * Get administrator e-mail address(es).
   */
  static public function inactive_user_admin_mail() {
    $admin_user = User::load(1);
    // $admin_mail	=  \Drupal::database()->query('SELECT mail FROM {users} WHERE uid = :uid', array(':uid' => 1))->fetchField();
    $mail = \Drupal::config('system.site')->get('mail');
    $inactive_user_admin_email = \Drupal::config('inactive_user.settings')->get('inactive_user_admin_email');
    if ($inactive_user_admin_email) {
      return $inactive_user_admin_email;
    }
    elseif ($mail) {
      return $mail;
    }
    else {
      return $admin_user->get('mail')->value;
    }
  }

  /**
   * Returns 1 if the user has ever created a node or a comment.
   *
   * The settings of inactive_user.module allow to protect such
   * users from deletion.
   */
  static public function _inactive_user_with_content($uid) {
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->Fields('n', array('nid', 'title'));
    $query->condition('type', 'group', '=');
    $group = $query->execute()->fetchObject();

    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->addExpression('count(*)');
    $query->condition('nfd.uid', $uid);
    $user_has_nodes = $query->execute()->fetchField();

    $query = \Drupal::database()->select('comment_field_data', 'cfd');
    $query->addExpression('count(*)');
    $query->condition('cfd.uid', $uid);
    $user_has_comments = $query->execute()->fetchField();

    return (($user_has_nodes + $user_has_comments) > 0) ? 1 : 0;
  }

}
