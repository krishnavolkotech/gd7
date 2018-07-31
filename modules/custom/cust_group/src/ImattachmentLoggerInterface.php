<?php

namespace Drupal\cust_group;

use Drupal\cust_group\Entity\ImAttachmentsData;

/**
 * Schedules the notification for actions performed on an entity.
 */
interface ImattachmentLoggerInterface {

  /**
   * @param $fid
   * @param $mail
   * @param $action
   * @param $time
   * @param $body
   * @param $subject
   * @return mixed
   */
  public function logMail($fid, $mail, $action, $time, $body, $subject, $sid);

  /**
   * @param ImAttachmentsData $imfile
   * @param $action
   * @param $im_reminder_subject
   * @param $im_reminder_body
   * @return mixed
   */
  public function sendMail(ImAttachmentsData $imfile, $action, $im_reminder_subject, $im_reminder_body, $sid);
}