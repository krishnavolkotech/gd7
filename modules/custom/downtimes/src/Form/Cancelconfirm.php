<?php

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form for deleting mymodule data.
 */
class Cancelconfirm extends ConfirmFormBase {

    /**
     * The ID of the item to delete.
     *
     * @var string
     */
    protected $id;

    /**
     * {@inheritdoc}.
     */
    public function getFormId()
    {
      return 'cancelconfirmform';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion() {
      //  return t('Do you want to delete %id?', array('%id' => $this->id));
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl() {
      // this needs to be a valid route otherwise the cancel link won't appear
      //  return new Url('mymodule.home');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription() {
      //  return t('Only do this if you are sure!');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText() {
      //  return $this->t('Delete it Now!');
    }


    /**
     * {@inheritdoc}
     */
    public function getCancelText() {
      //  return $this->t('Nevermind');
    }

    /**
     * {@inheritdoc}
     *
     * @param int $id
     *   (optional) The ID of the item to be deleted.
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
      //  $this->id = $id;
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
	  $nodeinfo = node_load($nid);
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



    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
	  $user = Drupal::currentUser();
	  $user_role = get_user_role();
	  extract($_SESSION['form_values']);
	  $message = 'Die Blockzeit wurde storniert.';
	  //$date_report = get_timestamp($cancelled_end_date);
	  //$date_report = get_unix_timestamp($cancelled_end_date);
	  $date_report = $cancelled_end_date;
 
          $query = \Drupal::database()->update('downtimes');
		$query->fields([
		  'cancelled' => 1,
		  'enddate_reported' => $date_report,
		  'comment' => $comment,
		  'user_id' => $user->uid,
		]);
		$query->condition('downtime_id',$nid , '=');
		$query->execute();

	  drupal_set_message(t($message));

	  $node_resolve = \Drupal\node\Entity\Node::load($nid);

          $query = \Drupal::database()->select('downtimes', 'd');
		    $query->fields('d', ['state_id']);
		    $query->condition('d.downtime_id', $nid, '=');
                    $query->range(1);
	  $node_cancel->state = $query->execute()->fetchField();

          $query = \Drupal::database()->select('downtimes', 'd');
		    $query->fields('d', ['service_id']);
		    $query->condition('d.downtime_id', $nid, '=');
                    $query->range(1);
	  $node_cancel->service = $query->execute()->fetchField();

	  $mode = 'Cancel';
	  $path = $type;

	  if (isset($_SESSION['Group_name'])) {
            $path = Url::fromUserInput('/node/' . $_SESSION['Group_id'] . '/downtimes');
	  }
	  else {
	    $path = Url::fromUserInput('/downtimes');
	  }

	  unset($_SESSION['form_values']);

	  $event = array(
	    'module' => 'node',
	    'uid' => $node_cancel->uid,
	    'oid' => $node_cancel->nid,
	    'type' => 'node',
	    'action' => 'Cancel',
	    'node' => $node_cancel,
	    'params' => array('nid' => $node_cancel->nid),
	  );

	  if ($notifications_content_disable != 1) {
	    # Use custom downtimes_notifications to send immediate downtimes notifications instead of notifications module.
	    # Immediate notifications are still inserted into notifications_queue but get deleted by downtimes_notifications_notifications hook.
	    # Digested notifications are handled by the default notifications module.
	    notifications_event($event);
	    $action = "Cancel";
	  //  downtimes_notifications_insert($node_cancel, $action);
	  }
//	  drupal_goto($path);
          
          $form_state->set('redirect', $path);

    }

}

