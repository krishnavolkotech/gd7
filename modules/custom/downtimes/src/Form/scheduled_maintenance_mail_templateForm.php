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
class scheduled_maintenance_mail_templateForm extends ConfigFormBase {

 //  protected $dateFormatter;

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'downtime_scheduled_maintenance_mail_template_form';
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
  *  Menu callback; mailtemplate for scheduled maintenance.
  */

  public function buildForm(array $form, FormStateInterface $form_state) {
	//    drupal_set_title(t('Mail Template for Scheduled Maintenance'));

	  $form['reminder_mail_subject'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Mail Subject'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('reminder_mail_subject'),
	    '#description' => t('Enter Reminder Mail Subject'),
	  );
          $reminder_mail_body = \Drupal::config('downtimes.settings')->get('reminder_mail_body');
	  $form['reminder_mail_body'] = array(
            '#type' => 'text_format',
	    '#title' => t('Mail Body'),
	    '#default_value' => $reminder_mail_body['value'],
	    '#description' => t('Enter Reminder Mail Body'),
	  );
	  $form['resolve_maintenance_mail_subject'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Mail Subject'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('resolve_maintenance_mail_subject'),
	    '#description' => t('Enter Resolve Maintenance Mail Subject'),
	  );

          $resolve_maintenance_mail_body = \Drupal::config('downtimes.settings')->get('resolve_maintenance_mail_body');
	  $form['resolve_maintenance_mail_body'] = array(
            '#type' => 'text_format',
	    '#title' => t('Mail Body'),
	    '#default_value' => $resolve_maintenance_mail_body['value'],
	    '#description' => t('Enter Resolve Maintenance Mail Body'),
	  );
	  $form['number_of_days'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Number of Days'),
	    '#default_value' => \Drupal::config('downtimes.settings')->get('number_of_days'),
	    '#description' => t('Enter Number of Days'),
	  );

	 if (\Drupal::moduleHandler()->moduleExists('token')) {
	      $form['token_tree'] = [
		'#theme' => 'token_tree_link',
		'#token_types' => 'all',
		'#show_restricted' => TRUE,
	      ];
	    }



/**
	  $form['tokens'] = array(
	    '#type' => 'fieldset',
	    '#title' => 'Available tokens',
	    '#collapsible' => TRUE,
	    '#collapsed' => TRUE,
	  );

	  if ($tokens = get_available_tokens()) {
	    $headers = array(t('Token'), t('Replacement value'));
	    $rows = array();

	    foreach ($tokens as $token => $token_description) {
	      $row = array();
	      $row[] = '[' . $token . ']';
	      $row[] = $token_description;
	      $rows[] = $row;
	    }

	    $form['tokens']['avail_tokens'] =  array(
		    '#theme' => 'table', 
		    '#header' => $headers,
		    '#rows' => $rows,
		    '#empty' => t('No Data Created Yet'),
		    '#attributes' => array(
		      'id' => 'description', 
		      '#class' => 'description'
		      ),  
	    );
	  }
*/
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
     $reminder_mail_subject = $form_state->getValue('reminder_mail_subject');
     $reminder_mail_body = $form_state->getValue('reminder_mail_body');
     $resolve_maintenance_mail_subject = $form_state->getValue('resolve_maintenance_mail_subject');
     $resolve_maintenance_mail_body = $form_state->getValue('resolve_maintenance_mail_body');
     $number_of_days = $form_state->getValue('number_of_days');

    // echo '<pre>';  print_r($number_of_days);  exit;
    \Drupal::configFactory()->getEditable('downtimes.settings')
      ->set('reminder_mail_subject', $reminder_mail_subject)
      ->set('reminder_mail_body', $reminder_mail_body)
      ->set('resolve_maintenance_mail_subject', $resolve_maintenance_mail_subject)
      ->set('resolve_maintenance_mail_body', $resolve_maintenance_mail_body)
      ->set('number_of_days', $number_of_days)
      ->save();

   //  echo \Drupal::config('downtimes.settings')->get('number_of_days');  exit;

     parent::submitForm($form, $form_state);
  }
}

