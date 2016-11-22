<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\hzd_customizations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Description of DowntimesMailTemplate
 *
 * @author sandeep
 */
class DowntimesMailTemplate extends ConfigFormBase {

  //put your code here
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'downtimes_mail_template';
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
    $data = $config->get('downtimes_mail_template');
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => $data['subject'] ? : NULL,
    );
    $form['mail_content'] = array(
      '#type' => 'textarea',
      '#title' => t('Content'),
      '#default_value' => $data['mail_content'] ? : NULL,
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
    $data = ['subject' => $form_state->getValue('subject'), 'mail_content' => $form_state->getValue('mail_content')];
    $this->config('hzd_customizations.settings')
        ->set('downtimes_mail_template', $data)
        ->save();
  }

}
