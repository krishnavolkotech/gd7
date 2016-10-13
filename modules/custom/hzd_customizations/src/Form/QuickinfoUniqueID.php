<?php

namespace Drupal\hzd_customizations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an QuickinfoUniqueID form.
 */
class QuickinfoUniqueID extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'get_default_unique_id';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hzd_customizations.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hzd_customizations.settings');

    $form['quickinfo_default_unique_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Quickifo Default Unique Id'),
      '#default_value' => $config->get('quickinfo_default_unique_id') ?: NULL,
      '#prefix' => t('Please Enter Default Quickinfo Unique Id'),
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
    $this->config('hzd_customizations.settings')
      ->set('quickinfo_default_unique_id', $form_state->getValue('quickinfo_default_unique_id'))
      ->save();
  }

}
