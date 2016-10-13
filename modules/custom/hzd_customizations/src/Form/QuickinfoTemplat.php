<?php

namespace Drupal\hzd_customizations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an QuickinfoTemplat form.
 */
class QuickinfoTemplat extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quick_info_template';
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

    $form['quick_info_subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => $config->get('quick_info_subject') ?: NULL,
    );
    $form['quick_info_content'] = array(
      '#type' => 'textarea',
      '#title' => t('Content'),
      '#default_value' => $config->get('quick_info_content') ?: NULL,
    );
    $form['quick_info_footer'] = array(
      '#type' => 'textfield',
      '#title' => t('Footer'),
      '#default_value' => $config->get('quick_info_footer') ?: NULL,
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#show_restricted' => TRUE,
      ];
    }
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
      ->set('quick_info_subject', $form_state->getValue('quick_info_subject'))
      ->set('quick_info_content', $form_state->getValue('quick_info_content'))
      ->set('quick_info_footer', $form_state->getValue('quick_info_footer'))
      ->save();
  }

}
