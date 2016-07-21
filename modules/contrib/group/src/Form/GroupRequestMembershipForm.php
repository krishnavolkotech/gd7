<?php

/**
 * @file
 * Contains \Drupal\group\Form\GroupRequestMembershipForm.
 */

namespace Drupal\group\Form;

use Drupal\group\Entity\Form\GroupContentForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Request form for joining a group.
 */
class GroupRequestMembershipForm extends GroupContentForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['entity_id']['#access'] = FALSE;
    $form['group_roles']['#access'] = FALSE;
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message Body'),
      '#description' => $this->t('We will attache this message in request membership mail.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $subject = "Requested Membership for a Group";
    $message = $form_state->getValue('message');
    $to = $this->config('system.site')->get('mail');
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'group';
    $key = 'immediate_notifications';
    $params['message'] = $message;
    $params['subject'] = $subject;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== true) {
      drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
    }else {
      drupal_set_message(t('Mail sent.'), 'status');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Request Membership group');
    return $actions;
  }

}
