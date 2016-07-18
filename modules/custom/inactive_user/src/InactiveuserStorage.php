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
  static function inactive_user_admin_mail() {
    $admin_user = User::load(1);
    // $admin_mail	= db_query('SELECT mail FROM {users} WHERE uid = :uid', array(':uid' => 1))->fetchField();
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
  function _inactive_user_with_content($uid) {
    $user_has_nodes = db_select('node', 'n')->fields('n', array('uid'))->condition('n.uid', $uid)->execute()->rowcount();

    $user_has_comments = db_select('comment', 'c')->fields('c', array('uid'))->condition('c.uid', $uid)->execute()->rowcount();

    return (($user_has_nodes + $user_has_comments) > 0) ? 1 : 0;
  }

}
