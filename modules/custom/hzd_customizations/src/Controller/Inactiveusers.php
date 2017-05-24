<?php

namespace Drupal\hzd_customizations\Controller;

use Drupal\group\Entity\GroupContent;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\inactive_user\Inactiveuserhelper;
use Drupal\Core\Url;
use Drupal\inactive_user\InactiveuserStorage;

/**
 * Class Inactiveusers.
 *
 * @package Drupal\hzd_customizations\Controller
 */
class Inactiveusers extends ControllerBase {

  public function inactive_users() {
    $user_list = '';
    /**
         * fetch user from inactive users table
         */
    $query = \Drupal::database()->select('inactive_users', 'iu');
    $query->addField('iu', 'uid');
    $query->condition('iu.uid', 1, '!=');
    $users = $query->execute()->fetchAll();
    /**
         *      [0] => disabled
         * [604800] => 1 week
         * [1209600] => 2 weeks
         * [1814400] => 3 weeks
         * [2419200] => 4 weeks
         * [2592000] => 1 month
         * [7776000] => 3 months
         * [15552000] => 6 months
         * [23328000] => 9 months
         * [31536000] => 1 year
         * [47088000] => 1 year 6 months
         * [63072000] => 2 years
         */
    if ($users) {
      foreach ($users as $user) {
        /**
         * foreach users fetch access , name
         */
        $query = \Drupal::database()->select('users_field_data', 'ufd');
        $query->Fields('ufd', array('access', 'name', 'uid'));
        $query->condition('ufd.uid', $user->uid, '=');
        $query->range(0, 1);
        $u = $query->execute()->fetchAll();

        foreach ($u as $access_user) {
          // User accesse is greater than last week.
          if ($access_user->access > time() - 604800) {
            // User activity in last week, remove from inactivity table.
            $query = \Drupal::database()->delete('inactive_users');
            $query->condition('uid', $user->uid, '=');
            $query->execute();
            /**
                         * log the message
                         */
            $url = Url::fromRoute('entity.user.edit_form', array('user' => $access_user->uid));
            $link = \Drupal::l(t('edit user'), $url);
            $message = 'recent user activity: ' . $access_user->name . 'removed from inactivity list. ' . $link;
            \Drupal::logger('inactive_user')->notice($message);
          }
        }
      }
    }

    // Notify administrator of inactive user accounts.
    self::notify_admin_inactive_accounts();

    // Notify users that their account has been inactive.
    self::notify_user_inactive_accounts();

    // Warn users when they are about to be blocked.
    self::warn_to_block_inactive_accounts();

    // Block user.
    self::block_inactive_accounts();

    // Warn users when they are about to be deleted
//    $this->warn_to_delete_inactive_accounts();
    // Automatically delete users.
    // $this->delete_inactive_accounts();
    $result['#markup'] = t("Checked inactive users");
    return $result;
  }

  /**
   * Notify administrator of inactive user accounts.
   */
  public function notify_admin_inactive_accounts() {
    $notify_time = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_admin');

    /**
         * if notify time is set
         */
    if ($notify_time) {
      $user_list = '';
      $query = \Drupal::database()->select('users_field_data', 'ufd');
      $query->fields('ufd', array('uid', 'name', 'mail', 'access', 'created', 'status'));
      /**
             *  (access not 0 and login not 0 and ufd.created  ) or (created < current time - notifytime)
             */
      $orandcond1 = db_and()->condition('ufd.access', 0, '!=')
        ->condition('ufd.login', 0, '!=')
        ->condition('ufd.access', (time() - $notify_time), '<');

      $orandcond2 = db_and()->condition('ufd.login', 0, '=')
        ->condition('ufd.created', (time() - $notify_time), '<');
      $condition = db_or()->condition($orandcond1)->condition($orandcond2);
      $query->condition($condition);
      /**
             *  active user and not an admin
             */
      $query->condition('ufd.uid', 1, '!=');
      $query->condition('ufd.status', 1, '=');
      $result = $query->execute()->fetchAll();

      foreach ($result as $user) {
        /**
                 * Fetch inactive user flag value where user id not equal to
                 */
        $query = \Drupal::database()->select('inactive_user_flag', 'iuf');
        $query->addField('iuf', 'value');
        $query->condition('iuf.user_id', 1, '!=');
        $inactive_flag = $query->execute()->fetchField();
        /**
                 * Fetch already  uid  of a user exist in
                 * inactive_users table to update else insert to inactive_users table
                 */
        $query = \Drupal::database()->select('inactive_users', 'iu');
        $query->addField('iu', 'uid');
        $query->condition('iu.uid', $user->uid, '=');
        $query->condition('iu.notified_admin', 1, '=');
        $inactive_users_uid = $query->execute()->fetchAll();

        /**
                 * if inactive user flag value is 0 and isset $inactive_users_uid  and user is created before (curent time - $notify_time)
                 */

        if ($inactive_flag != 1 && $user->uid && !$inactive_users_uid
        && ($user->created < (time() - $notify_time))) {

          // db_query('UPDATE {inactive_users} SET notified_admin = 1 WHERE uid = %d', $user->uid);.
          $query = \Drupal::database()->update('inactive_users');
          $query->fields([
            'notified_admin' => 1,
            'inactive_user_notification_flag' => 0,
          ]);
          $query->condition('uid', $user->uid);
          $update_result = $query->execute();

          if (!$update_result) {
            \Drupal::database()->insert('inactive_users')
              ->fields(array(
                'uid' => $user->uid,
                'notified_admin' => 1,
                'inactive_user_notification_flag' => 0,
              ))->execute();
            // Must create a new row.
            //  @db_query('INSERT INTO {inactive_users} (uid, notified_admin) VALUES (%d, 1)', $user->uid);
          }

          $user_list .= "$user->name ($user->mail) last active on " . \Drupal::service('date.formatter')->format($user->access, 'long') . ".\n";
        }
      }

      if (isset($user_list)) {
        Inactiveuserhelper::inactive_user_mail(
            t(\Drupal::config('system.site')->get('site_name') . ' Inactive users'), Inactiveuserhelper::inactive_user_mail_text('notify_admin_text'), $notify_time, NULL, $user_list);
        unset($user_list);
      }
    }
  }

