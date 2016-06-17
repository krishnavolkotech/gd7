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

define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
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
  public function buildForm(array $form, FormStateInterface $form_state, $rel_type = NULL) {
    $services = array(t('Service')) + HzdNotificationsHelper::_services_list($rel_type);
    $form['services'] = array(
      '#type' => 'select',
      '#options' => $services,
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
      '#prefix' => "<div id ='service-content-types' >",
      '#suffix' => '</div>',
      '#options' => array('0' => 'Content Type'),
      '#prefix' => "<div class = 'content-type hzd-form-element'>",
      '#suffix' => '</div>',
    );

    $form['rel_type'] = array(
      '#type' => 'hidden',
      '#value' => $rel_type,
    );

    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $intervals[''] = t('Interval');
    $form['send_interval'] = array(
      '#type' => 'select',
      '#options' => $intervals,
      '#prefix' => "<div class = 'send-interval hzd-form-element'>",
      '#suffix' => '</div>',
    );

    $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));
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
    
  }
  
  // ajax callback function
  function service_content(array &$form, FormStateInterface $form_state) {
    $service = $form_state->getValue('services');
    $rel_type = $form_state->getValue('rel_type');
    $content_types = HzdNotificationsHelper::_get_content_types($service, FALSE, $rel_type);
    $form['content_type']['#options'] = $content_types;
    return $form['content_type'];
  }

}
