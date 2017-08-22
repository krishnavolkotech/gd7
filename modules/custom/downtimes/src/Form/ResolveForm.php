<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\
 */

namespace Drupal\downtimes\Form;

use DateTime;
use Drupal;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cust_group\Controller\CustNodeController;

/*
 * Resolve Downtimes form
 */

class ResolveForm extends FormBase {
  //  protected $dateFormatter;

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
            $container->get('keyvalue.expirable')->get("downtimes_resolve_")
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
    $node = Drupal::routeMatch()->getParameter('node');
    $query = Drupal::database()->select('resolve_cancel_incident','ri')
            ->condition('downtime_id',$node->id())
            ->fields('ri',['downtime_id'])
            ->execute()
            ->fetchField();
    if($query){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
//    $group = \Drupal::routeMatch()->getParameter('group');
//      if (is_object($group)) {
//        $group_id = $group->id();
//      }
//      else {
//        $group_id = $group;
    $group = \Drupal\group\Entity\Group::load(INCIDENT_MANAGEMENT);
//      }


    $user = Drupal::currentUser();
    $user_role = $user->getRoles();
    // User::getRoles($exclude_locked_roles = FALSE)
    $type = ($form_type == 'resolve_maintenance' ? 'Maintenance' : 'Incident');
    
//    $node = Drupal\group\Entity\GroupContent::load($group_content_id);
//    $node = $group_content->getEntity();
    if (is_object($node)) {
      $nid = $node->id();
    } else {
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

    $query = Drupal::database()->select('downtimes', 'd');
    $query->fields('d', ['scheduled_p']);
    $query->condition('d.downtime_id', $nid, '=');
//    $query->range(1);
    $resolved_title = $query->execute()->fetchField();

//    $resolved_title = $services['scheduled_p'];

    if ($resolved_title == 0) {
      // drupal_set_title(t('Resolve Incident'));
      $form['#title'] = t('Resolve Incident');
    } else {
      // drupal_set_title(t('Resolve Maintenances'));
      $form['#title'] = t('Resolve Maintenance');
    }
    // drupal_set_breadcrumb($breadcrumb);

    $form['comment'] = array(
        '#type' => 'text_format',
        '#title' => t('Comment'),
        '#required' => TRUE,
        '#id' => 'reason',
        '#allowed_formats'  => ['basic_html'],
        '#format' => 'basic_html',
        '#weight' => -2,
    );
    $groupMember = $group->getMember($user);
    //Only site admins have the checkbox not to send notifications
    if (array_intersect(['site_administrator', 'administrator'], $user_role)) {
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
      );
    }

    //$first_month_first_day = date('01.01.Y - 00:00');
    $first_month_first_day = $start_date;

    $form['date_reported'] = array(
        '#title' => t('Actual End Date'),
        '#type' => 'textfield',
        '#default_value' => $first_month_first_day,
        '#date_format' => $date_format,
        '#size' => 60,
        '#weight' => -3,
        '#required' => TRUE,
        '#description' => "Format : " . date($date_format, REQUEST_TIME),
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
    $query = Drupal::database()->select('downtimes', 'd');
    $query->fields('d', ['startdate_planned']);
    $query->condition('d.downtime_id', $form_state->getValue('nid'), '=');
    $sql = $query->execute()->fetchObject();
    $enddate = 0;
    $startdate = DateTimePlus::createFromTimestamp($sql->startdate_planned)->getTimestamp();
//echo $this->isValidDate($form_state->getValue('date_reported'));exit;
    if ($this->isValidDate($form_state->getValue('date_reported'))) {
      $enddate = DateTimePlus::createFromFormat('d.m.Y - H:i', $form_state->getValue('date_reported'), null, ['validate_format' => false])->getTimestamp();
    } else {
      $form_state->setErrorByName('date_reported', $this->t('Invalid date format'));
    }

    $currentdate = REQUEST_TIME;
    if ($enddate <= $startdate) {
      $form_state->setErrorByName('date_reported', $this->t('Resolve End date should be after start date.'));
    }

    if ($enddate > $currentdate) {
      $form_state->setErrorByName('date_reported', $this->t('Actual end date cannot be in the future.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //Todo pass form state values to confirm form
    $group = \Drupal\group\Entity\Group::load(INCIDENT_MANAGEMENT);
    $user = Drupal::currentUser();
    $comment = $form_state->getValue('comment');
    $nid = $form_state->getValue('nid');
    $date_reported = $form_state->getValue('date_reported');
    $uid = $user->id();
    $notifications_content_disable = $form_state->getValue('notifications_content_disable');
    $downtime_resolve = array(
        'comment' => $comment,
        'nid' => $nid,
        'date_reported' => $date_reported,
        'uid' => $uid,
        'gid' => $group->id(),
        'notifications_content_disable' => $notifications_content_disable,
    );
    $key = "downtimes_resolve_" . $nid;
    //Todo if more than one user access this might get issue
    $this->keyValueExpirable->setWithExpire($key, $downtime_resolve, 24 * 60 * 60);

    // Redirect to the confirm form.
    $url = Url::fromRoute('downtimes.confirm', ['group' => $group->id(), 'node' => $nid]);
    $form_state->setRedirectUrl($url);
  }

  function isValidDate($date, $format = 'd.m.Y - H:i') {
    $f = DateTime::createFromFormat($format, $date);
    $valid = DateTime::getLastErrors();
    return ($valid['warning_count'] == 0 and $valid['error_count'] == 0);
  }

}