  /**
   * Notify users that their account has been inactive.
   */
  public function notify_user_inactive_accounts() {

    $notify_time = \Drupal::config('inactive_user.settings')->get('inactive_user_notify');

    // variable_get('inactive_user_notify', 0)
    if ($notify_time) {

      $query = \Drupal::database()->select('users_field_data', 'ufd');
      $query->fields('ufd');

      /**
             *  active user and not an admin
             */
      $query->condition('ufd.uid', 1, '!=');
      $query->condition('ufd.status', 1, '=');

      $orandcond1 = db_and()->condition('ufd.access', 0, '!=')
        ->condition('ufd.login', 0, '!=')
        ->condition('ufd.access', (time() - $notify_time), '<');

      $orandcond2 = db_and()->condition('ufd.login', 0, '=')
        ->condition('ufd.created', (time() - $notify_time), '<');

      $condition = db_or()->condition($orandcond1)->condition($orandcond2);
      $query->condition($condition);

      $query->condition('ufd.uid', 1, '!=');
      $query->condition('ufd.status', 1, '=');

      $result = $query->execute()->fetchAll();

      foreach ($result as $user) {
        $query = \Drupal::database()->select('inactive_user_flag', 'iuf');
        $query->addField('iuf', 'value');
        $query->condition('iuf.user_id', $user->uid, '=');
        $inactive_flag = $query->execute()->fetchField();
        // $inactive_flag = db_result(db_query("SELECT value from {inactive_user_flag} WHERE user_id = %d", $user->uid));.
        $query = \Drupal::database()->select('inactive_users', 'iu');
        $query->addField('iu', 'uid');
        $query->condition('iu.uid', $user->uid, '=');
        $query->condition('iu.notified_user', 1, '=');
        $inactive_users_uid = $query->execute()->fetchAll();

        if ($inactive_flag != 1 && $user->uid && !$inactive_users_uid && ($user->created < (time() - $notify_time))) {
          $query = \Drupal::database()->update('inactive_users');
          $query->fields([
            'notified_user' => 1,
          ]);
          $query->condition('uid', $user->uid);
          $affected_rows = $query->execute();
          // db_query('UPDATE {inactive_users} SET notified_user = 1 WHERE uid = %d', $user->uid);.
          if (!$affected_rows) {
            // @db_query('INSERT INTO {inactive_users} (uid, notified_user) VALUES (%d, 1)', $user->uid);
            \Drupal::database()->insert('inactive_users')
              ->fields(array(
                'uid' => $user->uid,
                'notified_user' => 1,
              ))->execute();
          }

          $inactive_user_notify_mail_subject = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_mail_subject');
          if (!$inactive_user_notify_mail_subject) {
            $inactive_user_notify_mail_subject = \Drupal::config('system.site')->get('site_name') . 'Account inactivity';
          }

          $inactive_user_notify_text = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_mail_subject');
          if (!$inactive_user_notify_text) {
            $inactive_user_notify_text = Inactiveuserhelper::inactive_user_mail_text('notify_text');
          }

          Inactiveuserhelper::inactive_user_mail($inactive_user_notify_mail_subject, $inactive_user_notify_text, $notify_time, $user, NULL);
          $user_list = '';
          $user_list .= "user $user->name notified of inactivity " . \Drupal::service('date.formatter')->format($user->access, 'long') . ".\n";

          $url = Url::fromRoute('entity.user.edit_form', array('user' => $user->uid));
          $link = \Drupal::l(t('edit user'), $url, array('query' => array('destination' => 'admin/user/user')));
          // $edit_user = Link::createFromRoute($this->t('edit user'), $url, array('query' => array('destination' => 'admin/user/user')))->toString();
          $message = "user $user->name notified of inactivity " . $user->name . $link;
          \Drupal::logger('inactive_user')->notice($message);
        }
      }
    }
  }

