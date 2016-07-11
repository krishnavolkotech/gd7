<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\ServiceNotificationsUserForm
 */

namespace Drupal\hzd_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\Controller\HzdNotifications;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_notifications\HzdNotificationsHelper;

define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
class ServiceNotificationsUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'service_notifications_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL, $rel_type = NULL) {
    $form['rel_type'] = array('#type' => 'hidden', '#value' => $rel_type);
    $uid = $uid ? $uid : \Drupal::currentUser()->id();
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $content_types = HzdNotificationsHelper::service_notifications_content_type($rel_type);
    $default_interval = HzdNotificationsHelper::_get_default_timeintervals($uid, $rel_type);
    $form['account'] = array('#type' => 'value', '#value' => $uid);
    if($rel_type == KONSONS) {
      $types = array(1 => 'downtimes', 'problem', 'release', 'early_warnings');
    }
    else {
      $types = array(1 => 'release', 'early_warnings');
    }

    $form['subscriptions'] = array(
      '#type' => 'table',
      '#header' => '',
    );

    foreach($content_types as $content_key => $content) {
      $form['subscriptions'][$content_key]['subscriptions_type_' . $content_key] = array(
        '#markup' => $content,
        '#prefix' => "<div class = 'hzd_type'>",
        '#suffix' => "</div>"
      );
      $form['subscriptions'][$content_key]['subscriptions_interval_' . $content_key] = array(
        '#type' => 'radios',
        '#options' => $intervals,
        '#default_value' => isset($default_interval[$types[$content_key]]) ? $default_interval[$types[$content_key]] : -1,
        '#prefix' => "<div class = 'hzd_time_interval'>",
        '#suffix' => "</div>"
      );
    }

    if ($rel_type == KONSONS) {
      //Getting the default time intervals for the planning files of release management
      $planning_files_default_interval = db_query("SELECT default_send_interval FROM {planning_files_notifications_default_interval} 
                                         WHERE uid = :uid", array(":uid" => $uid))->fetchField();
      $form['subscriptions'][5]['subscriptions_type_5'] = array(
        '#markup' => t("Planning Files"),
        '#prefix' => "<div class = 'hzd_type'>",
        '#suffix' => "</div>"
      );
      $form['subscriptions'][5]['subscriptions_interval_5'] = array(
        '#type' => 'radios',
        '#options' => $intervals,
        '#default_value' => ($planning_files_default_interval == 0 || $planning_files_default_interval) ? $planning_files_default_interval : -1,
        '#prefix' => "<div class = 'hzd_time_interval'>",
        '#suffix' => '</div>'
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('account');
    $rel_type = $form_state->getValue('rel_type');
    $types = HzdNotificationsHelper::hzd_get_content_type_name($rel_type);
    $content_types = HzdNotificationsHelper::service_notifications_content_type($rel_type);
    $subscriptions = $form_state->getValue('subscriptions');
    $default_interval = hzd_get_default_interval($uid, $rel_type);
    // get all services
    $services = hzd_get_all_services($rel_type);

    //get default interval of each services and type
    $user_notifications = HzdNotificationsHelper::hzd_get_user_notifications($uid, $rel_type, $services, $default_interval);
    foreach($content_types as $content_key => $content) {
      $int_val = $subscriptions[$content_key]['subscriptions_interval_' . $content_key];
      HzdNotificationsHelper::insert_default_user_intervel($types[$content_key], $int_val, $uid, $rel_type);
      if($default_interval[$types[$content_key]] != $int_val) {
        // get all services to update the interval
        HzdNotificationsHelper::hzd_modify_services($user_notifications, $types[$content_key], $uid, $default_interval[$types[$content_key]], $int_val);
      }
    }

    // planning files notifications
    if($rel_type == KONSONS) {
      $pf_int_val = $subscriptions[5]['subscriptions_interval_5'];
      $default_pf_interval = HzdNotificationsHelper::get_default_pf_timeintervals($uid);
      HzdNotificationsHelper::insert_default_pf_user_intervel($pf_int_val, $uid);
      if(($pf_int_val != $default_pf_interval) || ($default_pf_interval == '')) {
        //remove notifications from previous interval and update with user submitted interval
        HzdNotificationsHelper::hzd_modify_pf_notifications($uid, $pf_int_val, $default_pf_interval);
      }
    }
  }

}
