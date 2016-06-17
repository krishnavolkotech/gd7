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
  public function buildForm(array $form, FormStateInterface $form_state, $rel_type = NULL) {
    $form['rel_type'] = array('#type' => 'hidden', '#value' => $rel_type);
    $uid = \Drupal::currentUser()->id();
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $content_types = $this->_service_notifications_content_type($rel_type);
    $default_interval = HzdNotificationsHelper::_get_default_timeintervals($uid, $rel_type);
    if($rel_type == KONSONS) {
      $types = array(1 => 'downtimes', 'problem', 'release', 'early_warnings');
    }
    else {
      $types = array(1 => 'release', 'early_warnings');
    }

    /*foreach($content_types as $content_key => $content) {
      $form['subscriptions_type_' . $content_key] = array(
        '#markup' => $content,
        '#prefix' => "<div class = 'hzd_type'>",
        '#suffix' => "</div>"
      );

      $form['subscriptions_interval_' . $content_key] = array(
        '#type' => 'radios',
        '#options' => $intervals,
        '#default_value' => $default_interval[$types[$content_key]] ? $default_interval[$types[$content_key]] : -1,
        '#prefix' => "<div class = 'hzd_time_interval'>",
        '#suffix' => "</div>"
      );
    }*/

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
  
    $uid = \Drupal::currentUser()->id();
    $rel_type = $form_state->getValue('rel_type');
    
    if($rel_type == KONSONS) {
      $types = array(1 => 'downtimes', 'problem', 'release', 'early_warnings');
    }
    else {
      $types = array(1 => 'release', 'early_warnings');
    }
    $content_types = $this->_service_notifications_content_type($rel_type);
    foreach($content_types as $content_key => $content) {
      $subscriptions = $form_state->getValue('subscriptions');
      $int_val = $subscriptions[$content_key]['subscriptions_interval_' . $content_key];
      HzdNotificationsHelper::insert_default_user_intervel($types[$content_key], $int_val, $uid, $rel_type);
    }
  }
  
  
  
    /*
     * service notifications content type
     */
    function _service_notifications_content_type($rel_type = KONSONS) {
      if ($rel_type == KONSONS) {
        return array('1' => 'Current Incidents and Planned Maintenances', 2 => 'Problems', 3 => 'Releases', 4 => 'Early Warnings');
      }
      else {
        return array('1' => 'Releases', 'Early Warnings');
      }
    }

}