  /**
   * Warn users when they are about to be blocked.
   */
  public function warn_to_block_inactive_accounts() {

    $warn_time = \Drupal::config('inactive_user.settings')->get('inactive_user_auto_block_warn');
    $block_time = \Drupal::config('inactive_user.settings')->get('inactive_user_auto_block');
    if ($warn_time && $block_time) {
      /**
             * select users detail where uid not equal to 1 and status not blocked
             * either ( access not 0 and login not 0 and access time is less than (current
             * time - block time))
             * or
             * login 0 and created is lesss than  ( access not 0 and login not 0
             * and access time is less than (current
             * time - block time))
             *
             */
      $query = \Drupal::database()->select('users_field_data', 'ufd');
      $query->fields('ufd');
      // Mon, 05.10.2015 05:29
      //            $date_formatter = \Drupal::service('date.formatter')->format((time() - $block_time));
      //            echo $date_formatter;
      // .
      $orandcond1 = db_and()->condition('ufd.access', 0, '!=')
        ->condition('ufd.login', 0, '!=')
        ->condition('ufd.access', (time() - $block_time), '<');
      $orandcond2 = db_and()->condition('ufd.login', 0, '=')
        ->condition('ufd.created', (time() - $block_time), '<');
      $condition = db_or()->condition($orandcond1)->condition($orandcond2);
      $query->condition($condition);
      $query->condition('ufd.uid', 1, '!=');
      $query->condition('ufd.status', 0, '!=');
      // $query->range(1);.
      $result = $query->execute()->fetchAll();

      foreach ($result as $user) {
        $query = \Drupal::database()->select('inactive_user_flag', 'iuf');
        $query->addField('iuf', 'value');
        $query->condition('iuf.user_id', $user->uid, '=');
        $query->range(0, 1);
        $inactive_flag = $query->execute()->fetchField();

        // $inactive_flag = db_result(db_query("SELECT value from {inactive_user_flag} WHERE user_id = %d", $user->uid));.
        $query = \Drupal::database()->select('inactive_users', 'iu');
        $query->addField('iu', 'uid');
        $query->condition('iu.uid', $user->uid, '=');
        $query->condition('iu.warned_user_block_timestamp', 0, '>');
        $query->range(0, 1);
        $inactive_users_uid = $query->execute()->fetchField();

        if ($inactive_flag != 1 && $user->uid &&  !$inactive_users_uid && ($user->created < (time() - $block_time))) {
          /**
                    * time + warn time = warned_user_block_timestamp block time
                    */
          /*
           *
           * time() + $warn_time
           *
           */
          $query = \Drupal::database()->update('inactive_users');
          $query->fields([
            'warned_user_block_timestamp' => time() + $warn_time,
          ]);
          $query->condition('uid', $user->uid, '=');
          $update_result = $query->execute();

          if (!$update_result) {
            // @db_query('INSERT INTO {inactive_users} (uid, notified_user) VALUES (%d, 1)', $user->uid);

            \Drupal::database()->insert('inactive_users')
              ->fields(array(
                'uid' => $user->uid,
                'warned_user_block_timestamp' => time() + $warn_time,
              ))->execute();

          }

          $inactive_user_block_warn_mail_subject = \Drupal::config('inactive_user.settings')->get('inactive_user_block_warn_mail_subject');
          if (!$inactive_user_block_warn_mail_subject) {
            $inactive_user_block_warn_mail_subject = \Drupal::config('system.site')->get('site_name') . 'Account inactivity';
          }

          $inactive_user_block_warn_text = \Drupal::config('inactive_user.settings')->get('inactive_user_block_warn_text');
          if (!$inactive_user_block_warn_text) {
            $inactive_user_block_warn_text = Inactiveuserhelper::inactive_user_mail_text('block_warn_text');
          }

          Inactiveuserhelper::inactive_user_mail($inactive_user_block_warn_mail_subject, $inactive_user_block_warn_text, $warn_time, $user, NULL);

          $url = Url::fromRoute('entity.user.edit_form', array('user' => $user->uid));
          $link = \Drupal::l(t('edit user'), $url, array('query' => array('destination' => 'admin/user/user')));

          $message = "user $user->name warned will be blocked due to inactivity " . $user->name . $link;
          \Drupal::logger('inactive_user')->notice($message);

        }
      }

    }
  }

