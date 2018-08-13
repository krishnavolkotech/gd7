<?php

namespace Drupal\cust_group;

use Drupal\Core\Database\Connection;
use Drupal\cust_group\Entity\ImAttachmentsData;
use Drupal\Core\Render\Markup;

class ImattachmentLogger implements ImattachmentLoggerInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a NotificationScheduler object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  public function sendMail(ImAttachmentsData $imfile, $action, $im_reminder_subject, $im_reminder_body, $sid) {
    $token_service = \Drupal::token();
    $user = $imfile->getOwner();
    $subject = Markup::create($token_service->replace($im_reminder_subject, [
      'user' => $user,
      'file' => $imfile,
    ]));
    $mail_body = Markup::create($token_service->replace($im_reminder_body, [
      'user' => $user,
      'file' => $imfile,
    ]));

    $mail = $imfile->getFileOwnerEmail();
    if (!empty($mail)) {
      send_fromgroup_immediate_notifications($subject, $mail_body, $mail, 'html');
      $this->logMail($imfile->id(), $mail, $action, time(), $mail_body, $subject, $sid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logMail($fid, $mail, $action, $time, $body, $subject, $sid) {
    $connection = $this->connection;
    $table = 'im_attachment_notifications_log';

    if (is_null($sid)) {
      $fields = [
        'fid' => $fid,
        'author_email' => $mail,
        'action' => $action,
        'time' => $time,
        'body' => $body,
        'subject' => $subject
      ];
      $connection->insert($table)->fields($fields)->execute();
    } else {
      $connection->update($table)
        ->fields([
          'fid' => $fid,
          'author_email' => $mail,
          'action' => $action,
          'time' => $time,
          'body' => $body,
          'subject' => $subject
        ])
        ->condition('sid', $sid, '=')
        ->execute();
    }
  }
}