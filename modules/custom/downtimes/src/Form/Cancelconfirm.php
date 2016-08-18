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
	  return t('Are you sure you want to confirm to cancel these items? This action cannot be undone.');

    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl() {
	 if (isset($_SESSION['Group_name'])) {
	    $path = 'node/' . $_SESSION['Group_id'] . '/' . $_SESSION['form_values']['type'];
	  }
	  else {
	    $path = 'downtimes';
	  }

          $path = isset($_SESSION['Group_name']) ? 'node/' . $_SESSION['Group_id'] . '/' . 'downtimes' : 'downtimes';

	 // return confirm_form($form, t('Are you sure you want to confirm to cancel these items?'), $path, t('This action cannot be undone.'), t('Submit'), t('Cancel'));

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
	  $form['nodes'] = array('#prefix' => '<ul>', '#suffix' => '</ul>', '#tree' => TRUE);
	  $form['operation'] = array('#type' => 'hidden', '#value' => $edit);
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

