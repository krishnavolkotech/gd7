<?php

/**
 * @file
 * Contains \Drupal\group\Form\GroupCancelRequestMembershipForm.
 */

namespace Drupal\group\Form;

use Drupal\group\Entity\Form\GroupContentDeleteForm;

/**
 * Provides a form for leaving a group.
 */
class GroupCancelRequestMembershipForm extends GroupContentDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $message = 'Are you sure you want to cancel membership request for %group?';
    $replace = ['%group' => $this->getEntity()->getGroup()->label()];
    return $this->t($message, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel group mebership request');
  }

}