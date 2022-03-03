<?php

namespace Drupal\hzd_customizations\Controller;

use Drupal\group\Entity\GroupContent;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\inactive_user\Inactiveuserhelper;
use Drupal\Core\Url;
use Drupal\inactive_user\InactiveuserStorage;
use Drupal\Core\Database\Query\Condition;

/**
 * Class Inactiveusers.
 *
 * @package Drupal\hzd_customizations\Controller
 */
class Inactiveusers extends ControllerBase {

  public static function inactive_users() {
    $sitename = \Drupal::config('system.site')->get('name');
    global $base_url;
    /**
     * fetch inactive users from inactive users table and check each users
     * last access. if inactive user logged in past one week. Then delete
     * inactive user row from inactive_users table.
     */
    /**
    //   deleting inactive users from cron commented.
    $query = \Drupal::database()->select('inactive_users', 'iu');
    $query->addField('iu', 'uid');
    $query->condition('iu.uid', 1, '!=');
    $inactive_users = $query->execute()->fetchAll();
    */
    /**
     * [0] => disabled
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
    /**
    if ($inactive_users) {
      foreach ($inactive_users as $user) {
    */
        /**
         * Foreach users fetch access, name.
         */
    /** 
        $query = \Drupal::database()->select('users_field_data', 'ufd');
        $query->Fields('ufd', array('access', 'name', 'uid'));
        $query->condition('ufd.uid', $user->uid, '=');
        $query->range(0, 1);
        $user_details = $query->execute()->fetchAll();

        foreach ( $user_details as  $user_detail) {
          // User accesses is greater than last week.
          if ( $user_detail->access > time() - 604800) {
            // If User active in last week, remove from inactivity table.
            $query = \Drupal::database()->delete('inactive_users');
            $query->condition('uid', $user->uid, '=');
            $query->execute();
    */ 
            /**
             * Log message - 'user removed from inactive user list'.
             */
    /**
            $url = Url::fromRoute('entity.user.edit_form', array('user' =>  $user_detail->uid));
            $link = \Drupal::l(t('edit user'), $url);
            $message = 'recent user activity: ' .  $user_detail->name . 'removed from inactivity list. ' . $link;
            \Drupal::logger('inactive_user')->notice($message);
          }
        }
      }
    }
    */
    // Notify administrator of inactive user accounts.
    self::notify_admin_inactive_accounts($sitename, $base_url);
    // Notify users that their account has been inactive.
    self::notify_user_inactive_accounts($sitename, $base_url);
    // Warn users when they are about to be blocked.
    self::warn_to_block_inactive_accounts($sitename, $base_url);
    // Block inactive user.
    self::block_inactive_accounts($sitename, $base_url);
//    $result['#markup'] = t("Checked inactive users");
//    return $result;
  }

