<?php

namespace Drupal\group\Form;

use Drupal\group\Entity\Form\GroupContentDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for leaving a group.
 */
class GroupLeaveForm extends GroupContentDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $message = 'Are you sure you want to leave %group?';
    $replace = ['%group' => $this->getEntity()->getGroup()->label()];
    return $this->t($message, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Leave group');
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $groupId = $this->getEntity()->getGroup()->id();
    $form_state->setRedirect('view.my_groups.page_1');
    return parent::submitForm($form, $form_state);
  }

}
