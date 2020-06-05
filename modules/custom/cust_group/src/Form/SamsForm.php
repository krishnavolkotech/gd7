<?php

/**
 * @file
 * Contains \Drupal\problem_management\Form\ProblemmanagementsettingsForm
 */

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SamsForm extends ConfigFormBase {

  //  protected $dateFormatter;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sams_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cust_group.sams.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['sams_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Sams Group ID'),
      '#default_value' => \Drupal::config('cust_group.sams.settings')->get('sams_id'),
      '#required' => TRUE,
    );
    
    $form['sams_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Sams URL'),
      '#default_value' => \Drupal::config('cust_group.sams.settings')->get('sams_url'),
      '#required' => TRUE,
    );
     
    $form['sams_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Sams User'),
      '#default_value' => \Drupal::config('cust_group.sams.settings')->get('sams_user'),
      '#required' => TRUE,
    );
 
    $form['sams_pw'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Sams Password'),
      '#default_value' => \Drupal::config('cust_group.sams.settings')->get('sams_pw'),
      '#required' => TRUE,
    );
    
    return parent::buildForm($form, $form_state);
  }

  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sams_id = $form_state->getValue('sams_id');
    $sams_url = $form_state->getValue('sams_url');
    $sams_user = $form_state->getValue('sams_user');
    $sams_pw = $form_state->getValue('sams_pw');
    \Drupal::configFactory()->getEditable('cust_group.sams.settings')
      ->set('sams_id', $sams_id)
      ->set('sams_url', $sams_url)
      ->set('sams_user', $sams_user)
      ->set('sams_pw', $sams_pw)
      ->save();
    parent::submitForm($form, $form_state);
  }
}
