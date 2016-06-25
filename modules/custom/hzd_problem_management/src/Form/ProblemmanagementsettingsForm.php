<?php

/**
 * @file
 * Contains \Drupal\problem_management\Form\ProblemmanagementsettingsForm
 */

namespace Drupal\problem_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\problem_management\HzdStorage;
// use Drupal\problem_management\Inactiveuserhelper;
// use Drupal\Core\Datetime\DateFormatter;
use Drupal\hzd_customizations\HzdcustomisationStorage;
/**
 * Configure inactive_user settings for this site.
 */
class ProblemmanagementsettingsForm extends ConfigFormBase {

 //  protected $dateFormatter;
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'problem_management_settings_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'problem_management.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $form["#prefix"] = "<div class = 'problem_configure'>";
    $form["#suffix"] = "</div>";
    $form['import_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to import CSV file'),
      '#description' => t('/srv/www/betriebsportal/files/import/problem.csv)'),
      '#default_value' => \Drupal::config('problem_management.settings')->get('import_path'),
      '#required' => TRUE,
      '#prefix' => '<div class = "url_alias_textfield">',
      '#suffix' => '</div>'
    );


    /**
    $form['import_time'] = array(
      '#type' => 'textfield',
      '#title' => t('Import daily at (hh:mm)'),
      '#description' => t('24hrs format Ex:(23:05, 03:40)'),
      '#default_value' => \Drupal::config('problem_management.settings')->get('import_time'),
      '#required' => TRUE,
      '#size' => 15,
    );
    */

    $form['import_mail'] = array(
      '#type' => 'textfield',
      '#title' => t('Email address for import errors'),
      '#default_value' => \Drupal::config('problem_management.settings')->get('import_mail'),
      '#required' => TRUE,
      '#size' => 15,
    );

    $form['import_alias'] = array(
      '#type' => 'textfield',
      '#title' => t('URL alias for group views'),
      '#default_value' => \Drupal::config('problem_management.settings')->get('import_alias'),
      '#description' => $base_url . "/&lt;group name&gt;/",
      '#size' => 15,
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
    $import_path = $form_state->getValue('import_path');
   // $import_time = $form_state->getValue('import_time');
    $import_mail = $form_state->getValue('import_mail');
    $import_alias = $form_state->getValue('import_alias');
    if (strpos($import_path, '.csv') == false) {
      $form_state->setErrorByName('import_path', $this->t('Please enter csv problem file path'));
    } 
   // $ptn = "/^([0-1]\d|2[0-3]):([0-5]\d)$/";
   //  preg_match($ptn, trim($import_time), $matches);
    if (!valid_email_address($import_mail)) {
      // form_set_error('',t('Invalid mail id'));
        $form_state->setErrorByName('import_mail', $this->t('Invalid mail'));
    }

    /**
    if (!$matches) {
       $form_state->setErrorByName('import_time', $this->t('Invalid Time'));
      // form_set_error('',t('Invalid Time'));
    }
    */
  }
  
  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $import_path = $form_state->getValue('import_path');
   // $import_time = $form_state->getValue('import_time');
    $import_mail = $form_state->getValue('import_mail');
    $import_alias = $form_state->getValue('import_alias');
 
    \Drupal::configFactory()->getEditable('problem_management.settings')
      ->set('import_path', $import_path)
   // ->set('import_time', $import_time)
      ->set('import_mail', $import_mail)
      ->set('import_alias', $import_alias)
      ->save();
   // \Drupal::configFactory()->getEditable('locale.settings')->

    HzdcustomisationStorage::change_url_alias($import_alias, 'problems');
    menu_cache_clear_all();
    parent::submitForm($form, $form_state);
  }
}
