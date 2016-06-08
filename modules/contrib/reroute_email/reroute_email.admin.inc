<?php

/**
 * @file
 * Reroute Email admin configuration functions.
 */

/**
 * Settings form.
 */
function reroute_email_settings() {
  // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
$form[REROUTE_EMAIL_ENABLE] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Enable rerouting'),
    '#default_value' => \Drupal::config('reroute_email.settings')->get(REROUTE_EMAIL_ENABLE),
    '#description'   => t('Check this box if you want to enable email rerouting. Uncheck to disable rerouting.'),
  );

  // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
$form[REROUTE_EMAIL_ADDRESS] = array(
    '#type'          => 'textfield',
    '#title'         => t('Email addresses'),
    '#default_value' => \Drupal::config('reroute_email.settings')->get(REROUTE_EMAIL_ADDRESS),
    '#description'   => 'Provide a space, comma, or semicolon-delimited list of email addresses to pass through. Every destination email address which is not on this list will be rerouted to the first address on the list.<br/> If the field is empty and no value is provided, <strong>all outgoing emails would be aborted</strong> and the email would be recorded in the <a href="@dblog">recent log entries</a>.', array('@dblog' => \Drupal\Core\Url::fromRoute('dblog.overview')),
    '#states' => array(
      'visible' => array(':input[name=reroute_email_enable]' => array('checked' => TRUE)),
    ),
  );

  // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
$form[REROUTE_EMAIL_ENABLE_MESSAGE] = array(
    '#type' => 'checkbox',
    '#title' => t('Show rerouting description in mail body'),
    '#default_value' => \Drupal::config('reroute_email.settings')->get(REROUTE_EMAIL_ENABLE_MESSAGE),
    '#description' => t('Check this box if you want a message to be inserted into the email body when the mail is being rerouted. Otherwise, SMTP headers will be used to describe the rerouting. If sending rich-text email, leave this unchecked so that the body of the email will not be disturbed.'),
    '#states' => array(
      'visible' => array(':input[name=reroute_email_enable]' => array('checked' => TRUE)),
    ),
  );


  return system_settings_form($form);
}

/**
 * Validation callback for reroute_email_settings() form.
 */
function reroute_email_settings_validate($form, $form_state) {
  if ($form_state['values']['reroute_email_enable'] == TRUE) {
    // Allow splitting emails by space, comma, semicolon.
    $addresslist = preg_split(REROUTE_EMAIL_EMAIL_SPLIT_RE, $form_state['values']['reroute_email_address'], -1, PREG_SPLIT_NO_EMPTY);
    foreach ($addresslist as $address) {
      if (!valid_email_address($address)) {
        form_set_error('reroute_email_address', t('@address is not a valid email address', array('@address' => $address)));
      }
    }
  }
}

/**
 * Form for sending test messages.
 */
function reroute_email_test_email_form() {
  return array(
    'addresses' => array(
      '#type' => 'fieldset',
      '#description' => t('Email addresses are not validated: any valid or invalid email address format could be submitted.'),
      'to' => array(
        '#type' => 'textfield',
        '#title' => t('To'),
        '#required' => TRUE,
      ),
      'cc' => array(
        '#type' => 'textfield',
        '#title' => t('Cc'),
      ),
      'bcc' => array(
        '#type' => 'textfield',
        '#title' => t('Bcc'),
      ),
    ),
    'subject' => array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => t('Reroute Email Test'),
    ),
    'body' => array(
      '#type' => 'textarea',
      '#title' => t('Body'),
    ),
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Send email'),
    ),
  );
}

/**
 * Submit handler for email test.
 */
function reroute_email_test_email_form_submit(&$form, &$form_state) {
  $from = "devarajjohnson23@gmail.com";
  $to = $form_state['values']['to'];
  $param_keys = array('cc', 'bcc', 'subject', 'body');
  $params = array_intersect_key($form_state['values'], array_flip($param_keys));
  $langcode = \Drupal::languageManager()->getDefaultLanguage();
    
  // Send email with drupal_mail.
    $message =  \Drupal::service('plugin.manager.mail')->mail('reroute_email', 'test_email_form', $to, $langcode, $params, $from);

  //$message = drupal_mail('reroute_email', 'test_email_form', $to, language_default(), $params);
  if (!empty($message['result'])) {
    drupal_set_message(t("Test email submitted for delivery."));
  }
}
