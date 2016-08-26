<?php

/**
 * @file
 * Contains \Drupal\hzd_release_management\Form\ReleasemanagementsettingsForm
 */

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
// use Drupal\problem_management\InactiveuserStorage;
// use Drupal\problem_management\Inactiveuserhelper;
// use Drupal\Core\Datetime\DateFormatter;
use Drupal\hzd_customizations\HzdcustomisationStorage;


class ReleasemanagementsettingsForm extends ConfigFormBase {

 //  protected $dateFormatter;
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'release_management_settings_form';
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
  $form['#attached']['library'] = array('hzd_release_management/hzd_release_management'); 

  $form['import_path_csv_released'] = array(
    '#type' => 'textfield',
    '#title' => t('Path to import csv file (Released)'),
    '#description' => t('/srv/www/betriebsportal/files/import/released.csv'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('import_path_csv_released'),
    '#required' => TRUE,
    '#prefix' => '<div class = "url_alias_textfield">',
    '#suffix' => '</div>'
    );
  $form['import_path_csv_locked'] = array(
    '#type' => 'textfield',
    '#title' => t('Path to import csv file (Locked)'),
    '#description' => t('/srv/www/betriebsportal/files/import/locked.csv'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('import_path_csv_locked'),
    '#required' => TRUE,
    '#prefix' => '<div class = "url_alias_textfield">',
    '#suffix' => '</div>'
    );
  $form['import_path_csv_progress'] = array(
    '#type' => 'textfield',
    '#title' => t('Path to import csv file (In Progress)'),
    '#description' => t('/srv/www/betriebsportal/files/import/progress.csv'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('import_path_csv_progress'),
    '#required' => TRUE,
    '#prefix' => '<div class = "url_alias_textfield">',
    '#suffix' => '</div>'
    );
/**
  $form['import_path_csv_rejected'] = array(
    '#type' => 'textfield',
    '#title' => t('Path to import csv file (Rejected)'),
    '#description' => t('/srv/www/betriebsportal/files/import/rejected.csv'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('import_path_csv_rejected'),
    '#required' => TRUE,
    '#prefix' => '<div class = "url_alias_textfield">',
    '#suffix' => '</div>'
    );
*/
  $form['import_path_csv_ex_eoss'] = array(
    '#type' => 'textfield',
    '#title' => t('Path to Ex-EOSS csv file (Released Exeoss)'),
    '#description' => t('/srv/www/betriebsportal/files/import/ex_eoss.csv'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('import_path_csv_ex_eoss'),
    '#required' => TRUE,
    '#prefix' => '<div class = "url_alias_textfield">',
    '#suffix' => '</div>'
    );

/**
  $form['import_time'] = array(
    '#type' => 'textfield',
    '#title' => t('Import daily at (hh:mm)'),
    '#description' => t('24hrs format Ex:(23:05, 03:40)'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('import_time_releases'),
    '#required' => TRUE,
    '#size' => 25,
    );
*/
  $form['import_mail'] = array(
    '#type' => 'textfield',
    '#title' => t('Email address for import errors'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('import_mail_releases'),
    '#required' => TRUE,
    '#size' => 25,
    );
  $form['import_alias'] = array(
    '#type' => 'textfield',
    '#title' => t('URL alias for group views'),
    '#default_value' => \Drupal::config('hzd_release_management.settings')->get('import_alias_releases'),
    '#description' => $base_url . "/&lt;group name&gt;/",
    '#size' => 25,
    );
 
  $form['submit'] = array(
    '#suffix' => t('If you change the file location, you need to clear drupal cache.'),
    '#attributes' => array('readonly' => 'readonly'),
    );

   return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  $import_paths = array();
  $import_paths["released"] = $form_state->getValue('import_path_csv_released');
  $import_paths["ex_eoss"] = $form_state->getValue('import_path_csv_ex_eoss');
  $import_paths["locked"] = $form_state->getValue('import_path_csv_locked');
  $import_paths["progress"] = $form_state->getValue('import_path_csv_progress');

    if (strpos($import_paths["released"], '.csv') == false) {
      $form_state->setErrorByName('import_path_csv_released', $this->t('Please enter released csv  file path'));
    } 
    if (strpos($import_paths["ex_eoss"], '.csv') == false) {
      $form_state->setErrorByName('import_path_csv_ex_eoss', $this->t('Please enter ex_eoss csv  file path'));
    } 
    if (strpos($import_paths["locked"], '.csv') == false) {
      $form_state->setErrorByName('import_path_csv_locked', $this->t('Please enter locked csv  file path'));
    } 
    if (strpos($import_paths["progress"], '.csv') == false) {
      $form_state->setErrorByName('import_path_csv_progress', $this->t('Please enter progress csv  file path'));
    } 

  // $import_time = $form_state->getValue('import_time');
  $import_mail = $form_state->getValue('import_mail');
  $import_alias = $form_state->getValue('import_alias');

  $ptn = "/^([0-1]\d|2[0-3]):([0-5]\d)$/";
  
  preg_match($ptn, trim($import_time), $matches);
  
  if (!valid_email_address($import_mail)) {
    $form_state->setErrorByName('import_mail', $this->t('Invalid mail'));
  }
/**
  if (!$matches) {
    $form_state->setErrorByName('import_time', $this->t('Invalid time'));
  }
*/
  }
  
  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $import_path_released = $form_state->getValue('import_path_csv_released');
    $import_path_locked = $form_state->getValue('import_path_csv_locked');
    $import_path_progress = $form_state->getValue('import_path_csv_progress');
    $import_path_csv_ex_eoss = $form_state->getValue('import_path_csv_ex_eoss');
   // $import_path_rejected = $form_state->getValue('import_path_csv_rejected');
   // $import_time = $form_state->getValue('import_time');
    $import_mail = $form_state->getValue('import_mail');
    $import_alias = trim($form_state->getValue('import_alias'));

    \Drupal::configFactory()->getEditable('hzd_release_management.settings')
     // ->set('import_path_csv_rejected', $import_path_rejected)
      ->set('import_path_csv_ex_eoss', $import_path_csv_ex_eoss)
      ->set('import_path_csv_released', $import_path_released)
      ->set('import_path_csv_locked', $import_path_locked)
      ->set('import_path_csv_progress', $import_path_progress)
    //  ->set('import_time_releases', $import_time)
      ->set('import_mail_releases', $import_mail)
      ->set('import_alias_releases', $import_alias)
      ->save();
    HzdcustomisationStorage::change_url_alias($import_alias, 'releases');
    menu_cache_clear_all();
    parent::submitForm($form, $form_state);
  }
}