  /**
   * Notify administrator of inactive user accounts.
   */
  public static function notify_admin_inactive_accounts($sitename, $base_url) {
    // Admin notify time.
    $notify_time = \Drupal::config('inactive_user.settings')
      ->get('inactive_user_notify_admin');
    /**
     * If notify time is set.
     */
    if ($notify_time) {
      $user_list = [];
      /**
       * Fetch user details who were not new user,
       * and user access time is less than notify time - (time() - $notify_time).
       *  or else
       * Fetch user details who were not logged in even once
       * and user created time is less than notify time - (time() - $notify_time).
       * and
       * not admin, active user.
       */
      $query = \Drupal::database()->select('users_field_data', 'ufd');
      $query->fields('ufd', array('uid', 'name', 'mail', 'access', 'created', 'status'));
      /**
       *  (access not 0 and login not 0 and ufd.created  ) or (created < current time - notifytime)
       */
      $and1 = new Condition('AND');
      $orandcond1 = $and1->condition('ufd.access', 0, '!=')
        ->condition('ufd.login', 0, '!=')
        ->condition('ufd.access', (time() - $notify_time), '<');

      $and2 = new Condition('AND');
      $orandcond2 = $and2->condition('ufd.login', 0, '=')
        ->condition('ufd.created', (time() - $notify_time), '<');
	
      $or = new Condition('OR');
      $condition = $or->condition($orandcond1)->condition($orandcond2);
      $query->condition($condition);
      /**
       *  Active user and not an admin.
       */
      $query->condition('ufd.uid', 1, '!=');
      $query->condition('ufd.status', 1, '=');
      $result = $query->execute()->fetchAll();

      foreach ($result as $user) {
        /**
         * Fetch inactive user flag [ 0, 1] value from inactive_user_flag table
         * where user id not equal to admin uid.
         * flag value 1 notification sent
         * flag value 0 notification sent
         */
        $query = \Drupal::database()->select('inactive_user_flag', 'iuf');
        $query->addField('iuf', 'value');
        $query->condition('iuf.user_id', $user->uid, '=');
        $donotblock = $query->execute()->fetchField();
        /**
         * Check user already an inactive user by querying for uid in
         * inactive_users table. If uid already exist then update else
         * insert user to inactive_users table.
         */
        $query = \Drupal::database()->select('inactive_users', 'iu');
        $query->addField('iu', 'notified_admin');
        $query->condition('iu.uid', $user->uid, '=');
        $query->condition('iu.notified_admin', 1, '=');
        $notified_admin = $query->execute()->fetchField();
        /**
         * If inactive user flag value is 1 (don't auto block user)  and
         * if not notified_admin
         * and user is created before notify time (current time - $notify_time).
         */
        if ($donotblock != 1 && $user->uid && !$notified_admin
        && ($user->created < (time() - $notify_time))) {
          // Update inactive users table. with notified admin column 1 and
          // inactive_user_notification_flag column 0.
          $query = \Drupal::database()->update('inactive_users');
          $query->fields([
            'notified_admin' => 1,
            'inactive_user_notification_flag' => 0,
          ]);
          $query->condition('uid', $user->uid);
          $update_result = $query->execute();
          // If not updated insert new record to inactive_users.
          if (!$update_result) {
            \Drupal::database()->insert('inactive_users')
              ->fields(array(
                'uid' => $user->uid,
                'notified_admin' => 1,
                'inactive_user_notification_flag' => 0,
              ))->execute();
            // Must create a new row.
          }
          $user_list[] = "$user->name ($user->mail) last active on " .
            \Drupal::service('date.formatter')->format($user->access, 'long');
        }
      }
      if (!empty($user_list)) {
	$data = ['#theme'=>'item_list','#type'=>'ul','#items'=>$user_list];
        $user_list =  \Drupal::service('renderer')->renderRoot($data);
        Inactiveuserhelper::inactive_user_mail(
            t($sitename . ' Inactive users'),
            Inactiveuserhelper::inactive_user_mail_text('notify_admin_text'),
            $notify_time, NULL, $user_list);
        $user_list = [];
      }
    }
  }

