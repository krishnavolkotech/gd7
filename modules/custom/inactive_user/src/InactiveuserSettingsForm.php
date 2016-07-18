<?php

// \Drupal::config('hello.settings')->get('things');
// https://www.commercialprogression.com/post/drupal-8-oop-part-2-creating-admin-form
// https://www.drupal.org/node/2206607
// http://drupal8cmi.org/drupal-8-hello-configuration-management
namespace Drupal\inactive_user;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

// Use Drupal\Core\Datetime\DateFormatter;.
/**
 * Configure inactive_user settings for this site.
 */
class InactiveuserSettingsForm extends ConfigFormBase {

  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inactive_user_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'inactive_user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('inactive_user.settings');
    $period = array();
    $period['0'] = 'disabled';

    $period_time_intervals = array(
      604800,
      1209600,
      1814400,
      2419200,
      2592000,
      7776000,
      15552000,
      23328000,
      31536000,
      47088000,
      63072000,
    );

    foreach ($period_time_intervals as $period_time) {
      $period = $period + array($period_time => \Drupal::service('date.formatter')->formatInterval($period_time));
    }

    $warn_period_time_intervals = array(
      86400,
      172800,
      259200,
      604800,
      1209600,
      1814400,
      2592000,
    );
    $warn_period = array();
    $warn_period['0'] = 'disabled';
    foreach ($warn_period_time_intervals as $warning_period_time) {
      $warn_period = $warn_period + array($warning_period_time => \Drupal::service('date.formatter')->formatInterval($warning_period_time));
    }

    $mail_variables = ' %username, %useremail, %lastaccess, %period, %sitename, %siteurl';

    // Set administrator e-mail.
    $form['inactive_user_admin_email_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Administrator e-mail'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['inactive_user_admin_email_fieldset']['inactive_user_admin_email'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('E-mail addresses'),
      '#default_value' => InactiveuserStorage::inactive_user_admin_mail(),
      '#description' => $this->t('Supply a comma-separated list of e-mail addresses that will receive administrator alerts. Spaces between addresses are allowed.'),
      '#maxlength' => 256,
      '#required' => TRUE,
    );

    // Inactive user notification.
    $form['inactive_user_notification'] = array(
      '#type' => 'fieldset',
      '#title' => t('Inactive user notification'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['inactive_user_notification']['inactive_user_notify_admin'] = array(
      '#type' => 'select',
      '#title' => t("Notify administrator when a user hasn't logged in for more than"),
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_notify_admin'),
      '#options' => $period,
      '#description' => $this->t("Generate an email to notify the site administrator that a user account hasn't been used for longer than the specified amount of time.  Requires crontab."),
    );

    $form['inactive_user_notification']['inactive_user_notify'] = array(
      '#type' => 'select',
      '#title' => $this->t("Notify users when they haven't logged in for more than"),
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_notify'),
      '#options' => $period,
      '#description' => t("Generate an email to notify users when they haven't used their account for longer than the specified amount of time.  Requires crontab."),
    );

    $form['inactive_user_notification']['inactive_user_notify_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body of user notification e-mail'),
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_notify_text'),
    // '#default_value' => $inactive_user_notify,.
      '#cols' => 70,
      '#rows' => 10,
      '#description' => $this->t('Customize the body of the notification e-mail sent to the user.') . ' ' . t('Available variables are:') . $mail_variables,
      '#required' => TRUE,
    );

    // Automatically block inactive users.
    $form['block_inactive_user'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Automatically block inactive users'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['block_inactive_user']['inactive_user_auto_block_warn'] = array(
      '#type' => 'select',
      '#title' => $this->t('Warn users before they are blocked'),
    // '#default_value' => variable_get('inactive_user_auto_block_warn', 0),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_auto_block_warn'),
      '#options' => $warn_period,
      '#description' => $this->t('Generate an email to notify a user that his/her account is about to be blocked.'),
    );

    $form['block_inactive_user']['inactive_user_block_warn_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body of user warning e-mail'),
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_block_warn_text'),
      '#cols' => 70,
      '#rows' => 10,
      '#description' => $this->t('Customize the body of the notification e-mail sent to the user when their account is about to be blocked.') . ' ' . t('Available variables are:') . $mail_variables,
      '#required' => TRUE,
    );
    $form['block_inactive_user']['inactive_user_auto_block'] = array(
      '#type' => 'select',
    // For visual clarity.
      '#prefix' => '<div><hr></div>',
      '#title' => t("Block users who haven't logged in for more than"),
    // '#default_value' => variable_get('inactive_user_auto_block', 0),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_auto_block'),
      '#options' => $period,
      '#description' => t("Automatically block user accounts that haven't been used in the specified amount of time.  Requires crontab."),
    );
    $form['block_inactive_user']['inactive_user_notify_block'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user'),
    // '#default_value' => variable_get('inactive_user_notify_block', 0),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_notify_block'),
      '#description' => $this->t('Generate an email to notify a user that his/her account has been automatically blocked.'),
    );
    /**
 * $block_notify_text = \Drupal::config('inactive_user.settings')->get('inactive_user_block_notify_text');
 * if (!$block_notify_text) {
 * $block_notify_text =  Inactiveuserhelper::inactive_user_mail_text('block_notify_text');
 * }
*/
    $form['block_inactive_user']['inactive_user_block_notify_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body of blocked user account e-mail'),
    // '#default_value' => variable_get('inactive_user_block_notify_text', _inactive_user_mail_text('block_notify_text')),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_block_notify_text'),
      '#cols' => 70,
      '#rows' => 10,
      '#description' => $this->t('Customize the body of the notification e-mail sent to the user when their account has been blocked.') . ' ' . t('Available variables are:') . $mail_variables,
      '#required' => TRUE,
    );
    $form['block_inactive_user']['inactive_user_notify_block_admin'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify administrator'),
    // '#default_value' => variable_get('inactive_user_notify_block_admin', 0),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_notify_block_admin'),
      '#description' => $this->t('Generate an email to notify the site administrator when a user is automatically blocked.'),
    );

