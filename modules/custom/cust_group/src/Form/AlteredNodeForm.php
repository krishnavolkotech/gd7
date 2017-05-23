<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\cust_group\Form;

use Drupal\node\NodeForm;

/**
 * Description of AlteredNodeForm
 *
 * @author sandeep
 */
class AlteredNodeForm extends NodeForm {

  //put your code here

  public function save(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $node = $this->entity;
    if ($node->getType() == 'quickinfo') {
      $messages = drupal_get_messages();
      if ($node->get('status')->value == 1) {
        drupal_set_message(t('@type %title has been published.', ['@type' => node_get_type_label($node), '%title' => $node->tolink($node->label())]));
      } else {
        drupal_set_message(t('@type %title has been saved.', ['@type' => node_get_type_label($node), '%title' => $node->tolink($node->label())]));
      }
    }
  }

}