  /**
   * Notify users that their account has been inactive.
   */
  public static function notify_user_inactive_accounts($sitename, $base_url) {
    // User notify time.
    $notify_time = \Drupal::config('inactive_user.settings')->get('inactive_user_notify');

    if ($notify_time) {
      // Fetch user details from users_field_data.
      $query = \Drupal::database()->select('users_field_data', 'ufd');
      $query->fields('ufd');
      /**
       *  Active user and not an admin.
       */
      $query->condition('ufd.uid', 1, '!=');
      $query->condition('ufd.status', 1, '=');
      // not new user and access time is less than notify time.

      $and1 = new Condition('AND');
      $orandcond1 = $and1->condition('ufd.access', 0, '!=')
        ->condition('ufd.login', 0, '!=')
        ->condition('ufd.access', (time() - $notify_time), '<');

      $and2 = new Condition('AND');
      // or new user and and created time is less than notify time.
      $orandcond2 = $and2->condition('ufd.login', 0, '=')
        ->condition('ufd.created', (time() - $notify_time), '<');

      $or = new Condition('OR');
      $condition = $or->condition($orandcond1)->condition($orandcond2);
      $query->condition($condition);

      $result = $query->execute()->fetchAll();

      foreach ($result as $user) {          
        $query = \Drupal::database()->select('inactive_user_flag', 'iuf');
        $query->addField('iuf', 'value');
        $query->condition('iuf.user_id', $user->uid, '=');
        $donotblock = $query->execute()->fetchField();
        
        $query = \Drupal::database()->select('inactive_users', 'iu');
        $query->addField('iu', 'notified_user');
        $query->condition('iu.uid', $user->uid, '=');
        $query->condition('iu.notified_user', 1, '=');
        $notified_user = $query->execute()->fetchField();

        if ($donotblock != 1 && $user->uid && !$notified_user
          && ($user->created < (time() - $notify_time))) {
          // update inactive_users row with notified user.
          $query = \Drupal::database()->update('inactive_users');
          $query->fields([
            'notified_user' => 1,
          ]);
          $query->condition('uid', $user->uid);
          $affected_rows = $query->execute();
          if (!$affected_rows) {
            // insert user record to inactive user table with notified user 1.
            \Drupal::database()->insert('inactive_users')
              ->fields(array(
                'uid' => $user->uid,
                'notified_user' => 1,
              ))->execute();
          }
          $inactive_user_notify_mail_subject = \Drupal::config('inactive_user.settings')
            ->get('inactive_user_notify_mail_subject');
          if (!$inactive_user_notify_mail_subject) {
            $inactive_user_notify_mail_subject = $site_name . ' Account inactivity';
          }
          $inactive_user_notify_text = \Drupal::config('inactive_user.settings')
            ->get('inactive_user_notify_text');
          if (!$inactive_user_notify_text) {
            $inactive_user_notify_text = Inactiveuserhelper::inactive_user_mail_text('notify_text');
          }
	  else {
	    $member = User::load($user->uid);
	    $tokens = [
	      '%username',
              '%lastaccess',
              '%sitename',
              '%siteurl'
            ];
	    $token_values = [
    	      $member->getDisplayName(),
	      \Drupal::service('date.formatter')->format($member->getLastAccessedTime(), 'long'),
	      $sitename,
	      $base_url,
            ];
            $inactive_user_notify_text = str_replace($tokens, $token_values, $inactive_user_notify_text);
          }
          Inactiveuserhelper::inactive_user_mail($inactive_user_notify_mail_subject,
            $inactive_user_notify_text, $notify_time, $user, NULL);

          $url = Url::fromRoute('entity.user.edit_form', array(
            'user' => $user->uid
          ));
          $link = \Drupal::l(t('edit user'), $url, array(
            'query' => array(
              'destination' => 'admin/user/user'
            )));
          $message = "user $user->name notified of inactivity " . $user->name . $link;
          \Drupal::logger('inactive_user')->notice($message);
        }
      }
    }
  }

