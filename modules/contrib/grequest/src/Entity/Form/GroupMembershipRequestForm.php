<?php

namespace Drupal\grequest\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Plugin\Validation\Constraint\GroupContentCardinality;

/**
 * Provides a form for requesting a group membership.
 */
class GroupMembershipRequestForm extends ContentEntityForm implements ConfirmFormInterface {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->getGroup()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->getConfirmText();
    $actions['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());

    return $actions;
  }

  /**
   * Form cancel handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Make field disabled so validation will render but not allow edits.
    $form['entity_id']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $violations = $this->entity->validate();
    foreach ($violations as $violation) {
      $constraint = $violation->getConstraint();
      if ($constraint instanceof GroupContentCardinality && $constraint->entityMessage == $violation->getMessage()->getUntranslatedString()) {
        $form_state->setError($form, $this->t('You have already sent a request'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);
    $this->messenger()->addMessage($this->t('Your request is waiting for approval'));
    $form_state->setRedirectUrl($this->getCancelUrl());
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {}

  /**
   * {@inheritdoc}
   */
  public function getDescription() {}

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Request group membership');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {}

}
