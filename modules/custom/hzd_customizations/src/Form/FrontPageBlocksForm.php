<?php

namespace Drupal\hzd_customizations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Function to build settings form for front page blocks
 */
class FrontPageBlocksForm extends ConfigFormBase{
  
  /**
   * {@inheritdoc} 
   */
  public function getFormId() {
    return 'front_page_block_settings';
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

    $form['last_n_days_problems'] = array(
      '#type' => 'number',
      '#title' => t('Number of days'),
      '#maxlength' => 3,
      '#min' => 0,
      '#default_value' => $config->get('last_n_days_problems') ?: 0,
      '#description' => t('Enter number of days to show problems from'),
    );
    $form['last_n_days_releases'] = array(
      '#type' => 'number',
      '#title' => t('Number of days'),
      '#maxlength' => 3,
      '#min' => 0,
      '#default_value' => $config->get('last_n_days_releases') ?: 0,
      '#description' => t('Enter number of days to show releases from'),
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
      ->set('last_n_days_problems', $form_state->getValue('last_n_days_problems'))
      ->set('last_n_days_releases', $form_state->getValue('last_n_days_releases'))
      ->save();
  }
  
}