  /**
   * Warn users when they are about to be blocked.
   */
  public static function warn_to_block_inactive_accounts($sitename, $base_url) {
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
       */
      $query = \Drupal::database()->select('users_field_data', 'ufd');
      $query->fields('ufd');
      $and1 = new Condition('AND');
      $orandcond1 = $and1->condition('ufd.access', 0, '!=')
        ->condition('ufd.login', 0, '!=')
        ->condition('ufd.access', (time() - $block_time), '<');

      $and2 = new Condition('AND');
      $orandcond2 = $and2->condition('ufd.login', 0, '=')
        ->condition('ufd.created', (time() - $block_time), '<');
	
      $or = new Condition('OR');
      $condition = $or->condition($orandcond1)->condition($orandcond2);
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
        $donot_block = $query->execute()->fetchField();

        $query = \Drupal::database()->select('inactive_users', 'iu');
        $query->addField('iu', 'warned_user_block_timestamp');
        $query->condition('iu.uid', $user->uid, '=');
        $query->condition('iu.warned_user_block_timestamp', 0, '>');
        $warned_user_block_timestamp = $query->execute()->fetchField();

        if ($donot_block != 1 && $user->uid &&  !$warned_user_block_timestamp &&
          ($user->created < (time() - $block_time))) {
          /**
           * time + warn time = warned_user_block_timestamp block time
           */
          /**
           * time() + $warn_time
           */
          $query = \Drupal::database()->update('inactive_users');
          $query->fields([
            'warned_user_block_timestamp' => time() + $warn_time,
          ]);
          $query->condition('uid', $user->uid, '=');
          $update_result = $query->execute();

          if (!$update_result) {
            \Drupal::database()->insert('inactive_users')
              ->fields(array(
                'uid' => $user->uid,
                'warned_user_block_timestamp' => time() + $warn_time,
              ))->execute();
          }
          $inactive_user_block_warn_mail_subject = \Drupal::config('inactive_user.settings')
            ->get('inactive_user_block_warn_mail_subject');
          if (!$inactive_user_block_warn_mail_subject) {
            $inactive_user_block_warn_mail_subject = \Drupal::config('system.site')
                ->get('site_name') . 'Account inactivity';
          }
          $inactive_user_block_warn_text = \Drupal::config('inactive_user.settings')
            ->get('inactive_user_block_warn_text');
          if (!$inactive_user_block_warn_text) {
            $inactive_user_block_warn_text = Inactiveuserhelper::inactive_user_mail_text('block_warn_text');
          }
          else {
	    $member = User::load($user->uid);
                  $tokens = [
			     '%username',
                             '%lastaccess',
			     '%sitename',
			     '%period',
                             '%siteurl',
		  ];
                  $token_values = [
                                   $member->getDisplayName(),
                                   \Drupal::service('date.formatter')->format($member->getLastAccessedTime(), 'long'),
				   $sitename,
				   \Drupal::service('date.formatter')->formatInterval($block_time),
                                   $base_url,
				   ];
                  $inactive_user_block_warn_text = str_replace($tokens, $token_values, $inactive_user_block_warn_text);
          }
          Inactiveuserhelper::inactive_user_mail($inactive_user_block_warn_mail_subject,
            $inactive_user_block_warn_text, $warn_time, $user, NULL);
          $url = Url::fromRoute('entity.user.edit_form', array(
            'user' => $user->uid
          ));
          $link = \Drupal::l(t('edit user'), $url, array(
            'query' => array(
              'destination' => 'admin/user/user'
            )));

          $message = "user $user->name warned will be blocked due to inactivity " . $user->name . $link;
          \Drupal::logger('inactive_user')->notice($message);
        }
      }
    }
  }

  /**
   * Automatically block users.
   */
  public static function block_inactive_accounts($site_name, $base_url) {
    $block_time = \Drupal::config('inactive_user.settings')->get('inactive_user_auto_block');
    if ($block_time) {
      $query = \Drupal::database()->select('users_field_data', 'ufd');
      $query->fields('ufd');
      
      $and1 = new Condition('AND');
      $orandcond1 = $and1->condition('ufd.access', 0, '!=')
        ->condition('ufd.login', 0, '!=')
        ->condition('ufd.access', (time() - $block_time), '<');

      $and2 = new Condition('AND');
      $orandcond2 = $and2->condition('ufd.login', 0, '=')
        ->condition('ufd.created', (time() - $block_time), '<');
	
      $or = new Condition('OR');
      $condition = $or->condition($orandcond1)->condition($orandcond2);
      $query->condition($condition);
      // if not admin and user is not blocked yet.
      $query->condition('ufd.uid', 1, '!=');
      $query->condition('ufd.status', 0, '!=');

      // $query->range(1, 0);.
      $result = $query->execute()->fetchAll();

      foreach ($result as $user) {
        $query = \Drupal::database()->select('inactive_user_flag', 'iuf');
        $query->addField('iuf', 'value');
        $query->condition('iuf.user_id', $user->uid, '=');
        $query->range(0, 1);
        $donotblock = $query->execute()->fetchField();

        if ( $donotblock != 1) {
          /**
           * check  warned_user_block_timestamp less than currennt time
           */
          $query = \Drupal::database()->select('inactive_users', 'iuf');
          $query->addField('iuf', 'uid');
          $query->condition('iuf.uid', $user->uid, '=');
          $query->condition('iuf.warned_user_block_timestamp', time(), '<');
          $warned_user_block_timestamp = $query->execute()->fetchField();

          $inactive_user_auto_block_warn_time = \Drupal::config('inactive_user.settings')
          ->get('inactive_user_auto_block_warn');
          // inactive_user_auto_block_warn_time is 0 then block immediately.
          if ($user->uid && $inactive_user_auto_block_warn_time == '0') {
            $user_block = User::load($user->uid);
            $user_block->block();
            $user_block->save();
            // Storing blocked user time into database.
            $inactive_user_notify_block = \Drupal::config('inactive_user.settings')
              ->get('inactive_user_notify_block');
            if ($inactive_user_notify_block) {
              $query = \Drupal::database()->select('inactive_users', 'iu');
              $query->addField('iu', 'notified_user_block');
              $query->condition('iu.uid', $user->uid, '=');
              $query->condition('iu.notified_user_block', 1, '=');
              $notified_user_block = $query->execute()->fetchField();

              if (!$notified_user_block) {
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

                $inactive_user_block_mail_subject = \Drupal::config('inactive_user.settings')
                  ->get('inactive_user_block_mail_subject');
                if (!$inactive_user_block_mail_subject) {
                  $inactive_user_block_mail_subject = \Drupal::config('system.site')
                      ->get('site_name') . 'Account blocked due to inactivity';
                }

                $inactive_user_block_notify_text = \Drupal::config('inactive_user.settings')
                  ->get('inactive_user_block_notify_text');
                if (!$inactive_user_block_notify_text) {
                  $inactive_user_block_notify_text = Inactiveuserhelper::inactive_user_mail_text('block_notify_text');
                }
                else {
                  $tokens = [
			    '%username',
			    '%period',
			    '%sitename',
                    '%siteurl'
			    ];
                  $token_values = [
				   $user_block->getDisplayName(),
				   \Drupal::service('date.formatter')->format($user_block->getLastAccessedTime(), 'long'),
				   $site_name,
				   $base_url,
				   ];
                  $inactive_user_block_notify_text = str_replace($tokens, $token_values, $inactive_user_block_notify_text);
                }

                Inactiveuserhelper::inactive_user_mail($inactive_user_block_mail_subject, $inactive_user_block_notify_text, $block_time, $user, NULL);
                $url = Url::fromRoute('entity.user.edit_form', array(
                  'user' => $user->uid
                ));
                $link = \Drupal::l(t('edit user'), $url, array(
                  'query' => array(
                    'destination' => 'admin/user/user'
                  )));

                $message = "user $user->name blocked due to inactivity" . $user->name . $link;
                \Drupal::logger('inactive_user')->notice($message);
              }
            }

            $inactive_user_notify_block_admin = \Drupal::config('inactive_user.settings')
              ->get('inactive_user_notify_block_admin');
            // Notify admin.
            if ($inactive_user_notify_block_admin) {
              $query = \Drupal::database()->select('inactive_users', 'iuf');
              $query->addField('iuf', 'uid');
              $query->condition('iuf.uid', $user->uid, '=');
              $query->condition('iuf.notified_admin_block', 1, '=');
              $query->range(0, 1);
              $notified_admin_block = $query->execute()->fetchField();

              if (!$notified_admin_block) {
                $query = \Drupal::database()->update('inactive_users');
                $query->fields([
                  'notified_admin_block' => 1,
                ]);
                $query->condition('uid', $user->uid);
                $update_result = $query->execute();

                if (!$update_result) {
                  \Drupal::database()->insert('inactive_users')
                    ->fields(array(
                      'uid' => $user->uid,
                      'notified_admin_block' => 1,
                    ))->execute();
                }
                $user_list = [];
                $user_list[] = "user $user->name ($user->mail) last active on " .
                  \Drupal::service('date.formatter')->format($user->access, 'long');
              }
            }
          }
          elseif ($user->uid && $warned_user_block_timestamp && ($user->created < (time() - $block_time))) {
            // Block user after warning sent and user created time less than block time.
            $user_block = User::load($user->uid);
            $user_block->block();
            $user_block->save();
            $inactive_user_notify_block = \Drupal::config('inactive_user.settings')->get('inactive_user_notify_block');
            // Notify user.
            if ($inactive_user_notify_block) {
              $query = \Drupal::database()->select('inactive_users', 'iuf');
              $query->addField('iuf', 'notified_user_block');
              $query->condition('iuf.uid', $user->uid, '=');
              $query->condition('iuf.notified_user_block', 1, '=');
              $notified_user_block = $query->execute()->fetchField();

              if (!$notified_user_block) {
                $query = \Drupal::database()->update('inactive_users');
                $query->fields([
                  'notified_user_block' => 1,
                ]);
                $query->condition('uid', $user->uid);
                $update_result = $query->execute();

                if (!$update_result) {
                  // @\Drupal::database()->query('INSERT INTO {inactive_users} (uid, notified_user) VALUES (%d, 1)', $user->uid);
                  \Drupal::database()->insert('inactive_users')
                    ->fields(array(
                      'uid' => $user->uid,
                      'notified_user_block' => 1,
                    ))->execute();
                }

                $inactive_user_block_mail_subject = \Drupal::config('inactive_user.settings')
                  ->get('inactive_user_block_mail_subject');
                if (!$inactive_user_block_mail_subject) {
                  $inactive_user_block_mail_subject = \Drupal::config('system.site')
                      ->get('site_name') . 'Account blocked due to inactivity';
                }

                $inactive_user_block_notify_text = \Drupal::config('inactive_user.settings')
                  ->get('inactive_user_block_notify_text');
                if (!$inactive_user_block_notify_text) {
                  $inactive_user_block_notify_text = Inactiveuserhelper::inactive_user_mail_text('block_notify_text');
                }
                else {
		  $tokens = [
	            '%username',
	            '%period',
	            '%sitename',
                    '%siteurl'
	          ];
                  $token_values = [
		    $user_block->getDisplayName(),
		    \Drupal::service('date.formatter')->format($user_block->getLastAccessedTime(), 'long'),
		    $site_name,
		    $base_url,
		  ];
                  $inactive_user_block_notify_text = str_replace($tokens, $token_values, $inactive_user_block_notify_text);
                }
                Inactiveuserhelper::inactive_user_mail(
                  $inactive_user_block_mail_subject,
                  $inactive_user_block_notify_text,
                  $block_time, $user, NULL);

                $url = Url::fromRoute('entity.user.edit_form', array(
                  'user' => $user->uid
                ));

                $link = \Drupal::l(t('edit user'), $url, array(
                  'query' => array(
                    'destination' => 'admin/user/user'
                  )));
                $message = "user $user->name blocked due to inactivity" . $user->name . $link;
                \Drupal::logger('inactive_user')->notice($message);
              }
            }
            // Notify admin.
            $inactive_user_notify_block_admin = \Drupal::config('inactive_user.settings')
              ->get('inactive_user_notify_block_admin');
            if ($inactive_user_notify_block_admin) {
              $query = \Drupal::database()->select('inactive_users', 'iuf');
              $query->addField('iuf', 'notified_admin_block');
              $query->condition('iuf.uid', $user->uid, '=');
              $query->condition('iuf.notified_admin_block', 1, '=');
              $notified_admin_block = $query->execute()->fetchField();
              if (!$notified_admin_block) {
                $query = \Drupal::database()->update('inactive_users');
                $query->fields([
                  'notified_admin_block' => 1,
                ]);
                $query->condition('uid', $user->uid);
                $update_result = $query->execute();

                if (!$update_result) {
                  // @\Drupal::database()->query('INSERT INTO {inactive_users} (uid, notified_user) VALUES (%d, 1)', $user->uid);
                  \Drupal::database()->insert('inactive_users')
                    ->fields(array(
                      'uid' => $user->uid,
                      'notified_admin_block' => 1,
                    ))->execute();
                }
                $user_list = [];
                $user_list[] = "user $user->name ($user->mail) last active on " .
                  \Drupal::service('date.formatter')->format($user->access, 'long');
              }
            }
          }
        }
      }

      if (!empty($user_list)) {
	$data = ['#theme'=>'item_list','#type'=>'ul','#items'=>$user_list];
        $user_list =  \Drupal::service('renderer')->renderRoot($data);
        Inactiveuserhelper::inactive_user_mail(
            t($site_name . ' Blocked users'), Inactiveuserhelper::inactive_user_mail_text('block_notify_admin_text'), $block_time, NULL, $user_list);
        $user_list = [];
      }
    }
  }
}
