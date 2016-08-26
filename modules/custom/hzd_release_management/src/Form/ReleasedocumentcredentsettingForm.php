<?php

/**
 * @file
 * Contains \Drupal\hzd_release_management\Form\ReleasedocumentcredentsettingForm
 */

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
// use Drupal\problem_management\InactiveuserStorage;
// use Drupal\problem_management\Inactiveuserhelper;
// use Drupal\Core\Datetime\DateFormatter;
use Drupal\hzd_customizations\HzdcustomisationStorage;


class ReleasedocumentcredentsettingForm extends ConfigFormBase {

 //  protected $dateFormatter;
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'release_document_credentials_settings_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hzd_release_management.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
   // $config = $this->config('release_management.settings');
  global $base_url;

  $form['release_import_username'] = array(
    '#type' => 'textfield',
    '#title' => t('username'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('release_import_username'),
    '#prefix' => t('Please input the external system credentials here which are used to download the release documentation from CSV files during import.'),
  );

  $form['release_import_password'] = array(
    '#type' => 'textfield',
    '#title' => t('password'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('release_import_password'),
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
    $release_import_username = $form_state->getValue('release_import_username');
    $release_import_password = $form_state->getValue('release_import_password');

    \Drupal::configFactory()->getEditable('hzd_release_management.settings')
      ->set('release_import_username', $release_import_username)
      ->set('release_import_password', $release_import_password)
      ->save();
     parent::submitForm($form, $form_state);
  }
}
