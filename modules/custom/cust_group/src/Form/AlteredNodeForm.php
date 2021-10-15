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
      //$messages = drupal_get_messages();
      $messages = \Drupal::messenger()->all();
      $nodeTitle = $node->tolink($node->label())->toString();
      if ($node->get('status')->value == 1) {
                \Drupal::messenger()->deleteAll();	
        \Drupal::messenger()->addMessage(t('@type @title has been published.', ['@type' => node_get_type_label($node), '@title' => $nodeTitle]));
      }
      elseif($node->custom_isnew == 1) {
          \Drupal::messenger()->deleteAll();
          \Drupal::messenger()->addMessage(t('@type @title has been saved', ['@type' => node_get_type_label($node), '@title' => $nodeTitle]));
      } else {
        \Drupal::messenger()->addMessage(t('@type @title has been updated', ['@type' => node_get_type_label($node), '@title' => $nodeTitle]));
      }
    }
  }

}
