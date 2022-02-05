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
class Cancelconfirm extends ConfirmFormBase {

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $id;

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
        $container->get('keyvalue.expirable')->get('downtimes_cancel_' . $nid)
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'cancelconfirmform';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    //  return t('Do you want to delete %id?', array('%id' => $this->id));
    return $this->t('Are you sure you want to confirm to cancel these items? This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    } else {
      $nid = $node;
    }
    $downtimes_resolve = $this->keyValueExpirable->get("downtimes_cancel_" . $nid);
    return new Url('downtimes.new_downtimes_controller_newDowntimes', ['group' => $downtimes_resolve['gid']]);
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
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    }
    else {
      $nid = $node;
    }
    $downtimes_resolve = $this->keyValueExpirable->get("downtimes_cancel_" . $nid);
    if (empty($downtimes_resolve)) {
      \Drupal::messenger()->addMessage($this->t('Invalid Request.'), 'error');
      return $this->redirect('<front>');
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* $user_role = get_user_role();
      extract($_SESSION['form_values']); */
    $message = 'Die Blockzeit wurde storniert.';
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      $nid = $node->id();
    }
    else {
      $nid = $node;
    }
    $downtimes_resolve = $this->keyValueExpirable->get("downtimes_cancel_" . $nid);


    

    $comment = $downtimes_resolve['comment']['value'];
    $nid = $downtimes_resolve['nid'];
    //$date_report = $downtimes_resolve['date_reported'];

    $record = array(
      'end_date' => REQUEST_TIME,
      'date_reported' => REQUEST_TIME,
      'comment' => $comment,
      'downtime_id' => $nid,
      'uid' => $user->id(),
      'type' => 0,
    );
    \Drupal::database()->insert('resolve_cancel_incident')->fields($record)->execute();

    $query = \Drupal::database()->update('downtimes');
    $query->fields([
      'cancelled' => 1,
    ]);
    $query->condition('downtime_id', $nid, '=');
    $query->execute();
    


    $this->keyValueExpirable->delete("downtimes_cancel_" . $nid);
    \Drupal::messenger()->addMessage(t($message));
    \Drupal\Core\Cache\Cache::invalidateTags(array('node:' . $nid));
    $form_state->setRedirect('downtimes.new_downtimes_controller_newDowntimes', ['group' => $downtimes_resolve['gid']]);
    if(!isset($downtimes_resolve['notifications_content_disable']) || $downtimes_resolve['notifications_content_disable'] != 1) {
      $downtime_node =  \Drupal\node\Entity\Node::load($nid);
      if ($downtime_node instanceof \Drupal\node\Entity\Node){
        $users = _get_subscribed_users($downtime_node);
        _notify_users($downtime_node, 'cancel', $users);        
      }
    }
  }

}
