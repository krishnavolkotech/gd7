<?php

namespace Drupal\hzd_services;
use  Drupal\hzd_services\HzdservicesStorage; 

class HzdservicesHelper { 
 /*
  *Returns TRUE if the service exists
 */
 function service_exist($service, $type) {
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
     drupal_set_message(t('MAIL SUCESS'), 'status');
   }else{
     drupal_set_message(t('MAIL UNSUCESS'), 'warning');
   } 
 }
*/
/**
 *  Message Mail Functionality
 */
function send_problems_notification($key, $to, $subject, $message_text) {
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
    drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
  }
}

}
