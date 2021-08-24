<?php

namespace Drupal\cust_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 11/9/17
 * Time: 8:00 PM
 */
class NsmStateConfigForm extends FormBase {
  
  
  public function getFormId() {
    return 'nsm_state_config_form';
  }
  
  protected function getNsmData($state) {
    $nsm_user_names = \Drupal::database()
      ->select('nsm_role', 'nr');
    $nsm_user_names->fields('nr', ['rolename']);
    $nsm_user_names->leftJoin('nsm_user', 'nu', 'nr.id = nu.nsm_role_id and nu.state_id = ' . $state);
    $nsm_user_names->fields('nu', ['state_id', 'id', 'nsm_username']);
    $nsm_user_names->addField('nr', 'id', 'nsm_role_id');
    $nsm_user_names = $nsm_user_names->execute()->fetchAll();
    return $nsm_user_names;
  }
  
  public function buildForm(array $form, FormStateInterface $form_state, $state = NULL) {
    $form['state'] = [
      '#type' => 'hidden',
      '#value' => $state,
      '#access' => FALSE
    ];
    $data = $this->getNsmData($state);
    foreach ($data as $item) {
      $form[$item->rolename] = [
        '#type' => 'textfield',
        '#title' => $item->rolename,
        '#default_value' => $form_state->hasValue($item->rolename)?$form_state->getValue($item->rolename):$item->nsm_username,
      ];
    }
    
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => 'Submit'];
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $data = $form_state->getValues();
//    pr($data);exit;
    $roles = \Drupal::database()
      ->select('nsm_role', 'nr')->fields('nr')->execute()->fetchAll();
    foreach ($roles as $role) {
      $insert = [
        'state_id' => $data['state'],
        'nsm_role_id' => $role->id,
        'nsm_username' => $form_state->getValue($role->rolename),
      ];
      $check = \Drupal::database()->select('nsm_user', 'nu')
        ->fields('nu')
        ->condition('state_id', $data['state'])
        ->condition('nsm_role_id',$role->id)
        ->execute()
        ->fetch();
//      pr($check);exit;
      if (!empty($check)){
        $update = \Drupal::database()->update('nsm_user')
          ->fields($insert)
          ->condition('state_id', $data['state'])
          ->condition('nsm_role_id', $role->id)
          ->execute();
//        pr($insert);exit;
      }else{
        $acion = \Drupal::database()->insert('nsm_user')
          ->fields($insert)
          ->execute();
      }
      
    }
    \Drupal::messenger()->addMessage(t('Data saved succesfully'));
  }
  
}
