<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\NotificationsTemplates
 */

namespace Drupal\hzd_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class NotificationsTemplates extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notifications_template_form';
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
    $form['node_creation_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Notification form node creation subject'),
      '#default_value' => \Drupal::config('hzd_notifications.settings')->get('node_creation_subject'),
      '#description' => $this->t('Enter Subject to send notifications'),
    );
    $form['node_creation_body'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Notification form node creation body'),
      '#default_value' => \Drupal::config('hzd_notifications.settings')->get('node_creation_body')['value'],
      '#description' => $this->t('Enter body content to send notifications'),
    );

    $form['downtimes_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Downtimes Subject'),
    '#default_value' => \Drupal::config('hzd_notifications.settings')->get('downtimes_subject'),
    '#description' => $this->t('Enter Downtimes Subject to send notifications'),
    );

    $form['downtimes_body'] = array(
    '#type' => 'text_format',
    '#title' => t('Downtimes Body'),
    '#default_value' => \Drupal::config('hzd_notifications.settings')->get('downtimes_body')['value'],
    '#description' => $this->t('Enter Downtimes body content to send notifications'),
    );

    $form['quickinfo_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Quickinfo Subject'),
    '#default_value' => \Drupal::config('hzd_notifications.settings')->get('quickinfo_subject'),
    '#description' => $this->t('Enter Quickinfo Subject to send notifications'),
    );

    $form['quickinfo_body'] = array(
    '#type' => 'text_format',
    '#title' => t('Quickinfo Body'),
    '#default_value' => \Drupal::config('hzd_notifications.settings')->get('quickinfo_body')['value'],
    '#description' => $this->t('Enter Quickinfo body content to send notifications'),
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

  /*
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hzd_notifications.settings');
    $config->set('node_creation_subject', $form_state->getValue('node_creation_subject'))
           ->set('node_creation_body', $form_state->getValue('node_creation_body'))
           ->set('downtimes_subject', $form_state->getValue('downtimes_subject'))
           ->set('downtimes_body', $form_state->getValue('downtimes_body'))
           ->set('quickinfo_subject', $form_state->getValue('quickinfo_subject'))
           ->set('quickinfo_body', $form_state->getValue('quickinfo_body'))
           ->save();
  }
}
