<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\cust_group;

use Drupal\cust_group\Entity\ImAttachmentsData;

/**
 * Description of ImAttachmentReminder
 *
 * @author Abhi
 */
class ImAttachmentReminder {

  static public function PrepareMailsToImAuthers() {
    $ImConfig = \Drupal::config('cust_group.imattachmentreminder');
    $im_first_reminder = $ImConfig->get('im_first_reminder', FALSE);
    $im_reminder_frequency = $ImConfig->get('im_reminder_frequency', FALSE);

    // If Reminder days are not configured , then we will not send any notification.
    if ($im_first_reminder == FALSE || $im_reminder_frequency == FALSE) {
      return;
    }
    $im_reminder_subject = $ImConfig->get('im_reminder_subject');
    $im_reminder_body = $ImConfig->get('im_reminder_body');
    $query = \Drupal::entityQuery('cust_group_imattachments_data')
      ->condition('created', (time() - ($im_first_reminder * 24 * 60 * 60)), '<=')
      ->sort('created', 'ASC');

    $im_att_ids = $query->execute();
    foreach ($im_att_ids as $imid) {
      $connection = \Drupal::database();
      $result = $connection->query("SELECT * FROM {im_attachment_notifications_log} WHERE fid = :fid", [
        ':fid' => $imid,
      ])->fetchAssoc();
      if (empty($result['sid'])) {
        $imfile = ImAttachmentsData::load($imid);
        $log = new ImattachmentLogger($connection);
        $log->sendMail($imfile, 'First', $im_reminder_subject, $im_reminder_body, NULL);
      } else {
        $remtime = time() - ($im_reminder_frequency * 24 * 60 * 60);
        if ($result['time'] <= $remtime) {
          $imfile = ImAttachmentsData::load($imid);
          $updatelog = new ImattachmentLogger($connection);
          $updatelog->sendMail($imfile, 'Reminder', $im_reminder_subject, $im_reminder_body, $result['sid']);
        }
      }
    }

  }

}
