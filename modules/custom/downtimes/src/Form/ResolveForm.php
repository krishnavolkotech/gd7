<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\
 */

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/*
 * Resolve Downtimes form
 */
class ResolveForm extends FormBase {

 //  protected $dateFormatter;

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'resolve_form';
  }

  /*
   * Resolve Downtimes form
   */

  public function buildForm(array $form, FormStateInterface $form_state, $form_type = '') {
	 // $user_role = get_user_role();
         // User::getRoles($exclude_locked_roles = FALSE)    
          $user_role = get_user_role();
	  $type = ($form_type == 'resolve_maintenance' ? 'Maintenance' : 'Incident');

	  $pos_slash = strripos($_GET['q'], '/');
	  $nid = substr($_GET['q'], $pos_slash + 1);
          /**
	  $breadcrumb = array();
	  $breadcrumb[] = l(t('Home'), NULL);
	  if (isset($_SESSION['Group_name'])) {
	    $breadcrumb[] = l(t($_SESSION['Group_name']), 'node/' . $_SESSION['Group_id']);
	    $breadcrumb[] = l(t('Incidents and Maintenances'), 'node/' . $_SESSION['Group_id'] . '/downtimes');
	  }
	  else {
	    $breadcrumb[] = l(t('Incidents and Maintenances'), 'downtimes');
	  }
	  $breadcrumb[] = l(t('Downtime'), 'node/' . $nid);
	  $breadcrumb[] = t('Resolve');
          */ 
	  // $resolved_title = db_result(db_query("SELECT scheduled_p from {state_downtimes} WHERE down_id = %d", $nid));

          $query = \Drupal::database()->select('downtimes', 'd');
          $query->fields('d', ['scheduled_p']);
	  $query->condition('d.downtime_id', $nid, '=');
          $query->range(1);
	  $services = $query->execute()->fetchField();

          $resolved_title = $services['scheduled_p'];

          if ($resolved_title == 0) {
	    // drupal_set_title(t('Resolve Incident'));
	    $form['#title'] = t('Resolve Incident');
	  }
	  else {
	    // drupal_set_title(t('Resolve Maintenances'));
            $form['#title'] = t('Resolve Maintenances');
	  }
	 // drupal_set_breadcrumb($breadcrumb);

	  $form['comment'] = array(
	    '#type' => 'text_format',
	    '#title' => t('Comment'),
	    '#required' => TRUE,
	    '#id' => 'reason',
	    '#weight' => -2,
	  );

	  if (in_array($user_role, array('site_admin'))) {
	    $form['notifications']['#type'] = 'fieldset';
	    $form['notifications']['#title'] = t('Notifications');
	    $form['notifications']['#collapsible'] = TRUE;
	    $form['notifications']['notifications_content_disable'] = array(
	      '#type' => 'checkbox',
	      '#weight' => 2,
	      '#title' => t('Do not send notifications for this update.'),
	    );
	  }

	  $form['nid'] = array(
	    '#type' => 'hidden',
	    '#title' => t('hidden'),
	    '#value' => $nid,
	  );
	  $form['type'] = array(
	    '#type' => 'hidden',
	    '#title' => t('hidden'),
	    '#value' => $type,
	  );

	  // $nodeinfo = node_load($nid);
          $nodeinfo = \Drupal\node\Entity\Node::load($nid);
	  $start_date = str_replace(' Uhr', '', $nodeinfo->startdate_planned);
	  $end_date_planned = str_replace(' Uhr', '', $nodeinfo->enddate_planned);
	  $form['startdate_planned'] = array(
	    '#title' => t('Start Date'),
	    '#type' => 'textfield',
	    '#value' => $start_date,
	    '#size' => 60,
	    '#weight' => -5,
	    '#disabled' => true,
	  );

	  if (!empty($end_date_planned)) {
	  $form['enddate_planned'] = array(
	    '#title' => t('Expected End Date'),
	    '#type' => 'textfield',
	    '#value' => $end_date_planned,
	    '#size' => 60,
	    '#weight' => -4,
	    '#disabled' => true,
	  );
	  }

	  $first_month_first_day = date('Y-01-01 00:00');

	  $form['date_reported'] = array(
	    '#title' => t('Actual End Date'),
	    '#type' => 'date_text',
	    '#default_value' => $first_month_first_day,
	    '#date_format' => 'd.m.Y - H:i',
	    '#size' => 60,
	    '#weight' => -3,
	    '#required' => TRUE,
	    '#suffix' => ($resolved_title == 1) ? t('Please enter the actual end date of the maintenance.') : t('Please enter the actual end date of the incident.'),
	  );

	  $form['submit'] = array(
	    '#type' => 'submit',
	    '#value' => t('submit'),
	    '#id' => 'reason',
	    '#weight' => 3
	  );
	  return $form;
}

 /*
 * Validating downtimes resolve form
 * End date should not be empty and should be greater than start date of the downtime.
 */

  public function validateForm(array &$form, FormStateInterface $form_state) {

	 // $sql = db_fetch_object(db_query("select startdate_planned  from {state_downtimes} where down_id = %d", $form_state['values']['nid']));
        //  echo $form_state->getValue('nid');
          $query = \Drupal::database()->select('downtimes', 'd');
		    $query->fields('d', ['startdate_planned']);
		    $query->condition('d.downtime_id', $form_state->getValue('nid') , '=');
                    $query->range(1);
	  $sql = $query->execute()->fetchAll();
        //  pr($sql);
	  $startdate = $sql->startdate_planned;
	 // $enddate = get_unix_timestamp($_POST['date_reported']['date']);

          $enddate = \Drupal\Core\Datetime\DrupalDateTime::createFromFormat('c', $_POST['date_reported']['date']);
          // $my_rfc2822_string = $date->format('r');        $expiration_friendly = format_date($expiration);


	  $currentdate = time();

	  if ($enddate <= $startdate) {
	    // form_set_error('date_reported', t('Resolve End date should be after start date.'));
             $form_state->setErrorByName('date_reported', $this->t('Resolve End date should be after start date.'));
	  }

	  if ($enddate > $currentdate) {
	    // form_set_error('', t('Actual end date cannot be in the future.'));
            $form_state->setErrorByName('date_reported', $this->t('Actual end date cannot be in the future.'));
	  }
  }



  public function submitForm(array &$form, FormStateInterface $form_state) {
	  $_SESSION['form_values'] = $form_state->getvalues();
	//  $_SESSION['form_values']['resolved_end_date'] = $form_state['clicked_button']['#post']['date_reported']['date'];

	  if (isset($_SESSION['Group_name'])) {
	    $path = 'node/' . $_SESSION['Group_id'] . '/confirm';
	    $form_state->set('redirect', $path);
	  }
	  else {
	    $form_state->set('redirect', 'confirm');
	  }
   }
}
