<?php

namespace Drupal\hzd_services;
use  Drupal\hzd_services\HzdservicesStorage; 

class HzdservicesHelper { 
 /*
  *Returns TRUE if the service exists
 */
 static public function service_exist($service, $type) {
   $services = HzdservicesStorage::get_related_services($type);
   // echo $service;
   // echo '<pre>';  print_r($services);  exit;
   # return in_array(trim($service), $services);
  // echo '<pre>'; print_r(in_array(strtoupper(trim($service)), array_map('strtoupper',$services)));  exit;
   return in_array(strtoupper(trim($service)), array_map('strtoupper',$services));
 }

/**
 function send_problems_notification($mail, $subject, $body) {

   $message = drupal_mail('', '', $mail, '', $params = array(), NULL, FALSE);
   $message['subject'] = $subject;
   $message['body'] = $body;
   if (drupal_mail_send($message)) {
     \Drupal::messenger()->addMessage(t('MAIL SUCESS'), 'status');
   }else{
     \Drupal::messenger()->addMessage(t('MAIL UNSUCESS'), 'warning');
   } 
 }
*/
/**
 *  Message Mail Functionality
 */
static public function send_problems_notification($key, $to, $subject, $message_text) {
    
  $mailManager = \Drupal::service('plugin.manager.mail');
  $module  = 'problem_management';
  $params['message'] = $message_text;
  //  echo '<pre>';  print_r($params['message']); exit;
  $params['subject'] = $subject;
  //   echo '<pre>';  print_r($params['message']); exit; 
  $langcode = \Drupal::currentUser()->getPreferredLangcode();
  $send = true;
  // $params['from'] = \Drupal::config('system.site')->get('mail');
  // $message['subject'] = $params['subject'];
  // $message['body'][] = $params['message'];
  //  $message['body'][] = SafeMarkup::checkPlain($params['message']);
  // message['body'][] = $params['message'];
  //  $params['headers']['Content-Type'] = 'text/html; charset=UTF-8; format=flowed; delsp=yes';

  $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
  if (!$result['result']) {
    \Drupal::messenger()->addMessage(t('There was a problem sending your message and it was not sent.'), 'error');
  }
}

  /**
   *  Message Mail Functionality
   */
  static public function send_arbeitsanleitungen_notification($key, $to, $subject, $message_text) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'problem_management';
    $params['message'] = $message_text;
    $params['subject'] = $subject;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if (!$result['result']) {
      \Drupal::messenger()->addMessage(t('There was a problem sending your message and it was not sent.'), 'error');
    }
  }

}
