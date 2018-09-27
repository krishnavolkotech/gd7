<?php

/*
 * UpdateProblemsNotificationforAllServices
 */

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\HzdNotificationsHelper;

class UpdateGroupNotificationforAllGroup extends FormBase {

  public function getFormId() {
    return 'update_group_notification_for_all_group';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Update Group Notifications'
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    self::prepareBatch();
  }

  static public function prepareBatch() {
    $data = \Drupal::database()->select('group_notifications_user_default_interval', 'gndi')
      ->fields('gndi', ['uid', 'group_id'])
      ->condition('default_send_interval', -1, '=')
      ->condition('uid', 0, '!=')
      ->execute()
      ->fetchAllKeyed(0, 1);

    foreach ($data as $uid => $gid) {
      $group = \Drupal\group\Entity\Group::load($gid);
      $group_name = $group->label();
      if (isset($group_name)) {
        $operations[] = [
          '\Drupal\cust_group\Form\UpdateGroupNotificationforAllGroup::update',
          [
            $uid,
            $group,
            $gid,
            t('(Group @operation)', ['@operation' => $group_name]),
          ],
        ];
      }
    }
    $batch = [
      'title' => t('Updating Group Notification of @num Groups', ['@num' => count($data)]),
      'operations' => $operations,
      'finished' => '\Drupal\cust_group\Form\UpdateGroupNotificationforAllGroup::finishedCallBack',
    ];
    return batch_set($batch);
  }

  /**
   * @param $uid
   * @param $gid
   * @param $operation_details
   * @param $context
   */
  static public function update($uid, $group, $gid, $operation_details, &$context) {

    $account = \Drupal\user\Entity\User::load($uid);
    if (isset($account) && $account->isActive()) {
      $is_member = $group->getMember($account);
      if (!$is_member) {
        $context['results'][$uid][] = $group->label() . ':InActive:' . $gid;
        self::delete_uid_from_notification_table($uid, $gid);
      }

    } else {
      $context['results'][$uid][] = $group->label() . ':NotExist:' . $gid;
      self::delete_uid_from_notification_table($uid, $gid);
    }

    $context['message'] = t('Running Batch on uid "@uid" for @details',
      ['@uid' => $uid, '@details' => $operation_details]
    );
  }

  /**
   * @param $uid
   * @param $gid
   */
  function delete_uid_from_notification_table($uid, $gid) {
//    if (isset($uid) && isset($gid)) {
//      $query = \Drupal::database()
//        ->delete('group_notifications_user_default_interval')
//        ->condition('group_id', $gid)
//        ->condition('uid', $uid)
//        ->execute();
//    }
//
//    $groupNotifications = \Drupal::database()->select('group_notifications', 'gn')
//      ->fields('gn', ['id', 'uids'])
//      ->condition('group_id', $gid)
//      ->condition('send_interval', 0)
//      ->execute()
//      ->fetchAll();
//    foreach ($groupNotifications as $id) {
//      $users = array_values((array)unserialize($id->uids));
//      if (count($users) > 0) {
//        $key = array_search($uid, (array)$users);
//        if ($key !== FALSE) {
//          $newusers = array_diff($users, [$uid]);
//          \Drupal::database()->update('group_notifications')
//            ->fields(['uids' => serialize($newusers)])
//            ->condition('id', $id->id)
//            ->execute();
//        }
//      }
//    }
  }

  public static function finishedCallBack($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed...
      $messenger->addMessage(t('@count groups processed.', ['@count' => count($results)]));
      $messenger->addMessage(t('The final result was "%final"', ['%final' => print_r($results, TRUE)]));
    } else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }
//
//  public static function finishedCallBack($success, $results, $operations) {
//    if ($success) {
//      drupal_set_message(\Drupal::translation()->translate('Updated Group notifications successfully'));
//    }
//  }

}
