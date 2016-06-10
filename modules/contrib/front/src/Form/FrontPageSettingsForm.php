<?php

/**
 * @file
 * Contains \Drupal\front\Form\FrontPageSettingsForm
 */

namespace Drupal\front\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure inactive_user settings for this site.
 */
class FrontPageSettingsForm extends ConfigFormBase {

//  protected $dateFormatter;
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'front_page_settings_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'front.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // build the form for roles
    $roles = user_role_names();
    foreach ($roles as $role => $rolename) {
      $default_val = \Drupal::config('front.settings')->get('front_'. $role .'_text');
      $form['front_'. $role .'_text'] = array(
        '#type' => 'text_format',
        '#title' => t('Front Page for @rolename.', array('@rolename' => $rolename)),
        '#default_value' => $default_val['value'],
        '#cols' => 60,
        '#rows' => 20,
      );
    }

    $form['site'] = array(
      '#type' => 'fieldset',
      '#collapsible' => true,
      '#collapsed' => false,
      '#title' => t('Activate your front_page settings'),
    );

    $form['site']['site_frontpage'] = array(
      '#type' => 'textfield',
      '#title' => t('Default front page'),
      '#default_value' => \Drupal::config('front.settings')->get('site_frontpage'),
      '#size' => 40,
      '#description' => t('Change this setting to <em>front_page</em> to activate your front page settings.'),
    );

    $form['submit'] = array(
      '#attributes' => array('readonly' => 'readonly'),
    );
   return parent::buildForm($form, $form_state);
  }

   /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }
  

   /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $roles = user_role_names();
    $config = $this->config('front.settings');
    foreach($roles as $role => $rolename) {
      $front_page_text = $form_state->getValue('front_'. $role .'_text');
      $config->set('front_'. $role .'_text', $front_page_text);
    }
    $front_page_url = $form_state->getValue('site_frontpage');
    $config->set('site_frontpage', $front_page_url)
           ->save();
  }

}
