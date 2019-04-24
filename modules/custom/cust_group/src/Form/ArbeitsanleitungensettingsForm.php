<?php

/**
 * @file
 * Contains \Drupal\problem_management\Form\ProblemmanagementsettingsForm
 */

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ArbeitsanleitungensettingsForm extends ConfigFormBase {

  //  protected $dateFormatter;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arbeitsanleitungen_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'arbeitsanleitungen.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import_url'] = [
      '#type' => 'link',
      '#title' => t('Import Arbeitsanleitungen'),
      '#url' => Url::fromroute('arbeitsanleitungen.read_arbeitsanleitungen_zip'),
      '#size' => 14,
    ];
    $form["#prefix"] = "<div class = 'problem_configure'>";
    $form["#suffix"] = "</div>";
    $form['import_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to import ZIP file'),
      '#description' => t('Path relative to @path', ['@path' => DRUPAL_ROOT . '/']),
      '#default_value' => \Drupal::config('arbeitsanleitungen.settings')->get('import_path'),
      '#required' => TRUE,
      '#prefix' => '<div class = "url_alias_textfield">',
      '#suffix' => '</div>'
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
    if (strpos($import_path, '.zip') == false) {
      $form_state->setErrorByName('import_path', $this->t('Please enter zip problem file path'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $import_path = $form_state->getValue('import_path');

    \Drupal::configFactory()->getEditable('arbeitsanleitungen.settings')
      ->set('import_path', $import_path)
      ->save();
    parent::submitForm($form, $form_state);
  }
}
