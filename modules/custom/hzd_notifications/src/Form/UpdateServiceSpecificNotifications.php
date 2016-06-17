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

define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
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
  public function buildForm(array $form, FormStateInterface $form_state, $service_id = NULL, $type = NULL, $send_interval = NULL, $rel_type = NULL) {
    $services = array(t('Service')) + HzdNotificationsHelper::_services_list($rel_type);
    $content_types = HzdNotificationsHelper::_get_content_types($service_id, FALSE, $rel_type);
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $uid = \Drupal::currentUser()->id();
     $form['services'] = array(
      '#type' => 'select',
      '#options' => $services,
      '#default_value' => array($service_id),
      '#disabled' => TRUE,
      '#prefix' => "<div class = 'service_dropdown hzd-form-element'>",
      '#suffix' => '</div>', 
    );

    $form['content_type'] = array(
      '#type' => 'select',
      '#default_value' => array($type),
      '#attributes' =>  array('class' => 'notification_content_type_' . $service_id),
      '#options' => $content_types,
      '#prefix' => "<div class = 'content-type hzd-form-element'>",
      '#suffix' => '</div>',
    );
    
    $form['send_interval'] = array(
      '#type' => 'select',
      '#default_value' => array($send_interval),
      '#attributes' =>  array('class' => 'notification_interval_' . $service_id),
      '#options' => $intervals,
      '#prefix' => "<div class = 'send-interval hzd-form-element'>",
      '#suffix' => '</div>',
    );

    $form['submit'] = array('#type' => 'submit', '#value' => t('update'));
    $form['delete'] = array(
      '#attributes' =>  array('sid' => $service_id, 'uid' => $uid, 'rel_type' => $rel_type),
      '#type' => 'button',
      '#value' => t('Delete')
    );
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

}