  /**
   * Automatically block users.
   */
  public function block_inactive_accounts() {
    $block_time = \Drupal::config('inactive_user.settings')->get('inactive_user_auto_block');
    if ($block_time) {
      $query = \Drupal::database()->select('users_field_data', 'ufd');
      $query->fields('ufd');

      $orandcond1 = db_and()->condition('ufd.access', 0, '!=')
        ->condition('ufd.login', 0, '!=')
        ->condition('ufd.access', (time() - $block_time), '<');

      $orandcond2 = db_and()->condition('ufd.login', 0, '=')
        ->condition('ufd.created', (time() - $block_time), '<');

      $condition = db_or()->condition($orandcond1)->condition($orandcond2);
      $query->condition($condition);

      $query->condition('ufd.uid', 1, '!=');
      $query->condition('ufd.status', 0, '!=');

      // $query->range(1, 0);.
      $result = $query->execute()->fetchAll();

      foreach ($result as $user) {
        $query = \Drupal::database()->select('inactive_user_flag', 'iuf');
        $query->addField('iuf', 'value');
        $query->condition('iuf.user_id', $user->uid, '=');
        $query->range(0, 1);
        $inactive_flag = $query->execute()->fetchField();
        // $inactive_flag = db_result(db_query("SELECT value from {inactive_user_flag} WHERE user_id = %d", $user->uid));.
        if ($inactive_flag != 1) {
          /**
                    * check  warned_user_block_timestamp less than currennt time
                    */
          $query = \Drupal::database()->select('inactive_users', 'iuf');
          $query->addField('iuf', 'uid');
          $query->condition('iuf.uid', $user->uid, '=');
          $query->condition('iuf.warned_user_block_timestamp', time(), '<');

          $warned_user_block_timestamp_uid = $query->execute()->fetchField();

          $block_time = \Drupal::config('inactive_user.settings')->get('inactive_user_auto_block_warn');

          if ($user->uid && $block_time == '0') {
            $user_block = User::load($user->uid);
            $user_block->block();
            $user_block->save();
            // If ($user) {
            //                             $user->setStatus(0);
            //                             $user->save();
            //                             dpm('i am blocked' . $user->uid);
            //                        }
            //                        format_date
            //                        \Drupal::database()->insert('blocked_users')
            //                                ->fields(array(
            //                                    'uid' => $user->uid,
            //                                    'blocked_time' => time(),
            //                                ))->execute();
            //                        $query = \Drupal::database()->update('notifications');
            //                        $query->fields([
            //                            'status' => 0,
            //                        ]);
            //                        $query->condition('status', 1);
            //                        $query->condition('uid', $user->uid);
            //                        $query->execute();
            // db_query('UPDATE {users} SET status = 0 WHERE uid = %d', $user->uid);
            // Storing blocked user time into database.
            // db_query('INSERT INTO {blocked_users} (uid, blocked_time) VALUES (%d, %d)', $user->uid, time());
            // update the notifications table that block notifications for this user
            // db_query('UPDATE {notifications} SET status = %d WHERE status = %d AND uid = %d', 0, 1, $user->uid);
            // TO do
            //  notifications_queue_clean(array('uid' => $user->uid));
            // notify user.
            $inactive_user_notify_block = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_block');
            if ($inactive_user_notify_block) {
              $query = \Drupal::database()->select('inactive_users', 'iu');
              $query->addField('iu', 'uid');
              $query->condition('iu.uid', $user->uid, '=');
              $query->condition('iu.notified_user_block', 1, '=');
              $query->range(0, 1);
              $inactive_flag = $query->execute()->fetchField();

              if (!$inactive_flag) {
                $query = \Drupal::database()->update('inactive_users');
                $query->fields([
                  ' notified_user_block ' => 1,
                ]);
                $query->condition('uid', $user->uid);
                $update_result = $query->execute();

                if (!$update_result) {
                  \Drupal::database()->insert('inactive_users')
                    ->fields(array(
                      'uid' => $user->uid,
                      'notified_user_block' => 1,
                    ))->execute();
                }

                $inactive_user_block_mail_subject = \Drupal::config('inactive_user.settings')->get('inactive_user_block_mail_subject');
                if (!$inactive_user_block_mail_subject) {
                  $inactive_user_block_mail_subject = \Drupal::config('system.site')->get('site_name') . 'Account blocked due to inactivity';
                }

                $inactive_user_block_notify_text = \Drupal::config('inactive_user.settings')->get('inactive_user_block_notify_text');
                if ($inactive_user_block_notify_text) {
                  $inactive_user_block_notify_text = Inactiveuserhelper::inactive_user_mail_text('block_notify_text');
                }

                Inactiveuserhelper::inactive_user_mail($inactive_user_block_mail_subject, $inactive_user_block_notify_text, $block_time, $user, NULL);

                // watchdog('user', 'user %user blocked due to inactivity', array('%user' => $user->name), WATCHDOG_NOTICE, l(t('edit user'), "user/$user->uid/edit", array('query' => array('destination' => 'admin/user/user'))));.
                $url = Url::fromRoute('entity.user.edit_form', array('user' => $user->uid));
                $link = \Drupal::l(t('edit user'), $url, array('query' => array('destination' => 'admin/user/user')));

                $message = "user $user->name blocked due to inactivity" . $user->name . $link;
                \Drupal::logger('inactive_user')->notice($message);
              }
            }

            $inactive_user_notify_block_admin = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_block_admin');
            // Notify admin.
            if ($inactive_user_notify_block_admin) {
              $query = \Drupal::database()->select('inactive_users', 'iuf');
              $query->addField('iuf', 'uid');
              $query->condition('iuf.uid', $user->uid, '=');
              $query->condition('iuf.notified_admin_block', 1, '=');
              $query->range(0, 1);
              $inactive_flag = $query->execute()->fetchField();
              // db_fetch_object(db_query('SELECT uid FROM {inactive_users} WHERE uid = %d and notified_admin_block = 1', $user->uid));.
              if (!$inactive_flag) {
                $query = \Drupal::database()->update('inactive_users');
                $query->fields([
                  'notified_admin_block' => 1,
                ]);
                $query->condition('uid', $user->uid);
                $update_result = $query->execute();

                if (!$update_result) {
                  // @db_query('INSERT INTO {inactive_users} (uid, notified_user) VALUES (%d, 1)', $user->uid);
                  \Drupal::database()->insert('inactive_users')
                    ->fields(array(
                      'uid' => $user->uid,
                      'notified_admin_block' => 1,
                    ))->execute();
                }
                $user_list = '';
                $user_list .= "user $user->name ($user->mail) last active on " . \Drupal::service('date.formatter')->format($user->access, 'long') . ".\n";
              }
            }
          }
          // don't block user yet if we sent a warning and it hasn't expired.
          elseif ($user->uid && $warned_user_block_timestamp_uid && ($user->created < (time() - $block_time))) {
            $user_block = User::load($user->uid);
            $user_block->block();
            $user_block->save();
            // If ($user) {
            //                             $user->setStatus(0);
            //                             $user->save();
            //                             dpm('i am blocked else' . $user->uid);
            //                        }
            //                        \Drupal::database()->insert('blocked_users')
            //                                ->fields(array(
            //                                    'uid' => $user->uid,
            //                                    'blocked_time' => time(),
            //                                ))->execute();
            //                        $query = \Drupal::database()->update('notifications');
            //                        $query->fields([
            //                            'status' => 0,
            //                        ]);
            //
            //                        $query->condition('status', 1);
            //                        $query->condition('uid', $user->uid);
            //                        $query->execute();
            // notifications_queue_clean(array('uid' => $user->uid));.
            $inactive_user_notify_block = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_block');
            // Notify user.
            if ($inactive_user_notify_block) {
              $notified_user_block_uid = \Drupal::database()->select('inactive_users', 'iuf');
              $query->addField('iuf', 'uid');
              $query->condition('iuf.uid', $user->uid, '=');
              $query->condition('iuf.notified_user_block', 1, '=');
              $query->range(0, 1);
              $inactive_flag = $query->execute()->fetchField();

              if (!$notified_user_block_uid) {
                $query = \Drupal::database()->update('inactive_users');
                $query->fields([
                  'notified_user_block' => 1,
                ]);
                $query->condition('uid', $user->uid);
                $update_result = $query->execute();

                if (!$update_result) {
                  // @db_query('INSERT INTO {inactive_users} (uid, notified_user) VALUES (%d, 1)', $user->uid);
                  \Drupal::database()->insert('inactive_users')
                    ->fields(array(
                      'uid' => $user->uid,
                      'notified_user_block' => 1,
                    ))->execute();
                }

                $inactive_user_block_mail_subject = \Drupal::config('inactive_user.settings')->get('inactive_user_block_mail_subject');
                if (!$inactive_user_block_mail_subject) {
                  $inactive_user_block_mail_subject = \Drupal::config('system.site')->get('site_name') . 'Account blocked due to inactivity';
                }

                $inactive_user_block_notify_text = \Drupal::config('inactive_user.settings')->get('inactive_user_block_notify_text');
                if ($inactive_user_block_notify_text) {
                  $inactive_user_block_notify_text = Inactiveuserhelper::inactive_user_mail_text('block_notify_text');
                }

                Inactiveuserhelper::inactive_user_mail($inactive_user_block_mail_subject, $inactive_user_block_notify_text, $block_time, $user, NULL);

                // watchdog('user', 'user %user blocked due to inactivity', array('%user' => $user->name), WATCHDOG_NOTICE, l(t('edit user'), "user/$user->uid/edit", array('query' => array('destination' => 'admin/user/user'))));.
                $url = Url::fromRoute('entity.user.edit_form', array('user' => $user->uid));

                $link = \Drupal::l(t('edit user'), $url, array('query' => array('destination' => 'admin/user/user')));
                $message = "user $user->name blocked due to inactivity" . $user->name . $link;
                \Drupal::logger('inactive_user')->notice($message);
              }
            }

            // Notify admin.
            $inactive_user_notify_block_admin = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_block_admin');

            if ($inactive_user_notify_block_admin) {

              // db_fetch_object(db_query('SELECT uid FROM {inactive_users} WHERE uid = %d and notified_admin_block = 1', $user->uid))
              $query = \Drupal::database()->select('inactive_users', 'iuf');
              $query->addField('iuf', 'uid');
              $query->condition('iuf.uid', $user->uid, '=');
              $query->condition('iuf.notified_admin_block', 1, '=');
              $query->range(0, 1);
              $notified_admin_block_uid = $query->execute()->fetchField();
              if (!$notified_admin_block_uid) {
                $query = \Drupal::database()->update('inactive_users');
                $query->fields([
                  'notified_admin_block' => 1,
                ]);
                $query->condition('uid', $user->uid);
                $update_result = $query->execute();

                if (!$update_result) {
                  // @db_query('INSERT INTO {inactive_users} (uid, notified_user) VALUES (%d, 1)', $user->uid);
                  \Drupal::database()->insert('inactive_users')
                    ->fields(array(
                      'uid' => $user->uid,
                      'notified_admin_block' => 1,
                    ))->execute();
                }

                $user_list = '';
                $user_list .= "user $user->name ($user->mail) last active on " . \Drupal::service('date.formatter')->format($user->access, 'long') . ".\n";
              }
            }
          }
        }
      }

      if (isset($user_list)) {
        Inactiveuserhelper::inactive_user_mail(
            t(\Drupal::config('system.site')->get('site_name') . ' Blocked users'), Inactiveuserhelper::inactive_user_mail_text('block_notify_admin_text'), $block_time, NULL, $user_list);
        unset($user_list);
      }
    }
  }

