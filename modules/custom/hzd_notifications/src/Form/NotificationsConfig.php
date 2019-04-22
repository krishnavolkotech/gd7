<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 30/1/17
 * Time: 12:05 PM
 */

namespace Drupal\hzd_notifications\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\HzdNotificationsHelper;
use Drupal\node\Entity\NodeType;

class NotificationsConfig extends FormBase {
  
  
  /**
   * @return string
   */
  public function getFormId() {
    return 'notifications_config';
  }
  
  
  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
//    $uid = $uid ? $uid : \Drupal::currentUser()->id();
//    $vals =
//    $notificationNodetypes = ['group', 'downtimes', 'release', 'problem', 'early_warning', 'quickinfo'];
    $saveData = \Drupal::state()->get('NotificationDefaults');

//    pr($saveData);exit;
    $form['group'] = array(
      '#title' => $this->t('Default Group Notification'),
      '#type' => 'radios',
      '#default_value' => $saveData['group'],
      '#options' => $intervals,
    );
    
    $notificationNodetypes = ['downtimes', 'release', 'problem', 'early_warnings', 'quickinfo', 'release_comments'];
    foreach ($notificationNodetypes as $notificationNodetype) {
      $title = NodeType::load($notificationNodetype)->label();
      $form['service'][$notificationNodetype] = array(
        '#title' => $title,
        '#type' => 'radios',
        '#options' => $intervals,
        '#default_value' => isset($saveData[$notificationNodetype]) ? $saveData[$notificationNodetype] : -1,
      );
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    return $form;
  }
  
  function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    \Drupal::state()->set('NotificationDefaults', $form_state->getValues());
  }
}