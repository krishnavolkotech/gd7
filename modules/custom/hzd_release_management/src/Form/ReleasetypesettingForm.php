<?php

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class ReleasetypesettingForm extends ConfigFormBase {

  // Protected $dateFormatter;.
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'release_type_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hzd_release_management.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    // drupal_set_title(t('Release Type settings'));.
    $form['release_vocabulary_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Release type vocabulary id'),
      '#default_value' => \Drupal::config('hzd_release_management.settings')->get('release_vocabulary_id'),
      '#description' => t('Enter Release Type Vocabulary id'),
    );
    $form['konsens_service_term_id'] = array(
      '#type' => 'textfield',
      '#title' => t('KONSENS Term id'),
      '#default_value' => \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'),
      '#description' => t('Enter KONSENS Term id'),
    );
    $form['ex_eoss_service_term_id'] = array(
      '#type' => 'textfield',
      '#title' => t('EX-EOSS Term id'),
      '#default_value' => \Drupal::config('hzd_release_management.settings')->get('ex_eoss_service_term_id'),
      '#description' => t('Enter EX-EOSS Term id'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $release_vocabulary_id = $form_state->getValue('release_vocabulary_id');
    $konsens_service_term_id = $form_state->getValue('konsens_service_term_id');
    $ex_eoss_service_term_id = $form_state->getValue('ex_eoss_service_term_id');

    \Drupal::configFactory()->getEditable('hzd_release_management.settings')
      ->set('release_vocabulary_id', $release_vocabulary_id)
      ->set('konsens_service_term_id', $konsens_service_term_id)
      ->set('ex_eoss_service_term_id', $ex_eoss_service_term_id)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