  // Warn users when they are about to be deleted
//      function warn_to_delete_inactive_accounts() {
//          $warn_time = \Drupal::config('inactive_user.settings')->get('inactive_user_auto_delete_warn');
//          $delete_time = \Drupal::config('inactive_user.settings')->get('inactive_user_auto_delete');
//
//          if ($warn_time && $delete_time) {
//              $query = \Drupal::database()->select('users_field_data', 'ufd');
//              $query->fields('ufd');
//  
//              $orandcond1 = db_and()->condition('ufd.access', 0, '!=')
//                      ->condition('ufd.login', 0, '!=')
//                      ->condition('ufd.access', (time() - $delete_time), '<');
//  
//              $orandcond2 = db_and()->condition('ufd.login', 0, '=')
//                      ->condition('ufd.created', (time() - $delete_time), '<');
//  
//              $condition = db_or()->condition($orandcond1)->condition($orandcond2);
//              $query->condition($condition);
//  
//              $query->condition('ufd.uid', 1, '!=');
//  
//              // $query->range(1);
//              $result = $query->execute()->fetchAll();
//  
//              foreach ($result as $user) {
//                  $query = \Drupal::database()->select('inactive_user_flag', 'iuf');
//                  $query->addField('iuf', 'value');
//                  $query->condition('iuf.user_id', $user->uid, '=');
//                  $query->range(0, 1);
//                  $inactive_flag = $query->execute()->fetchField();
//                  //	$inactive_flag = db_result(db_query("SELECT value from {inactive_user_flag} WHERE user_id = %d", $user->uid));
//  
//                  $query = \Drupal::database()->select('inactive_users', 'iu');
//                  $query->addField('iu', 'uid');
//                  $query->condition('iu.uid', $user->uid, '=');
//                  $query->condition('iu.warned_user_delete_timestamp', 0, '>');
//                  $query->range(0, 1);
//                  $warned_user_delete_timestamp_uid = $query->execute()->fetchField();
//  
//                  if ($inactive_flag != 1 && $user->uid && !$warned_user_delete_timestamp_uid && ($user->created < (time() - $delete_time))) {
//                      $inactive_user_preserve_content = \Drupal::config('inactive_user.settings')->get('inactive_user_preserve_content');
//                      if ($inactive_user_preserve_content && InactiveuserStorage::_inactive_user_with_content($user->uid)) {
//                          $protected = 1;
//                      } else {
//                          $protected = 0;
//                      }
//  
//                      $query = \Drupal::database()->update('inactive_users');
//                      $query->fields([
//                          'warned_user_delete_timestamp' => time() + $warn_time,
//                          'protected' => $protected,
//                      ]);
//                      $query->condition('uid', $user->uid);
//                      $update_result = $query->execute();
//  
//                      if (!$update_result) {
//                          // @db_query('INSERT INTO {inactive_users} (uid, notified_user) VALUES (%d, 1)', $user->uid);
//                          \Drupal::database()->insert('inactive_users')
//                                  ->fields(array(
//                                      'uid' => $user->uid,
//                                      'warned_user_delete_timestamp' => time() + $warn_time,
//                                      'protected' => $protected,
//                                  ))->execute();
//                      }
//  
//                      if (!$protected) {
//  
//                          $inactive_user_delete_warn_mail_subject = \Drupal::config('inactive_user.settings')->get('inactive_user_delete_warn_mail_subject');
//                          if (!$inactive_user_delete_warn_mail_subject) {
//                              $inactive_user_delete_warn_mail_subject = \Drupal::config('system.site')->get('site_name') . 'Account inactivity';
//                          }
//  
//                          $inactive_user_delete_warn_text = \Drupal::config('inactive_user.settings')->get('inactive_user_delete_warn_text');
//                          if ($inactive_user_delete_warn_text) {
//                              $inactive_user_delete_warn_text = Inactiveuserhelper::inactive_user_mail_text('delete_warn_text');
//                          }
//  
//                          Inactiveuserhelper::inactive_user_mail($inactive_user_delete_warn_mail_subject, $inactive_user_delete_warn_text, $warn_time, $user, NULL);
//  
//                          //    watchdog('user', 'user %user blocked due to inactivity', array('%user' => $user->name), WATCHDOG_NOTICE, l(t('edit user'), "user/$user->uid/edit", array('query' => array('destination' => 'admin/user/user'))));
//                          $url = Url::fromRoute('entity.user.edit_form', array('user' => $user->uid));
//                          $link = \Drupal::l($this->t('edit user'), $url, array('query' => array('destination' => 'admin/user/user')));
//                          $message = "user $user->name warned will be deleted due to inactivity" . $user->name . $link;
//  
//                          \Drupal::logger('inactive_user')->notice($message);
//                      }
//                  }
//              }
//          }
//      }
  /**
   * Automatically delete users.
   */
//  public function delete_inactive_accounts() {
//    $user_list = '';
//    $delete_time = \Drupal::config('inactive_user.settings')->get('inactive_user_auto_delete');
//    if ($delete_time) {
//      $query = \Drupal::database()->select('users_field_data', 'ufd');
//      $query->fields('ufd');
//
//      $orandcond1 = db_and()->condition('ufd.access', 0, '!=')
//        ->condition('ufd.login', 0, '!=')
//        ->condition('ufd.access', (time() - $delete_time), '<');
//
//      $orandcond2 = db_and()->condition('ufd.login', 0, '=')
//        ->condition('ufd.created', (time() - $delete_time), '<');
//
//      $condition = db_or()->condition($orandcond1)->condition($orandcond2);
//      $query->condition($condition);
//      $query->condition('ufd.uid', 1, '!=');
//      $result = $query->execute()->fetchAll();
//
//      foreach ($result as $user) {
//        $query = \Drupal::database()->select('inactive_user_flag', 'iuf');
//        $query->addField('iuf', 'value');
//        $query->condition('iuf.user_id', $user->uid, '=');
//        $query->range(0, 1);
//        $inactive_flag = $query->execute()->fetchField();
//
//        $query = \Drupal::database()->select('inactive_users', 'iu');
//        $query->addField('iu', 'uid');
//        $query->condition('iu.uid', $user->uid, '=');
//        $query->condition('iu.warned_user_delete_timestamp', time(), '=');
//        $query->condition('iu.protected', 1, '!=');
//        $query->range(0, 1);
//        $warned_user_delete_timestamp_uid = $query->execute()->fetchField();
//
//        $inactive_user_auto_delete_warn = \Drupal::config('inactive_user.settings')->get('inactive_user_auto_delete_warn');
//
//         
//        if ($inactive_flag != 1 && $user->uid && (
//            ($inactive_user_auto_delete_warn && $warned_user_delete_timestamp_uid) 
//            || (!$inactive_user_auto_delete_warn))
//            && ($user->created < (time() - $delete_time))) {
//          
//          $inactive_user_preserve_content = \Drupal::config('inactive_user.settings')->get('inactive_user_preserve_content');
//
//          if ($inactive_user_preserve_content && InactiveuserStorage::_inactive_user_with_content($user->uid)) {
//            $query = \Drupal::database()->update('inactive_users');
//            $query->fields([
//              'protected' => 1,
//            ]);
//            $query->condition('uid', $user->uid);
//            $update_result = $query->execute();
//
//            if (!$update_result) {
//              // @db_query('INSERT INTO {inactive_users} (uid, notified_user) VALUES (%d, 1)', $user->uid);
//              \Drupal::database()->insert('inactive_users')
//                ->fields(array(
//                  'uid' => $user->uid,
//                  'protected' => 1,
//                ))->execute();
//            }
//          }
//          else {
//            // Delete the user
//            $array = (array) $user;
//            $groupContent = \Drupal::entityQuery('group_content')
//              ->condition('entity_id', $user->uid)
//              ->execute();
//            // pr($groupContent);exit;
//            foreach ($groupContent as $groupUser) {
//              $gUser = GroupContent::load($groupUser);
//              $gUser->delete();
//            }
//            // sess_destroy_uid($user->uid);
//            /*
//             * to do
//             */
//            // SessionManager::delete($user->uid);.
//            //                        $query = \Drupal::database()->delete('users');
//            //                        $query->condition('uid', $user->uid, '=');
//            //                        $query->execute();
//            $query = \Drupal::database()->delete('inactive_user_flag');
//            $query->condition('user_id', $user->uid, '=');
//            $query->execute();
//
//            $user_delete = User::load($user->uid);
//            // $query = \Drupal::database()->delete('users_roles');
//            //                        $query->condition('uid', $user->uid, '=');
//            //                        $query->execute();
//            //                        $query = \Drupal::database()->delete('authmap');
//            //                        $query->condition('uid', $user->uid, '=');
//            //                        $query->execute();
//            // module_invoke_all('user', 'delete', $array, $user);
//            // to do
//            //    ModuleHandler::invokeAll('user', array('delete', $array, $user));.
//            $inactive_user_notify_delete = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_deleten');
//
//            if ($inactive_user_notify_delete) {
//              $inactive_user_delete_notify_mail_subject = \Drupal::config('inactive_user.settings')->get('inactive_user_delete_notify_mail_subject');
//              if (!$inactive_user_delete_notify_mail_subject) {
//                $inactive_user_delete_notify_mail_subject = \Drupal::config('system.site')->get('site_name') . 'Account removed';
//              }
//
//              $inactive_user_delete_notify_text = \Drupal::config('inactive_user.settings')->get('inactive_user_delete_notify_text');
//              if ($inactive_user_delete_notify_text) {
//                $inactive_user_delete_notify_text = Inactiveuserhelper::inactive_user_mail_text('delete_notify_text');
//              }
//
//              Inactiveuserhelper::inactive_user_mail($inactive_user_delete_notify_mail_subject, $inactive_user_delete_notify_text, $delete_time, $user, NULL);
//            }
//            $inactive_user_notify_delete_admin = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_delete_admin');
//
//            if ($inactive_user_notify_delete_admin) {
//              // $user_list .= "$user->name ($user->mail) last active on ". format_date($user->access, 'large') .".\n";.
//              $user_list .= "user $user->name last active on " . \Drupal::service('date.formatter')->format($user->access, 'long') . ".\n";
//            }
//
//            $message = "user $user->name deleted due to inactivity" . $user->name;
//            
//            if ($user_delete) {
//              $user_delete->delete();
//            }
//            \Drupal::logger('inactive_user')->notice($message);
//          }
//        }
//      }
//      
//      if ($user_list) {
//        Inactiveuserhelper::inactive_user_mail(
//            t(\Drupal::config('system.site')->get('site_name') . ' Deleted accounts'), Inactiveuserhelper::inactive_user_mail_text('delete_notify_admin_text'), $delete_time, NULL, $user_list);
//        unset($user_list);
//      }
//      
//    }
//  }

}
