<?php

namespace Drupal\downtimes\Form;

use Drupal;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @param KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   */
  public function __construct(KeyValueStoreExpirableInterface $key_value_expirable) {
    $this->keyValueExpirable = $key_value_expirable;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $node = Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    } else {
      $nid = $node;
    }
    return new static(
            $container->get('keyvalue.expirable')->get('downtimes_resolve_')
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
    $node = Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    } else {
      $nid = $node;
    }
    $downtimes_resolve = $this->keyValueExpirable->get("downtimes_resolve_" . $nid);
    return new Url('downtimes.new_downtimes_controller_newDowntimes', ['group' => $downtimes_resolve['gid']]);
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
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param int $id
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $node = Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    } else {
      $nid = $node;
    }
    $downtimes_resolve = $this->keyValueExpirable->get("downtimes_resolve_" . $nid);
    if (empty($downtimes_resolve)) {
      \Drupal::messenger()->addMessage($this->t('Invalid Resolve.'), 'error');
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
    $user = Drupal::currentUser();
    $node = Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    } else {
      $nid = $node;
    }
    $message = 'Downtime has been resolved ';
    $downtimeType = \Drupal::database()->select('downtimes','d')
      ->fields('d',['scheduled_p'])
      ->condition('downtime_id',$nid)
      ->execute()
      ->fetchField();
    if($downtimeType == 1){
      $message = 'Maintenance has been resolved';
    }else{
      $message = 'Incident has been resolved';
    }
    $downtimes_resolve = $this->keyValueExpirable->get("downtimes_resolve_" . $nid);
    
    
    $comment = $downtimes_resolve['comment']['value'];
    $nid = $downtimes_resolve['nid'];
    $date_report = $downtimes_resolve['date_reported'];
    $enddate = DateTimePlus::createFromFormat('d.m.Y - H:i',$date_report)->getTimestamp();
    $record = array(
      'end_date' => $enddate,
      'date_reported' => REQUEST_TIME,
      'comment' => $comment,
      'downtime_id' => $nid,
      'uid' => $user->id(),
      'type' => 1,
    );
    \Drupal::database()->insert('resolve_cancel_incident')->fields($record)->execute();

    $query = Drupal::database()->update('downtimes');
    $query->fields([
      'resolved' => 1,
      'enddate_reported' => $enddate,
    ]);
    $query->condition('downtime_id', $nid, '=');
    $query->execute();
    $this->keyValueExpirable->delete("downtimes_resolve_" . $nid);
    \Drupal::messenger()->addMessage(t($message));
    \Drupal\Core\Cache\Cache::invalidateTags(array('node:' . $nid));
    $form_state->setRedirect('downtimes.new_downtimes_controller_newDowntimes', ['group' => $downtimes_resolve['gid']]);
    if(!isset($downtimes_resolve['notifications_content_disable'])  ||  $downtimes_resolve['notifications_content_disable'] != 1) {
      $downtime_node =  \Drupal\node\Entity\Node::load($nid);
      if ($downtime_node instanceof \Drupal\node\Entity\Node){
        $users = _get_subscribed_users($downtime_node);
        _notify_users($downtime_node, 'resolve', $users);
        // send_downtime_notifications($downtime_node, 'resolve');
      }
    }
  }
}
