<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\
 */

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use DateTime;
use Drupal;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\cust_group\Controller\CustNodeController;

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
        $container->get('keyvalue.expirable')->get("downtimes_cancel_" . $nid)
    );
  }

  /**
   * Constructs a ResolveForm object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   */
  public function __construct(KeyValueStoreExpirableInterface $key_value_expirable) {
    $this->keyValueExpirable = $key_value_expirable;
  }

  /*
   * Resolve Downtimes form
   */

  public function buildForm(array $form, FormStateInterface $form_state, $form_type = '') {
    $node = \Drupal::routeMatch()->getParameter('node');
    $query = Drupal::database()->select('resolve_cancel_incident','ri')
            ->condition('downtime_id',$node)
            ->fields('ri',['downtime_id'])
            ->execute()
            ->fetchField();
    if($query){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
      $group = \Drupal::routeMatch()->getParameter('group');
      if (is_object($group)) {
        $group_id = $group->id();
      }
      else {
        $group_id = $group;
        $group = \Drupal\group\Entity\Group::load($group_id);
      }
      
    $user = \Drupal::currentUser();
    $user_role = $user->getRoles();
    // User::getRoles($exclude_locked_roles = FALSE)    
    $type = ($form_type == 'resolve_maintenance' ? 'Maintenance' : 'Incident');
    if (is_object($node)) {
      $nid = $node->id();
    }
    else {
      $nid = $node;
    }
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
//    $query->range(1);
    $downtimeType = $query->execute()->fetchField();

//    $resolved_title = $services['scheduled_p'];

    if ($downtimeType == 0) {
      // drupal_set_title(t('Resolve Incident'));
      $form['#title'] = t('Cancel Incident');
    }
    else {
      // drupal_set_title(t('Resolve Maintenances'));
      $form['#title'] = t('Cancel Maintenances');
    }
    // drupal_set_breadcrumb($breadcrumb);

    $form['comment'] = array(
      '#type' => 'text_format',
      '#title' => t('Comment'),
      '#required' => TRUE,
      '#id' => 'reason',
      '#weight' => -2,
    );
    $groupMember = $group->getMember($user);
    if (in_array(SITE_ADMIN_ROLE, $user_role) || (CustNodeController::isGroupAdmin($group_id) == TRUE) || ($groupMember && $groupMember->getGroupContent()->get('request_status')->value == 1)) {
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
    $query = db_select('downtimes', 'd');
    $query->Fields("d");
    $query->where('d.downtime_id = ' . $nid);
    $nodeinfo = $query->execute()->fetchObject();
    $date_format = 'd.m.Y - H:i';

    $start_date = date($date_format, str_replace(' Uhr', '', $nodeinfo->startdate_planned));
    if (!empty($nodeinfo->enddate_planned)) {
      $end_date_planned = date($date_format, str_replace(' Uhr', '', $nodeinfo->enddate_planned));
    }
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
        '#description' => "Format : " . date($date_format, REQUEST_TIME),
      );
    }
    $first_month_first_day = date('Y-01-01 - 00:00');
    /* $form['date_reported'] = array(
      '#title' => t('Actual End Date'),
      '#type' => 'date_text',
      '#default_value' => $first_month_first_day,
      '#date_format' => 'd.m.Y - H:i',
      '#size' => 60,
      '#weight' => -3,
      '#required' => TRUE,
      '#suffix' => ($resolved_title == 1) ? t('Please enter the actual end date of the maintenance.') : t('Please enter the actual end date of the incident.'),
      ); */
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
    /*$query = \Drupal::database()->select('downtimes', 'd');
    $query->fields('d', ['startdate_planned']);
    $query->condition('d.downtime_id', $form_state->getValue('nid'), '=');
    $sql = $query->execute()->fetchObject();
    //$startdate = $sql->startdate_planned;
    //$enddate = get_unix_timestamp($_POST['date_reported']['date']);
    $startdate = DateTimePlus::createFromTimestamp($sql->startdate_planned)->getTimestamp();
    if(DateTime::createFromFormat('d.m.Y - H:i', $form_state->getValue('enddate_planned')) instanceof DateTime){
      $enddate = DateTimePlus::createFromFormat('d.m.Y - H:i',$form_state->getValue('enddate_planned'))->getTimestamp();
    }else{
      $form_state->setErrorByName('enddate_planned', $this->t('Invalid date format'));
    }
    $currentdate = REQUEST_TIME;
    if ($currentdate >= $startdate) {
      $form_state->setErrorByName('startdate_planned', $this->t('Cancel End date should not be after start date.'));
    }*/
  }

  /*
   * submit handler for the cancel form
   * form values are stored in the session for reusing the values after confirmation.
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //Todo pass form state values to confirm form
    $user = \Drupal::currentUser();
    $group = Drupal::routeMatch()->getParameter('group');
    $comment = $form_state->getValue('comment');
    $nid = $form_state->getValue('nid');
    $uid = $user->id();
    $notifications_content_disable = $form_state->getValue('notifications_content_disable');
    $downtime_resolve = array(
      'comment' => $comment,
      'nid' => $nid,
      'cancelled_end_date' => REQUEST_TIME,
      'uid' => $uid,
      'gid'=>$group,
      'notifications_content_disable' => $notifications_content_disable,
    );
    //Todo if more than one user access this might get issue
    $this->keyValueExpirable->setWithExpire("downtimes_cancel_" . $nid, $downtime_resolve, 6 * 60 * 60);

    // Redirect to the confirm form.
        $url = Url::fromRoute('downtimes.cancel_confirm',['group'=>$group,'node'=>$nid]);
    $form_state->setRedirectUrl($url);
  }

}
