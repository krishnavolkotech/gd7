<?php

/**
 * @file
 * Contains \Drupal\hzd_release_management\Form\ReleasedocumentsettingForm
 */

namespace Drupal\hzd_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class ArbeitsanleitungendocumentsettingForm extends ConfigFormBase {

  //  protected $dateFormatter;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arbeitsanleitungen_document_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hzd_notifications.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['arbeitsanleitungen_not_import'] = array(
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#default_value' => \Drupal::config('hzd_notifications.settings')->get('arbeitsanleitungen_not_import'),
      '#description' => t('The mail will be sent to above given mail address and also provided multiple email address by comma seperated.'),
    );

    $form['arb_success_download_text'] = array(
      '#type' => 'text_format',
      '#title' => t('Success Download Text'),
      '#default_value' => \Drupal::config('hzd_notifications.settings')->get('arb_success_download_text')['value'],
      '#description' => t('When documentation download is successful. Above text will send as mail.'),
    );

    $form['arb_failed_download_text'] = array(
      '#type' => 'text_format',
      '#title' => t('Failed Download Text'),
      '#default_value' => \Drupal::config('hzd_notifications.settings')->get('arb_failed_download_text')['value'],
      '#description' => t('When documentation download is failed. Above text will send as mail.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $success_download_text = $form_state->getValue('arb_success_download_text');
    $failed_download_text = $form_state->getValue('arb_failed_download_text');
    $release_not_import = $form_state->getValue('arbeitsanleitungen_not_import');

    \Drupal::configFactory()->getEditable('hzd_notifications.settings')
      ->set('arb_success_download_text', $success_download_text)
      ->set('arb_failed_download_text', $failed_download_text)
      ->set('arbeitsanleitungen_not_import', $release_not_import)
      ->save();
    parent::submitForm($form, $form_state);
  }
}
