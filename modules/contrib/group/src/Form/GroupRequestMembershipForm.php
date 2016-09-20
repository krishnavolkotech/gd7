<?php

/**
 * @file
 * Contains \Drupal\group\Form\GroupRequestMembershipForm.
 */

namespace Drupal\group\Form;

use Drupal\group\Entity\Form\GroupContentForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\ContentEntityForm;

/**
 * Provides a Request form for joining a group.
 */
class GroupRequestMembershipForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $config = $this->getEntity()->getContentPlugin()->getConfiguration();
    if (!empty($config['data']['info_text']['value'])) {
      $form['info_text'] = [
        '#markup' => $config['data']['info_text']['value'],
        '#weight' => -99,
      ];
    }
    $form['entity_id']['#access'] = FALSE;
    $form['group_roles']['#access'] = FALSE;
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message Body'),
      '#description' => $this->t('We will attache this message in request membership mail.'),
    ];
    return $form;
  }
  
  function validateForm(array &$form, FormStateInterface $form_state){
    $group = \Drupal::routeMatch()->getParameter('group');
    if($group->getMemberRequestStatus(\Drupal::currentUser()) === 0){
      $form_state->setErrorByName('message','You already have a pending request.');
    }else{
      $form_state->setRedirect('entity.group.canonical', ['group'=>$group->id()]);
    }
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $group = \Drupal::routeMatch()->getParameter('group');
    $subject = "Membership Request for a Group - ".$group->label();
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
