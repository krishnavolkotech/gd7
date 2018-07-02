<?php

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IMAttachmentReminderForm.
 */
class IMAttachmentReminderForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cust_group.imattachmentreminder',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'im_attachment_reminder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cust_group.imattachmentreminder');
    $form['im_first_reminder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First reminder'),
      '#description' => $this->t('Send first reminder after: &lt;x&gt; days'),
      '#maxlength' => 4,
      '#size' => 64,
      '#default_value' => $config->get('im_first_reminder'),
    ];
    $form['im_reminder_frequency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reminder frequency'),
      '#description' => $this->t('Send following reminders every: &lt;y&gt; days'),
      '#maxlength' => 4,
      '#size' => 64,
      '#default_value' => $config->get('im_reminder_frequency'),
    ];
    $form['im_reminder_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reminder subject'),
      '#description' => $this->t('Reminder subject'),
      '#maxlength' => 255,
      '#size' => 255,
      '#default_value' => $config->get('im_reminder_subject'),
    ];
    $form['im_reminder_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Reminder body'),
      '#default_value' => $config->get('im_reminder_body'),
      '#format' => 'full_html',
    ];
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

    $this->config('cust_group.imattachmentreminder')
      ->set('im_first_reminder', $form_state->getValue('im_first_reminder'))
      ->set('im_reminder_frequency', $form_state->getValue('im_reminder_frequency'))
      ->set('im_reminder_subject', $form_state->getValue('im_reminder_subject'))
      ->set('im_reminder_body', $form_state->getValue('im_reminder_body')['value'])
      ->save();
  }

}
