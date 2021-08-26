<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\resolve
 */

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cust_group\Controller\CustNodeController;

/**
 * Configure inactive_user settings for this site.
 */
class resolve_form extends FormBase {
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

  public function buildForm(array $form, FormStateInterface $form_state, $form_type = null) {
    
    $group = \Drupal::routeMatch()->getParameter('group');
      if (is_object($group)) {
        $group_id = $group->id();
      }
      else {
        $group_id = $group;
        $group = \Drupal\group\Entity\Group::load($group_id);
      }
    $current_user = \Drupal::service('current_user');
    
    
    $user_role = get_user_role();
    $type = ($form_type == 'resolve_maintenance' ? 'Maintenance' : 'Incident');
    $pos_slash = strripos($_GET['q'], '/');
    $nid = substr($_GET['q'], $pos_slash + 1);
    $breadcrumb = array();
    $breadcrumb[] = l(t('Home'), NULL);
    if (isset($_SESSION['Group_name'])) {
      $breadcrumb[] = l(t($_SESSION['Group_name']), 'node/' . $_SESSION['Group_id']);
      $breadcrumb[] = l(t('Incidents and Maintenances'), 'node/' . $_SESSION['Group_id'] . '/downtimes');
    } else {
      $breadcrumb[] = l(t('Incidents and Maintenances'), 'downtimes');
    }
    $breadcrumb[] = l(t('Downtime'), 'node/' . $nid);
    $breadcrumb[] = t('Resolve');
    $resolved_title = db_result(db_query("SELECT scheduled_p from {state_downtimes} WHERE down_id = %d", $nid));
    if ($resolved_title == 0) {
      drupal_set_title(t('Resolve Incident'));
    } else {
      drupal_set_title(t('Resolve Maintenances'));
    }
    drupal_set_breadcrumb($breadcrumb);
    $form['comment'] = array(
      '#type' => 'textarea',
      '#title' => t('Comment'),
      '#required' => TRUE,
      '#id' => 'reason',
      '#weight' => -2,
    );
    $user = \Drupal::currentUser();
    $groupMember = $group->getMember($user);
    if (in_array(SITE_ADMIN_ROLE, $user_role) || (CustNodeController::isGroupAdmin($group_id) == TRUE) || ($groupMember && group_request_status($groupMember))){
      $form['notifications']['#type'] = 'fieldset';
      $form['notifications']['#title'] = t('Notifications');
      $form['notifications']['#collapsible'] = TRUE;

      $form['notification']['notifications_content_disable'] = send_notification_form_element();
      $form['notification']['notifications_content_disable']['#weight'] = 2;

     /*
      $form['notifications']['notifications_content_disable'] = array(
        '#type' => 'checkbox',
        '#weight' => 2,
        '#title' => t('Do not send notifications for this update.'),
      );
      */
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

  function resolve_form_validate($form, &$form_state) {
    $sql = db_fetch_object(db_query("select startdate_planned  from {state_downtimes} where down_id = %d", $form_state['values']['nid']));
    $startdate = $sql->startdate_planned;
    $enddate = get_unix_timestamp($_POST['date_reported']['date']);
    $currentdate = time();
    if ($enddate <= $startdate) {
      form_set_error('', t('Resolve End date should be after start date.'));
    }
    if ($enddate > $currentdate) {
      form_set_error('', t('Actual end date cannot be in the future.'));
    }
  }

  /*
   * submit handler for the reolve form
   * form values are stored in the session for reusing the values after confirmation.
   */

  function resolve_form_submit($form, &$form_state) {
    $_SESSION['form_values'] = $form_state['values'];
    $_SESSION['form_values']['resolved_end_date'] = $form_state['clicked_button']['#post']['date_reported']['date'];
    if (isset($_SESSION['Group_name'])) {
      $path = 'node/' . $_SESSION['Group_id'] . '/confirm';
      $form_state['redirect'] = $path;
    } else {
      $form_state['redirect'] = 'confirm';
    }
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
    return parent::submitForm($form, $form_state);
  }
  
}
