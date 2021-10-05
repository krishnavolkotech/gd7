<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\ServiceSpecificNotificationsUserForm
 */

namespace Drupal\hzd_notifications\Form; 

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\Controller\HzdNotifications;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_notifications\HzdNotificationsHelper;

//define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
class ServiceSpecificNotificationsUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'set_service_specific_notifications';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL, $rel_type = NULL) {
    $services = array('<' . t('Service') . '>') + HzdNotificationsHelper::_services_list($rel_type);
    $uid = $uid ? $uid : \Drupal::currentUser()->id();
    $form_state_complete_form = $form_state->getCompleteForm();
    //$default_val = $form_state->getValue('services') ? $form_state->getValue('services') : '';
    if(empty($form_state_complete_form)) {
      $options = array('0' => '<' . t('Content Type') . '>');
    }
    else {
      $options = $this->service_content($form, $form_state)['#options'];
    }

    $form['services'] = array(
      '#type' => 'select',
      '#options' => $services,
      '#weight' => 1,
      '#ajax' => array(
          'callback' => '::service_content',
          'wrapper' =>  'service-content-types',
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
      ),
      '#prefix' => "<div class = 'service_dropdown hzd-form-element'>",
      '#suffix' => '</div>', 
    );

    $form['content_type'] = array(
      '#type' => 'select',
      '#weight' => 2,
      '#prefix' => "<div id ='service-content-types' class = 'content-type hzd-form-element'>",
      '#suffix' => '</div>',
      '#options' => $options,
    );

    $form['rel_type'] = array(
      '#type' => 'hidden',
      '#value' => $rel_type,
    );

    $form['account'] = array(
      '#type' => 'hidden',
      '#value' => $uid,
    );

    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $intervals[''] = '<' . t('Send notifications') . '>';
    $form['send_interval'] = array(
      '#type' => 'select',
      '#weight' => 3,
      '#options' => $intervals,
      '#prefix' => "<div class = 'send-interval hzd-form-element'>",
      '#suffix' => '</div>',
    );

    $form['submit'] = array('#type' => 'submit','#weight' => 4, '#value' => t('Save'));
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $service = $form_state->getValue('services');
    $content_type = $form_state->getValue('content_type');
    $send_interval = $form_state->getValue('send_interval');
    if($service == 0) {
      $form_state->setErrorByName('services', $this->t('Select Service'));
    }
    if($content_type == 0) {
      $form_state->setErrorByName('content_type', $this->t('Select Content Type'));
    }
    if($send_interval == '') {
      $form_state->setErrorByName('send_interval', $this->t('Select Interval'));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::messenger()->addMessage(t('Service mail preferences saved successfully'));
    $service = $form_state->getValue('services');
    $content_type = $form_state->getValue('content_type');
    $send_interval = $form_state->getValue('send_interval');
    $rel_type = $form_state->getValue('rel_type');
    $uid = $form_state->getValue('account');
    $types = HzdNotificationsHelper::hzd_get_content_type_name($rel_type);
    
    $service_override_record = array('service_id' => $service, 'type' => $types[$content_type], 'send_interval' => $send_interval,'uid' => $uid, 'rel_type' => $rel_type );
    $db = \Drupal::database();
    $checkId = $db->select('service_notifications_override','sno')
      ->fields('sno',['sid'])
      ->condition('service_id',$service)
      ->condition('type',$types[$content_type])
      ->condition('rel_type',$rel_type)
      ->condition('uid',$uid)
      ->execute()
      ->fetchField();
    if(!empty($checkId)){
      $update = $db->update('service_notifications_override')
        ->fields($service_override_record)
        ->condition('sid',$checkId)
        ->execute();
    }else{
      $insert = $db->insert('service_notifications_override')
        ->fields($service_override_record)
        ->execute();
    }

    // get user default interval of a particlural type
    $default_intval = HzdNotificationsHelper::hzd_default_content_type_intval($uid, $types[$content_type], $rel_type);

    // remove the default interval of particular service and update the overrided interval
    HzdNotificationsHelper::hzd_update_content_type_intval($service, $send_interval, $uid, $types[$content_type], $default_intval);
  }

  // ajax callback function
  public function service_content(array &$form, FormStateInterface $form_state) {
    $service = $form_state->getValue('services');
    $rel_type = $form_state->getValue('rel_type');
    $content_types = HzdNotificationsHelper::_get_content_types($service, FALSE, $rel_type);
    $form['content_type']['#options'] = $content_types;
    return $form['content_type'];
  }
}
