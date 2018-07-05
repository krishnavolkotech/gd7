<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\scheduled_maintenance_mail_templateForm.
 */

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure inactive_user settings for this site.
 */
class Downtimesnotesform extends ConfigFormBase {

 //  protected $dateFormatter;

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'downtime_notes_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'downtimes.settings',
    ];
  }

/*
 * admin setting form for setting downtimes Notes
 */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  $form['current_downtimes'] = array(
	    '#type' => 'text_format',
	    '#title' => t('Current Downtimes'),
      '#allowed_formats' => ['full_html'],
	    '#default_value' => \Drupal::config('downtimes.settings')->get('current_downtimes'),
	  );
	  $form['archived_downtimes'] = array(
	    '#type' => 'text_format',
	    '#title' => t('Archived Downtimes'),
      '#allowed_formats' => ['full_html'],
	    '#default_value' => \Drupal::config('downtimes.settings')->get('archived_downtimes'),
	  );
	  $form['report_downtimes'] = array(
	    '#type' => 'text_format',
	    '#title' => t('Report  Downtime - 1'),
      '#allowed_formats' => ['full_html'],
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_downtimes'),
	  );
	  $form['report_downtimes_2'] = array(
	    '#type' => 'text_format',
	    '#title' => t('Report  Downtime - 2'),
      '#allowed_formats' => ['full_html'],
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_downtimes_2'),
	  );
	  $form['report_downtimes_3'] = array(
	    '#type' => 'text_format',
      '#allowed_formats' => ['full_html'],
	    '#title' => t('Report  Downtime - 3'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_downtimes_3'),
	  );
	  $form['notes_downtimes'] = array(
	    '#type' => 'text_format',
	    '#title' => t('Notes'),
      '#allowed_formats' => ['full_html'],
	    '#default_value' => \Drupal::config('downtimes.settings')->get('notes_downtimes'),
	  );
	  $form['report_maintenance'] = array(
	    '#type' => 'text_format',
      '#allowed_formats' => ['full_html'],
	    '#title' => t('Report  Maintenance - 1'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_maintenance'),
	  );

	  $form['report_maintenance_2'] = array(
	    '#type' => 'text_format',
      '#allowed_formats' => ['full_html'],
	    '#title' => t('Report  Maintenance - 2'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_maintenance_2'),
	  );
	  $form['report_maintenance_3'] = array(
	    '#type' => 'text_format',
      '#allowed_formats' => ['full_html'],
	    '#title' => t('Report  Maintenance - 3'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_maintenance_3'),
	  );
    return parent::buildForm($form, $form_state);
  }

 /**
   * {@inheritDoc}
   */

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
     $current_downtimes = $form_state->getValue('current_downtimes')['value'];
     $archived_downtimes = $form_state->getValue('archived_downtimes')['value'];
     $report_downtimes = $form_state->getValue('report_downtimes')['value'];
     $report_downtimes_2 = $form_state->getValue('report_downtimes_2')['value'];
     $report_downtimes_3 = $form_state->getValue('report_downtimes_3')['value'];
     $notes_downtimes = $form_state->getValue('notes_downtimes')['value'];
     $report_maintenance = $form_state->getValue('report_maintenance')['value'];
     $report_maintenance_2 = $form_state->getValue('report_maintenance_2')['value'];
     $report_maintenance_3 = $form_state->getValue('report_maintenance_3')['value'];

   //  echo '<pre>';  print_r($maintenance_group_id);  exit;

    \Drupal::configFactory()->getEditable('downtimes.settings')
      ->set('current_downtimes', $current_downtimes)
      ->set('archived_downtimes', $archived_downtimes)
      ->set('report_downtimes', $report_downtimes)
      ->set('report_downtimes_2', $report_downtimes_2)
      ->set('report_downtimes_3', $report_downtimes_3)
      ->set('notes_downtimes', $notes_downtimes)
      ->set('report_maintenance', $report_maintenance)
      ->set('report_maintenance_2', $report_maintenance_2)
      ->set('report_maintenance_3', $report_maintenance_3)
      ->save();

     parent::submitForm($form, $form_state);
  }
}
