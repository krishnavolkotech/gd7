<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\hzd_customizations\Form;

/**
 * Description of Manage non production environment
 *
 * @author sandeep
 */
class ManagenonproductionenvironmentForm extends \Drupal\Core\Form\FormBase {

  function getFormId() {
    return 'manage_non_production_environment';
  }

  function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    
    $form['add_link'] = ['#type'=>'link','#title'=>'Add a new non-production environment','#url'=> \Drupal\Core\Url::fromRoute('node.add', ['node_type'=>'non_production_environment'])];
    $this->states = \Drupal::database()->select('states', 's')
            ->fields('s', ['id', 'state'])
            ->execute()
            ->fetchAllKeyed(0);
//    $selectedState = $form_state->getValue('state', '');
    $form['state'] = [
        '#type' => 'select',
        '#options' => $this->states,
        '#default_value' => $form_state->getValue('state', ''),
              '#ajax' => array(
            'callback' => [$this,'validate'],
            'wrapper' => 'samle',
            'method' => 'replace',
            'event' => 'change',
            'progress' => array(
                'type' => 'throbber',
                'message' => NULL,
            ),
        ),
    ];
    self::validate($form,$form_state);
    return $form;
  }

  function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    ;
  }
  function validate(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $nodeListQuery = \Drupal::entityTypeManager()->getStorage('node');
    $conditions = ['type' => 'non_production_environment'];
    if($form_state->getValue('state', null)){
      $conditions['field_non_production_state'] = $form_state->getValue('state');
    }
    $nodeList = $nodeListQuery->loadByProperties($conditions);
    $headers = ['State', 'Title', 'Operation'];
    foreach ($nodeList as $node) {
      $row[] = [
          $this->states[$node->get('field_non_production_state')->value],
          $node->label(),
          $node->toLink(t('Edit'), 'edit-form')
      ];
    }
//    pr($nodeList);exit;
    $form['data'] = ['#theme' => 'table', '#header' => $headers, '#rows' => $row,'#attributes'=>['id'=>'samle'],'#empty'=>$this->t('No data found')];
//    pr($form);exit;
    return $form['data'];
  }

}
