<?php

namespace Drupal\hzd_notifications;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Mail\MailManagerInterface;

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
      'entity',
      'bundle',
      'action',
      'user_data',
    ];
    $query = $connection->select('notifications_scheduled', 'ns');
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
      $user_ids = unserialize($notification['user_data']);
      $user_mails = hzd_user_mails($user_ids);

      if (is_array($user_mails) && count($user_mails) > 0) {

        $entity = \Drupal::entityTypeManager()
          ->getStorage($notification['entity'])
          ->load($notification['entity_id']);

        // Each notification subscribed by multiple users.
        foreach ($user_mails as $values) {
          $preference = $values->field_message_preference_value ? $values->field_message_preference_value : 'html';
          $mail = $values->mail;
          $data['node'] = $entity;
          $data['user'] = user_load_by_mail($mail);
          $mailContent = getNodeMailContentFromConfig($data, $notification['action']);

          $dispatch_data['subject'] = $mailContent['subject'];
          $dispatch_data['message_text'] = $mailContent['body'];
          $dispatch_data['to'] = $mail;
          $dispatch_data['preference'] = $preference;
          $dispatch_data['attachment'] = '';

          $this->dispatch($dispatch_data);
        }
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

    $this->send_immediate_notifications($subject, $message_text, $to, $preference, $attachment);
  }

  /**
   * Send immediate notifications.
   */
  public function send_immediate_notifications($subject, $message_text, $to, $preference, $attachment = NULL) {
    $mailManager = $this->mailManager;
    $module = 'hzd_release_management';
    $key = 'immediate_notifications';
    $params['message'] = $message_text;
    $params['subject'] = $subject;
    $params['preference'] = $preference ? $preference : 'html';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;
    $token_service = \Drupal::token();

    foreach (explode(',', trim($to, ',')) as $userMail) {
      $userEntity = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => trim($userMail)]);
      if ($userEntity && reset($userEntity)->get('field_notifications_status')->value !== 'Disable' && reset($userEntity)->isActive()
        && !hzd_user_inactive_status_check(reset($userEntity)->id())
      ) {
        $result = $mailManager->mail($module, $key, $userMail, $langcode, $params, NULL, $send);
      }
    }
    /*if ($result['result'] != TRUE) {
      drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
    }*/
  }

}