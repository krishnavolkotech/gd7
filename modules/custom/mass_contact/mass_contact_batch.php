<?php

namespace Drupal\mass_contact;

use Drupal\node\Entity\Node;

class MassMail {

  public static function sendMail($mail, $subject, $body, &$context) {
    $mailManager = \Drupal::service('plugin.manager.mail');

    $module = 'mass_contact';
    $to = $mail;
    $params['message'] = $body;
    $params['subject'] = $subject;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $key = 'mass_contact_immediate';
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] != true) {
      drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
    }
    else {
      drupal_set_message(t('Your message has been sent.'));
    }
  }

  static function sendMailFinished($success, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    /*if ($success) {
      $message = \Drupal::translation()->formatPlural(
          count($results), 'One post processed.', '@count posts processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);*/
    drupal_set_message(t('Successfuly sent mail\'s'));
  }

}
