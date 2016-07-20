<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\
 */

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/*
 * Cancel Downtimes form
 */
class CancelForm extends FormBase {

 //  protected $dateFormatter;

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cancel_form';
  }

  /*
   * Resolve Downtimes form
   */

  public function buildForm(array $form, FormStateInterface $form_state, $form_type = '') {
	 // $user_role = get_user_role();
          $user_role = get_user_role();
	  $type = ($form_type == 'cancel_maintenance' ? 'Maintenance' : 'Incident');
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
	  $resolved_title = db_result(db_query("SELECT scheduled_p from {state_downtimes} WHERE down_id = %d", $nid));
	  if ($resolved_title == 0) {
	    drupal_set_title(t('Cancel Incident'));
	  }
	  else {
	    drupal_set_title(t('Cancel Maintenances'));
	  }
	  drupal_set_breadcrumb($breadcrumb);
          */
	  $form['comment'] = array(
	    '#type' => 'textformat',
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
	  /*$form['date_reported'] = array(
	    '#title' => t('Actual End Date'),
	    '#type' => 'date_text',
	    '#default_value' => $first_month_first_day,
	    '#date_format' => 'd.m.Y - H:i',
	    '#size' => 60,
	    '#weight' => -3,
	    '#required' => TRUE,
	    '#suffix' => ($resolved_title == 1) ? t('Please enter the actual end date of the maintenance.') : t('Please enter the actual end date of the incident.'),
	  );*/
	  $form['submit'] = array(
	    '#type' => 'submit',
	    '#value' => t('submit'),
	    '#id' => 'reason',
	    '#weight' => 3
	  );
	  return $form;
}



/*
 * Validating downtimes cancel form
 * End date should not be empty and should be greater than start date of the downtime.
 */


  public function validateForm(array &$form, FormStateInterface $form_state) {

    $query = \Drupal::database()->select('dowtimes', 'd');
    $query->fields('d', ['startdate_planned']);
    $query->condition('d.downtime_id', $form_state->getValue('nid') , '=');
    $query->range(1);
    $sql = $query->execute()->fetchAll();
    $startdate = $sql->startdate_planned;
    //$enddate = get_unix_timestamp($_POST['date_reported']['date']);
    $currentdate = time();
    if ($currentdate >= $startdate) {
      $form_state->setErrorByName('startdate_planned', $this->t('Cancel End date should not be after start date.'));
    }
  }


 
/*
 * submit handler for the cancel form
 * form values are stored in the session for reusing the values after confirmation.
 */

  public function submitForm(array &$form, FormStateInterface $form_state) {
	  $_SESSION['form_values'] = $form_state->getvalues();
	  //$_SESSION['form_values']['cancelled_end_date'] = $form_state['clicked_button']['#post']['date_reported']['date'];
	  $_SESSION['form_values']['cancelled_end_date'] = strtotime(date('d.m.Y H:i'));
	  if (isset($_SESSION['Group_name'])) {
	    $path = 'node/' . $_SESSION['Group_id'] . '/cancel_confirm';
	    $form_state->set('redirect', $path);
	  }
	  else {
	    $form_state->set('redirect', 'cancel_confirm');
	  }
   }
}
