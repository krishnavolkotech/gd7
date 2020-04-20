<?php

namespace Drupal\hzd_sams\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection;
/**
 * Provides a form which writes registration information in xml/JSON file and sends this file to SAMS.
 */
class SamsRegistrationForm extends FormBase {
    
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
      return 'sams_registration_form';
  }

/**
 * {@inheritdoc}
 */
// $form = \Drupal::formBuilder()->getForm('Drupal\hzd_sams\Form\SamsRegistrationForm');
public function buildForm(array $form, FormStateInterface $form_state) {
  $form['user_name'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('username'),
    '#description' => t('gewünschter Benutzername im SAMS'),
    '#required' => TRUE,
  );
  
    // $form['lastname'] = array(
      // '#type' => 'textfield',
      // '#title' => $this->t('Lastname'),
      // '#description' => t(Lastname),
      // '#required' => TRUE,
    // );
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Submit'),
    ];
    return $form;
  }

/**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('user_name')) < 3) {
      $form_state->setErrorByName('user_name', $this->t('The username is too short. Please enter a name > 3'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   $xmlstr =
'<?xml version="1.0" encoding="UTF-8"?>' .
'<RegistrationData>' .
  '<username>'. $form_state->getValue('user_name') .'</username>' .
  '</RegistrationData>';

 
 return $xmlstr;


  // return $form_state ['user_name'];
  // , die xml oder json aus eingebenen Daten enthält und diese in Datei schreibt
  }

}
