<?php

/**
 * @file
 * Contains \Drupal\hzd_release_management\Form\ReleasedocumentsettingForm
 */

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
// use Drupal\Core\Datetime\DateFormatter;
// use Drupal\hzd_customizations\HzdcustomisationStorage;

class ReleasedocumentsettingForm extends ConfigFormBase {

 //  protected $dateFormatter;
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'release_document_settings_form';
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

  $form['release_not_import'] = array(
    '#type' => 'textfield',
    '#title' => t('Email'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('release_not_import'),
    '#description' => t('When documentation download is failed during import for 3 times, then an mail will be sent to above given mail address and also provided multiple email address by comma seperated.'),
  );

  $form['release_mail_body'] = array(
    '#type' => 'text_format',
    '#title' => t('Email content'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('release_mail_body')['value'],
    '#description' => t('When documentation download is failed during import for 3 times, then an mail with above given content will be sent.'),
    // '#format' => array('basic_html'),
  );
  
  $form['failed_download_text'] = array(
    '#type' => 'text_format',
    '#title' => t('Failed Download Text'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('failed_download_text')['value'],
    '#description' => t('When documentation download is failed and if user tries to access that release documentation, then the above given text will be displayed and provided a direct link of external system will be provided to download.'),
  );

  $form['secure_download_text'] = array(
    '#type' => 'text_format',
    '#title' => t('Secure Download Text'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('secure_download_text')['value'],
    '#description' => t('There are few documents which are available for download in a separate part of the DSL which requires a different user/password for access. If user access those files, then the above given text will be displayed and a direct link of external system to download will be provided.'),
  );
/**
  $form['import_path_csv_initial_released'] = array(
    '#type' => 'textfield',
    '#title' => t('Path to csv file for documentation import'),
    '#description' => t('/srv/www/betriebsportal/files/import/released.csv'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('import_path_csv_initial_released'),
   );
  */
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
 //   $import_path_csv_initial_released = $form_state->getValue('import_path_csv_initial_released');
    $import_path_locked = $form_state->getValue('import_path_csv_locked');
    $secure_download_text = $form_state->getValue('secure_download_text');
    $failed_download_text = $form_state->getValue('failed_download_text');
    $release_mail_body = $form_state->getValue('release_mail_body');
    $release_not_import = $form_state->getValue('release_not_import');

    \Drupal::configFactory()->getEditable('hzd_release_management.settings')
   //   ->set('import_path_csv_initial_released', $import_path_csv_initial_released)
      ->set('secure_download_text', $secure_download_text)
      ->set('failed_download_text', $failed_download_text)
      ->set('release_mail_body', $release_mail_body)
      ->set('release_not_import', $release_not_import)
      ->save();
       parent::submitForm($form, $form_state);
  }
}
