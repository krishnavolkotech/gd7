<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\downtimes.
 */
/**
 *  TO do
 *  reset to default button
 */
namespace Drupal\downtimes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
// use Drupal\problem_management\HzdStorage;
// use Drupal\problem_management\Inactiveuserhelper;
// use Drupal\Core\Datetime\DateFormatter;
// use Drupal\hzd_customizations\HzdcustomisationStorage;
/**
 * Configure inactive_user settings for this site.
 */
class downtimes extends ConfigFormBase {

 //  protected $dateFormatter;
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'downtimes_form';
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
 * admin setting form fo setting downtimes Notes
 */

  public function buildForm(array $form, FormStateInterface $form_state) {
	  $form['current_downtimes'] = array(
	    '#type' => 'textarea',
	    '#title' => t('Current Downtimes'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('current_downtimes'),
	  );
	  $form['archived_downtimes'] = array(
	    '#type' => 'textarea',
	    '#title' => t('Archived Downtimes'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('archived_downtimes'),
	  );
	  $form['report_downtimes'] = array(
	    '#type' => 'textarea',
	    '#title' => t('Report  Downtime - 1'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_downtimes'),
	  );
	  $form['report_downtimes_2'] = array(
	    '#type' => 'textarea',
	    '#title' => t('Report  Downtime - 2'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_downtimes_2'),
	  );
	  $form['report_downtimes_3'] = array(
	    '#type' => 'textarea',
	    '#title' => t('Report  Downtime - 3'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_downtimes_3'),
	  );
	  $form['notes_downtimes'] = array(
	    '#type' => 'textarea',
	    '#title' => t('Notes'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('notes_downtimes'),
	  );
	  $form['report_maintenance'] = array(
	    '#type' => 'textarea',
	    '#title' => t('Report  Maintenance - 1'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_maintenance'),
	  );
	  $form['report_maintenance_2'] = array(
	    '#type' => 'textarea',
	    '#title' => t('Report  Maintenance - 2'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('report_maintenance_2'),
	  );
	  $form['report_maintenance_3'] = array(
	    '#type' => 'textarea',
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

  /**
   * {@inheritDoc}
   */

  /*
   * submit handler for the problems settings page
   * selected services for the individual groups are stored in the table "group_problems_view"
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
     $current_downtimes = $form_state->getValue('current_downtimes');
     $archived_downtimes = $form_state->getValue('archived_downtimes');
     $report_downtimes = $form_state->getValue('report_downtimes');
     $report_downtimes_2 = $form_state->getValue('report_downtimes_2');
     $report_downtimes_3 = $form_state->getValue('report_downtimes_3');
     $report_maintenance = $form_state->getValue('report_maintenance');
     $notes_downtimes = $form_state->getValue('notes_downtimes');
     $report_maintenance_2 = $form_state->getValue('report_maintenance_2');
     $report_maintenance_3 = $form_state->getValue('report_maintenance_3');

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
