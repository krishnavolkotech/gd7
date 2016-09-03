<?php

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;

/**
 * Defines a confirmation form for deleting mymodule data.
 */
class Confirm extends ConfirmFormBase {
  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  // protected $id;

  /**
   * Constructs a ModulesUninstallConfirmForm object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   */
  public function __construct(KeyValueStoreExpirableInterface $key_value_expirable) {
    $this->keyValueExpirable = $key_value_expirable;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    }
    else {
      $nid = $node;
    }
    return new static(
        $container->get('keyvalue.expirable')->get('downtimes_resolve_' . $nid)
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // return t('Do you want to delete %id?', array('%id' => $this->id));

    /**
      if (isset($_SESSION['Group_name'])) {
      $path = 'node/' . $_SESSION['Group_id'] . '/' . $_SESSION['form_values']['type'];
      }
      else {
      $path = 'downtimes';
      }
     */
    return $this->t('Are you sure you want to confirm to resolve these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    //this needs to be a valid route otherwise the cancel link won't appear
    //   return new Url('mymodule.home');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    //   return t('Only do this if you are sure!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {

    return $this->t('Confirm');
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
    // $this->id = $id;
    //  return parent::buildForm($form, $form_state);
    /* $form['nodes'] = array('#prefix' => '<ul>', '#suffix' => '</ul>', '#tree' => TRUE);
      $form['operation'] = array('#type' => 'hidden', '#value' => $edit); */
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    }
    else {
      $nid = $node;
    }
    $downtimes_resolve = $this->keyValueExpirable->get("downtimes_resolve_" . $nid);
    if (empty($downtimes_resolve)) {
      drupal_set_message($this->t('Invalid Resolve.'), 'error');
      return $this->redirect('<front>');
    }
    return parent::buildForm($form, $form_state);
  }

  /*
   * Confirmation submit for the resolve form
   * resolved downtimes info is stored in the table "resolve_incident".
   * Updated the status of downtimes into the table "state_downtimes".
   * Inserts the nofication event for the resolved downtime 
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    // $user_role = get_user_role();
    //extract($_SESSION['form_values']);
    $message = 'Downtime has been resolved ';

    /* $date_report = get_timestamp($resolved_end_date);
      $date_report = get_unix_timestamp($resolved_end_date); */
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    }
    else {
      $nid = $node;
    }
    $downtimes_resolve = $this->keyValueExpirable->get("downtimes_resolve_" . $nid);

    $comment = $downtimes_resolve['comment']['value'];
    $nid = $downtimes_resolve['nid'];
    $date_report = $downtimes_resolve['date_reported'];

    $record = array(
      'end_date' => strtotime($date_report),
      'date_reported' => time(),
      'comment' => $comment,
      'downtime_id' => $nid,
      'uid' => $user->id(),
      'type' => 1,
    );
    db_insert('resolve_cancel_incident')->fields($record)->execute();

    $query = \Drupal::database()->update('downtimes');
    $query->fields([
      'resolved' => 1,
    ]);
    $query->condition('downtime_id', $nid, '=');
    $query->execute();
    $this->keyValueExpirable->delete("downtimes_resolve_" . $nid);
    drupal_set_message(t($message));
    $form_state->setRedirect('<front>');
    /* $node_resolve = \Drupal\node\Entity\Node::load($nid);
      $query = \Drupal::database()->select('downtimes', 'd');
      $query->fields('d', ['state_id']);
      $query->condition('d.downtime_id', $nid, '=');
      $query->range(1);
      $node_resolve->state = $query->execute()->fetchField();

      $query = \Drupal::database()->select('downtimes', 'd');
      $query->fields('d', ['service_id']);
      $query->condition('d.downtime_id', $nid, '=');
      $query->range(1);

      $node_resolve->service = $query->execute()->fetchField();


      $mode = 'Resolve';
      $path = $type;

      if (isset($_SESSION['Group_name'])) {
      // $path = 'node/' . $_SESSION['Group_id'] . '/downtimes';
      $path = Url::fromUserInput('/node/' . $_SESSION['Group_id'] . '/downtimes');
      }
      else {
      // $path = 'downtimes';
      $path = Url::fromUserInput('/downtimes');
      }
      unset($_SESSION['form_values']);

      $event = array(
      'module' => 'node',
      'uid' => $node_resolve->uid,
      'oid' => $node_resolve->nid,
      'type' => 'node',
      'action' => 'Resolve',
      'node' => $node_resolve,
      'params' => array('nid' => $node_resolve->nid),
      );


      if ($notifications_content_disable != 1) {
      # Use custom downtimes_notifications to send immediate downtimes notifications instead of notifications module.
      # Immediate notifications are still inserted into notifications_queue but get deleted by downtimes_notifications_notifications hook.
      # Digested notifications are handled by the default notifications module.
      notifications_event($event);
      $action = "Resolve";

      // downtimes_notifications_insert($node_resolve, $action);
      }
      $form_state->set('redirect', $path); */
  }

}