    // Automatically delete inactive users.
    $form['delete_inactive_user'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Automatically delete inactive users'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['delete_inactive_user']['inactive_user_auto_delete_warn'] = array(
      '#type' => 'select',
      '#title' => $this->t('Warn users before they are deleted'),
    // '#default_value' => variable_get('inactive_user_auto_delete_warn', 0),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_auto_delete_warn'),
      '#options' => $warn_period,
      '#description' => $this->t('Generate an email to notify a user that his/her account is about to be deleted.'),
    );

    $form['delete_inactive_user']['inactive_user_delete_warn_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body of user warning e-mail'),
    // '#default_value' => variable_get('inactive_user_delete_warn_text', _inactive_user_mail_text('delete_warn_text')),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_delete_warn_text'),
      '#cols' => 70,
      '#rows' => 10,
      '#description' => $this->t('Customize the body of the notification e-mail sent to the user when their account is about to be deleted.') . ' ' . t('Available variables are:') . $mail_variables,
      '#required' => TRUE,
    );
    $form['delete_inactive_user']['inactive_user_auto_delete'] = array(
      '#type' => 'select',
    // For visual clarity.
      '#prefix' => '<div><hr></div>',
      '#title' => $this->t("Delete users who haven't logged in for more than"),
    // '#default_value' => variable_get('inactive_user_auto_delete', 0),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_auto_delete'),
      '#options' => $period,
      '#description' => $this->t("Automatically delete user accounts that haven't been used in the specified amount of time.  Warning, user accounts are permanently deleted, with no ability to undo the action!  Requires crontab."),
    );

    $form['delete_inactive_user']['inactive_user_preserve_content'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Preserve users that own site content'),
    // '#default_value' => variable_get('inactive_user_preserve_content', 1),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_preserve_content'),
      '#description' => $this->t('Select this option to never delete users that own site content.  If you delete a user that owns content on the site, such as a user that created a node or left a comment, the content will no longer be available via the normal Drupal user interface.  That is, if a user creates a node or leaves a comment, then the user is deleted, the node and/or comment will no longer be accesible even though it will still be in the database.'),
    );
    $form['delete_inactive_user']['inactive_user_notify_delete'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user'),
    // '#default_value' => variable_get('inactive_user_notify_delete', 0),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_notify_delete'),
      '#description' => $this->t('Generate an email to notify a user that his/her account has been automatically deleted.'),
    );

    $form['delete_inactive_user']['inactive_user_delete_notify_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body of deleted user account e-mail'),
    // '#default_value' => variable_get('inactive_user_delete_notify_text', _inactive_user_mail_text('delete_notify_text')),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_delete_notify_text'),
      '#cols' => 70,
      '#rows' => 10,
      '#description' => $this->t('Customize the body of the notification e-mail sent to the user when their account has been deleted.') . ' ' . t('Available variables are:') . $mail_variables,
      '#required' => TRUE,
    );

    $form['delete_inactive_user']['inactive_user_notify_delete_admin'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify administrator'),
    // '#default_value' => variable_get('inactive_user_notify_delete_admin', 0),.
      '#default_value' => \Drupal::config('inactive_user.settings')->get('inactive_user_notify_delete_admin'),
      '#description' => $this->t('Generate an email to notify the site administrator when a user is automatically deleted.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Verifies the admin email submission is a properly formatted address.
   */
  function inactive_user_validate($form, &$form_state) {
    $valid_email = $form_state->getValue('inactive_user_admin_email');
    $mails = explode(',', $valid_email);
    $count = 0;
    foreach ($mails as $mail) {
      if ($mail && !valid_email_address(trim($mail))) {
        $invalid[] = $mail;
        $count++;
      }
    }
    if ($count == 1) {
      form_set_error('inactive_user_admin_email', $form_state, t('%mail is not a valid e-mail address', array('%mail' => $invalid[0])));
      // form_set_error('inactive_user_admin_email', t('%mail is not a valid e-mail address', array('%mail' => $invalid[0])));.
    }
    elseif ($count > 1) {
      form_set_error('inactive_user_admin_email', $form_state, t('The following e-mail addresses are invalid: %mail', array('%mail' => implode(', ', $invalid))));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('inactive_user.settings')
      ->set('inactive_user_admin_email', $form_state->getValue('inactive_user_admin_email'))
      ->set('inactive_user_notify_admin', $form_state->getValue('inactive_user_notify_admin'))
      ->set('inactive_user_notify', $form_state->getValue('inactive_user_notify'))
      ->set('inactive_user_notify_text', $form_state->getValue('inactive_user_notify_text'))
      ->set('inactive_user_auto_block_warn', $form_state->getValue('inactive_user_auto_block_warn'))
      ->set('inactive_user_block_warn_text', $form_state->getValue('inactive_user_block_warn_text'))
      ->set('inactive_user_auto_block', $form_state->getValue('inactive_user_auto_block'))
      ->set('inactive_user_block_notify_text', $form_state->getValue('inactive_user_block_notify_text'))
      ->set('inactive_user_notify_block', $form_state->getValue('inactive_user_notify_block'))
      ->set('inactive_user_notify_block_admin', $form_state->getValue('inactive_user_notify_block_admin'))
      ->set('inactive_user_delete_warn_text', $form_state->getValue('inactive_user_delete_warn_text'))
      ->set('inactive_user_auto_delete', $form_state->getValue('inactive_user_auto_delete'))
      ->set('inactive_user_preserve_content', $form_state->getValue('inactive_user_preserve_content'))
      ->set('inactive_user_notify_delete', $form_state->getValue('inactive_user_notify_delete'))
      ->set('inactive_user_delete_notify_text', $form_state->getValue('inactive_user_delete_notify_text'))
      ->set('inactive_user_notify_delete_admin', $form_state->getValue('inactive_user_notify_delete_admin'))
      ->set('inactive_user_auto_delete_warn', $form_state->getValue('inactive_user_auto_delete_warn'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
