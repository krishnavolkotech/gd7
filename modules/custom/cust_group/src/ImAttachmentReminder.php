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
    $token_service = \Drupal::token();
    $ImConfig = \Drupal::config('cust_group.imattachmentreminder');
    $im_first_reminder = $ImConfig->get('im_first_reminder', FALSE);
    $im_reminder_frequency = $ImConfig->get('im_reminder_frequency', FALSE);

    // If Reminder days are not configured , then we will not send any notification.
    if($im_first_reminder == FALSE || $im_reminder_frequency == FALSE) {
      return;
    }
    $im_reminder_subject = $ImConfig->get('im_reminder_subject');
    $im_reminder_body = $ImConfig->get('im_reminder_body');
    $query = \Drupal::entityQuery('cust_group_imattachments_data')
      ->condition('changed', (time() - ($im_first_reminder * 24 * 60 * 60)), '<=');
    $im_att_ids = $query->execute();
    foreach ($im_att_ids as $imid) {
      $sendFlag = FALSE;
      $imfile = ImAttachmentsData::load($imid);
      $file_age = floor((time() - $imfile->getChangedTime()) / (24 * 60 * 60));
      //Checking First Reminder
      if ($file_age == $im_first_reminder) {
        $sendFlag = TRUE;
      }
      //Checking for Reminder frequency
      if ($file_age % $im_reminder_frequency == 0) {
        $sendFlag = TRUE;
      }

      if ($sendFlag) {
        $user = $imfile->getOwner();
        $subject = $token_service->replace($im_reminder_subject, [
          'user' => $user,
          'file' => $imfile,
        ]);
        $mail_body = $token_service->replace($im_reminder_body, [
          'user' => $user,
          'file' => $imfile,
        ]);
        $mail = $imfile->getFileOwnerEmail();
        send_immediate_notifications($subject, $mail_body, $mail, 'html');
      }
    }
  }

}
