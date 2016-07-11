<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\maintenancegroupidForm.
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
class maintenancegroupidForm extends ConfigFormBase {

 //  protected $dateFormatter;
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'downtime_maintenancegroupid_form';
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
  * Menu callback; admin settings form for Scheduled Maintenance Group ID.
  */

  public function buildForm(array $form, FormStateInterface $form_state) {
  // drupal_set_title(t('Scheduled Maintenance Group Id'));
        //  $form['#markup']['#title'] = t('Scheduled Maintenance Group Id');
	  $form['maintenance_group_id'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Scheduled Maintenance Group Id'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('maintenance_group_id'), 
	    '#prefix' => t('Please Enter Scheduled Maintenance nid'),
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
     $maintenance_group_id = $form_state->getValue('maintenance_group_id');
   //  echo '<pre>';  print_r($maintenance_group_id);  exit;

    \Drupal::configFactory()->getEditable('downtimes.settings')
      ->set('maintenance_group_id', $maintenance_group_id)
      ->save();

     parent::submitForm($form, $form_state);
  }
}
