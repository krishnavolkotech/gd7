<?php

namespace Drupal\hzd_customizations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an Planning Files form.
 */
class PlanningFilesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'planning_files';
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

    $form['mlrp'] = array(
      '#type' => 'textfield',
      '#title' => t('mlRP id'),
      '#default_value' => $config->get('mlrp') ?: NULL,
      '#description' => t('Enter mlRP nid'),
    );
    $form['test_kalender'] = array(
      '#type' => 'textfield',
      '#title' => t('Test-Kalender id'),
      '#default_value' => $config->get('test_kalender') ?: NULL,
      '#description' => t('Enter Test-Kalender nid'),
    );
    $form['transkription_pp'] = array(
      '#type' => 'textfield',
      '#title' => t('Transkription Portfolioprodukte id'),
      '#default_value' => $config->get('transkription_pp') ?: NULL,
      '#description' => t('Enter Transkription Portfolioprodukte nid'),
    );
    $form['transkription_fmk'] = array(
      '#type' => 'textfield',
      '#title' => t('Transkription FMK-Kriterium id'),
      '#default_value' => $config->get('transkription_fmk') ?: NULL,
      '#description' => t('Enter Transkription FMK-Kriterium nid'),
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
      ->set('mlrp', $form_state->getValue('mlrp'))
      ->set('test_kalender', $form_state->getValue('test_kalender'))
      ->set('transkription_pp', $form_state->getValue('transkription_pp'))
      ->set('transkription_fmk', $form_state->getValue('transkription_fmk'))
      ->save();
  }

}
