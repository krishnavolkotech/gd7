<?php

namespace Drupal\hzd_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ALEdvNotificationForm.
 */
class ALEdvNotificationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hzd_notifications.aledvnotification',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aledv_notification_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hzd_notifications.aledvnotification');
    $form['aledv_subject_update'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject Update'),
      '#description' => $this->t('Mail subject when content is updated'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('aledv_subject_update'),
    ];
    $form['aledv_mail_footer'] = array(
      '#type' => 'text_format',
      '#title' => t('Mail footer'),
      '#description' => $this->t('Footer of the mail content'),
      '#format' => 'full_html',
      '#default_value' => $config->get('aledv_mail_footer'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('hzd_notifications.aledvnotification')
      ->set('aledv_subject_update', $form_state->getValue('aledv_subject_update'))
      ->set('aledv_mail_footer', $form_state->getValue('aledv_mail_footer')['value'])
      ->save();
  }

}
