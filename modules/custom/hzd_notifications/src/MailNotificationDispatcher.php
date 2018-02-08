<?php

namespace Drupal\hzd_notifications;

use Drupal\Core\Database\Connection;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\user\Entity\User;

class MailNotificationDispatcher implements NotificationDispatcherInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a MailNotificationDispatcher object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail manager service for dispatch.
   */
  public function __construct(Connection $connection, MailManagerInterface $mailManager) {
    $this->connection = $connection;
    $this->mailManager = $mailManager;
  }

  /**
   * @inheritdoc
   */
  public function getPendingNotifications() {
    $connection = $this->connection;

    $fields = [
      'sid',
      'entity_id',
      'entity_type',
      'bundle',
      'action',
      'user_data',
    ];

    $query = $connection->select(NOTIFICATION_SCHEDULE_TABLE, 'ns');
    $notifications = $query->fields('ns', $fields)
      ->execute()->fetchAllAssoc('sid', \PDO::FETCH_ASSOC);

    return $notifications;
  }

  /**
   * @inheritdoc
   */
  public function getNotificationsAndDispatch() {
    $notifications = $this->getPendingNotifications();

    // Multiple notifications.
    foreach ($notifications as $notification_id => $notification) {
      $failed_user_data = [];

      $user_ids = unserialize($notification['user_data']);
      // hzd_user_mails checks for inactive user and if field_notifications_status_value
      // != disable , gets mail and mail preference.
      //$user_mails = hzd_user_mails($user_ids);

      if (is_array($user_ids) && count($user_ids) > 0) {

        $entity = \Drupal::entityTypeManager()
          ->getStorage($notification['entity_type'])
          ->load($notification['entity_id']);

        // Each notification subscribed by multiple users.
        foreach ($user_ids as $user_id) {
          if(empty($user_id)){
            continue;
          }
          $user = User::load($user_id);

          // For some reason, user subscribed and is deleted from system.
          if (!is_object($user)) {
            continue;
          }

          $user_active = $user->isActive();
          $user_hzd_inactive = hzd_user_inactive_status_check($user_id);
          $user_notif_status = $user->get('field_notifications_status')->value;

          /**
           * Mail is not sent for following :
           *  - If user is in blocked state.
           *  - Is user has disabled notifications.
           *  - If user is in Inactive state.
           */
          if (!$user_active || $user_notif_status == 'Disable' || $user_hzd_inactive) {
            continue;
          }

          $preference = '';
          $preference = $user->get('field_message_preference')->value;

          if (empty($preference)) {
            $preference = 'html';
          }

          $mail = $user->getEmail();
          $data[$entity->getEntityTypeId()] = $entity;
          $data['user'] = $user;
          $mailContent = getNodeMailContentFromConfig($data, $notification['action']);

          $dispatch_data['subject'] = $mailContent['subject'];
          $dispatch_data['message_text'] = $mailContent['body'];
          $dispatch_data['to'] = $mail;
          $dispatch_data['preference'] = $preference;
          $dispatch_data['attachment'] = '';

          $notification_dispatched = $this->dispatch($dispatch_data);

          /* if (!$notification_dispatched) {
            // This is not required because , we use queue which does this error
            // handling implicitly.
            //$failed_user_data[] = $user->id();
          } */
        }

      }
      // Currently, any notifications fail to user, their mail id is stored back
      // into same row. Hence we get to retry the notifications to same user once again.
      // UPDATE: This part is not used because mails are queued and it takes care of this.
      if (empty($failed_user_data)) {
        $this->markAsDispatched([$notification_id]);
      }
      else {
        $update_data = [
          $notification_id => [
            'user_data' => serialize($failed_user_data)
          ],
        ];

        $this->markAsFailed($update_data);
      }

    }
  }

  /**
   * @inheritdoc
   */
  public function dispatch(array $dispatch_data) {

    $subject = $dispatch_data['subject'];
    $message_text = $dispatch_data['message_text'];
    $to = $dispatch_data['to'];
    $preference = $dispatch_data['preference'];
    $attachment = $dispatch_data['attachment'];

    $status = $this->send_immediate_notifications($subject, $message_text, $to, $preference, $attachment);

    return $status;
  }

  /**
   * Send immediate notifications.
   */
  public function send_immediate_notifications($subject, $message_text, $to, $preference, $attachment = NULL) {
    $mailManager = $this->mailManager;
    $module = 'hzd_notifications';
    $key = 'immediate_notifications';
    $params['message'] = $message_text;
    $params['subject'] = $subject;
    $params['preference'] = $preference ? $preference : 'html';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);
  }

  /**
   * @inheritdoc
   */
  public function markAsDispatched(array $notifications) {
    $query = $this->connection->delete(NOTIFICATION_SCHEDULE_TABLE);

    $query->condition('sid', $notifications, 'IN');
    $query->execute();
  }

  /**
   * @inheritdoc
   */
  public function markAsFailed(array $notifications_data) {
    foreach ($notifications_data as $notification_id => $notification_data) {
      $query = $this->connection->update(NOTIFICATION_SCHEDULE_TABLE);
      $fields_to_be_updated = $notification_data;
      $query->fields($fields_to_be_updated);
      $query->condition('sid', $notification_id, '=');
      $query->execute();
    }
  }

}