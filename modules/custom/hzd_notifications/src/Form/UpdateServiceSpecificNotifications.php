<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\UpdateServiceSpecificNotifications
 */

namespace Drupal\hzd_notifications\Form; 

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\Controller\HzdNotifications;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_notifications\HzdNotificationsHelper;

//define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
class UpdateServiceSpecificNotifications extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_service_specific_notifications';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL, $service_id = NULL, $type = NULL, $send_interval = NULL, $rel_type = NULL) {
    $services = array(t('Service')) + HzdNotificationsHelper::_services_list($rel_type);
    $content_types = HzdNotificationsHelper::_get_content_types($service_id, FALSE, $rel_type);
    $types = array('downtimes' => 1, 'problem' => 2 , 'release' => 3 , 'early_warnings' => 4);
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $uid = $uid ? $uid : \Drupal::currentUser()->id();
    $form['account'] = array('#type' => 'hidden', '#value' => $uid);
    $form['rel_type'] = array('#type' => 'hidden', '#value' => $rel_type);
    $form['sid'] = array('#type' => 'hidden', '#value' => $service_id);
    $form['services'] = array(
      '#type' => 'select',
      '#options' => $services,
      '#default_value' => $service_id,
      '#disabled' => TRUE,
      '#prefix' => "<div class = 'service_dropdown hzd-form-element'>",
      '#suffix' => '</div>', 
    );

    $form['content_type'] = array(
      '#type' => 'select',
      '#default_value' => $types[$type],
      '#attributes' =>  array('class' => ['notification_content_type_' . $service_id]),
      '#options' => $content_types,
      '#prefix' => "<div class = 'content-type hzd-form-element'>",
      '#suffix' => '</div>',
      '#disabled' => TRUE,
    );

    $form['send_interval'] = array(
      '#type' => 'select',
      '#default_value' => $send_interval,
      '#attributes' =>  array('class' => ['notification_interval_' . $service_id]),
      '#options' => $intervals,
      '#prefix' => "<div class = 'send-interval hzd-form-element'>",
      '#suffix' => '</div>',
    );

    $form['submit'] = array('#type' => 'submit', '#value' => t('update'));
    $form['delete'] = array(
      '#attributes' =>  array('sid' => $service_id, 'uid' => $uid, 'rel_type' => $rel_type,'hzdAction'=>'delete'),
      '#type' => 'button',
      '#value' => t('Delete')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $service = $form_state->getValue('services');
    $content_type = $form_state->getValue('content_type');
    $send_interval = $form_state->getValue('send_interval');
    $rel_type = $form_state->getValue('rel_type');
    $uid = $form_state->getValue('account');
    $types = HzdNotificationsHelper::hzd_get_content_type_name($rel_type);

    $intval = db_query("SELECT send_interval FROM {service_notifications_override} WHERE service_id = :sid AND type = :type 
             AND uid = :uid AND rel_type = :rel_type", 
             array(":sid" => $service, ":type" => $types[$content_type], ":uid" => $uid, ":rel_type" => $rel_type))->fetchField();
    if($intval != $send_interval) {
      // update service notifications override table
      db_update('service_notifications_override')->fields(array('send_interval' => $send_interval))
	      ->condition('service_id', $service)
	      ->condition('type', $types[$content_type])
	      ->condition('uid', $uid)
        ->execute();
      // remove the default interval of particular service and update the overrided interval
      HzdNotificationsHelper::hzd_update_content_type_intval($service, $send_interval, $uid, $types[$content_type], $intval);
    }
  }

}
