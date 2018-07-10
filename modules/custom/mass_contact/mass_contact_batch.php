<?php

namespace Drupal\mass_contact;

use Drupal\Core\Render\Markup;

class MassMail {

  public static function sendMail($group_member_id, $subject, $raw_body, &$context) {
    $token_service = \Drupal::token();
    $user = \Drupal\user\Entity\User::load($group_member_id);
    $mailManager = \Drupal::service('plugin.manager.mail');
    $footer = \Drupal::config('mass_contact.settings')->get('footer');

    $mail_body = [
      '#type'=>'inline_template',
      '#template' => '{% for text in items %}{{ text }}{% endfor %}',
      '#context' => [
        'items'=>[
          Markup::create($raw_body),
          Markup::create($token_service->replace($footer['value'], [
            'user' => $user,
          ])),
        ]
      ]
    ];
    $body = \Drupal::service('renderer')->render($mail_body);

    $module = 'mass_contact';
    $to = $user->getEmail();

    $params['message'] = $body;
    $params['subject'] = $subject;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $key = 'mass_contact_immediate';
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result']) {
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
