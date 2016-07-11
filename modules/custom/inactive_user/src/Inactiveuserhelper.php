<?php

namespace Drupal\inactive_user;

class Inactiveuserhelper { 
  
/**
 * Some default e-mail notification strings.
 */
static function inactive_user_mail_text($message) {
  switch ($message) {
    case 'notify_text':
      return t("Hello %username,\n\n  We haven't seen you at %sitename since %lastaccess, and we miss you!  Please come back and visit us soon at %siteurl.\n\nSincerely,\n  %sitename team");

    case 'notify_admin_text':
      return t("Hello,\n\n  This automatic notification is to inform you that the following users haven't been seen on %sitename for more than %period:\n\n%userlist");

    case 'block_warn_text':
      return t("Hello %username,\n\n  We haven't seen you at %sitename since %lastaccess, and we miss you!  This automatic message is to warn you that your account will be disabled in %period unless you come back and visit us before that time.\n\n  Please visit us at %siteurl.\n\nSincerely,\n  %sitename team");

    case 'block_notify_text':
      return t("Hello %username,\n\n  This automatic message is to notify you that your account on %sitename has been automatically disabled due to no activity for more than %period.\n\n  Please visit us at %siteurl to have your account re-enabled.\n\nSincerely,\n  %sitename team");

    case 'block_notify_admin_text':
      return t("Hello,\n\n  This automatic notification is to inform you that the following users have been automatically blocked due to inactivity on %sitename for more than %period:\n\n%userlist");

    case 'delete_warn_text':
      return t("Hello %username,\n\n  We haven't seen you at %sitename since %lastaccess, and we miss you!  This automatic message is to warn you that your account will be completely removed in %period unless you come back and visit us before that time.\n\n  Please visit us at %siteurl.\n\nSincerely,\n  %sitename team");

    case 'delete_notify_text':
      return t("Hello %username,\n\n  This automatic message is to notify you that your account on %sitename has been automatically removed due to no activity for more than %period.\n\n  Please visit us at %siteurl if you would like to create a new account.\n\nSincerely,\n  %sitename team");

    case 'delete_notify_admin_text':
      return t("Hello,\n\n  This automatic notification is to inform you that the following users have been automatically deleted due to inactivity on %sitename for more than %period:\n\n%userlist");
  }
}


/**
 * Wrapper for user_mail.
 */
static function inactive_user_mail($subject, $message, $period, $user = NULL, $user_list = NULL) {
  global $base_url;
  if ($user_list) {
    $to = _inactive_user_admin_mail();
    $variables = array(
      '%period' => format_interval($period),
      '%sitename' => variable_get('site_name', 'drupal'),
      '%siteurl' => l($base_url, $base_url),
      "%userlist" => $user_list,
    );
  }
  elseif (isset($user->uid)) {
    $to = $user->mail;
    $variables = array(
      '%username' => $user->name,
      '%useremail' => $user->mail,
      '%lastaccess' => empty($user->access) ? t('never') : format_date($user->access, 'custom', 'M d, Y'),
      '%period' => format_interval($period),
      '%sitename' => variable_get('site_name', 'drupal'),
      '%siteurl' => l($base_url, $base_url),
    );
  }
  if (isset($to)) {
    $from = variable_get('site_mail', ini_get('sendmail_from'));
    $headers = array(
      'Reply-to' => $from,
      'Return-path' => "<$from>",
      'Errors-to' => $from,
    );
    $recipients = explode(',', $to);
    foreach ($recipients as $recipient) {
      $recipient = trim($recipient);
      $params = array(
        'subject' => $subject,
        'message' => strtr($message, $variables),
        'headers' => $headers,
      );
      $users = user_load_multiple(array(), array('mail' => $recipient));
      $user = array_shift($users);
      $language = isset($user->uid) ? user_preferred_language($user) : language_default();
//  drupal_mail('example', 'notice', $account->mail, user_preferred_langcode($account), $params);
// 
      drupal_mail('inactive_user', 'inactive_user_notice', $recipient, $language, $params, $from, TRUE);
    }
  }
 }
}
