<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\ScheduledmaintenanceForm.
 */

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

// use Drupal\problem_management\HzdStorage;
// use Drupal\problem_management\Inactiveuserhelper;
// use Drupal\Core\Datetime\DateFormatter;
// use Drupal\hzd_customizations\HzdcustomisationStorage;
/**
 * Configure inactive_user settings for this site.
 */
class ScheduledmaintenanceForm extends ConfigFormBase {
  
  //  protected $dateFormatter;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'downtime_scheduledmaintenance_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'downtimes.settings',
    ];
  }

  /*
   * Menu callback; admin settings form for Scheduled Maintenance Group ID.
   */

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('downtimes.settings');
    $form['#tree'] = TRUE;
    $form['maintenance_advance_time'] = array(
      '#type' => 'textfield',
      '#title' => t('Minimum time to schedule a maintenance in advance (in minutes)'),
      '#default_value' => \Drupal::config('downtimes.settings')->get('maintenance_advance_time'),
      '#required' => TRUE,
    );
    $savedConfigFields = $config->get('sitewide_maintenance_windows');    
    $max = count($savedConfigFields);
    if(empty($max)) {
      $max = 0;
    }
    if($form_state->isSubmitted()){
      $max = $form_state->get('fields_count');
    }
    $form_state->set('fields_count', $max);
    $form['sitewide_maintenance_windows'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $arr = array(
      "Mon" => t("Mon"),
      "Tue" => t("Tue"),
      "Wed" => t("Wed"),
      "Thu" => t("Thu"),
      "Fri" => t("Fri"),
      "Sat" => t("Sat"),
      "Sun" => t("Sun")
    );
    
    for($delta=0; $delta<$max; $delta++) {
      if (!isset($form['sitewide_maintenance_windows'][$delta])) {
        if(!empty($savedConfigFields[$delta]['time_from'])){
          $fromTime = DrupalDateTime::createFromDateTime(\DateTime::createFromFormat('H:i:s',$savedConfigFields[$delta]['time_from']));
        }else{
          $fromTime = '';
        }
        if(!empty($savedConfigFields[$delta]['time_to'])){
          $untilTime = DrupalDateTime::createFromDateTime(\DateTime::createFromFormat('H:i:s',$savedConfigFields[$delta]['time_to']));
        }else{
          $untilTime = '';
        }
        
        $form['sitewide_maintenance_windows'][$delta] = [
          '#type' => 'fieldset',
          '#title'=>'',
          '#prefix' => '<div id="downtimes-fieldset-wrapper">',
          '#suffix' => '</div>',
        ];
        $form['sitewide_maintenance_windows'][$delta]['day_from'] = array(
          '#type' => 'select',
          '#options' => $arr,
          '#title' => 'From',
          '#default_value'=>isset($savedConfigFields[$delta]['day_from'])?$savedConfigFields[$delta]['day_from']:''
        );
        
        $form['sitewide_maintenance_windows'][$delta]['time_from'] = array(
          '#type' => 'datetime',
          '#date_time_element'=>'time',
          '#date_date_element'=>'none',
          '#default_value'=>$fromTime
        );
        
        $form['sitewide_maintenance_windows'][$delta]['day_to'] = array(
          '#type' => 'select',
          '#options' => $arr,
          '#title' => 'Until',
          '#default_value'=>isset($savedConfigFields[$delta]['day_to'])?$savedConfigFields[$delta]['day_to']:''
        );
        
        $form['sitewide_maintenance_windows'][$delta]['time_to'] = array(
          '#type' => 'datetime',
          '#date_time_element'=>'time',
          '#date_date_element'=>'none',
          '#default_value'=>$untilTime
        );
        $form['sitewide_maintenance_windows'][$delta]['is_deleted'] = array(
          '#type' => 'hidden',
          '#default_value'=>0,
          '#attributes'=>['class'=>['isDeleted']]
        );
      }
      $form['sitewide_maintenance_windows'][$delta]['remove'] = array(
        '#type' => 'button',
        '#value'=>'Remove',
        '#attributes'=>['class'=>['btn-error'],'onclick'=>"jQuery(this).parents('#downtimes-fieldset-wrapper').find('.isDeleted').val(1);jQuery(this).parents('#downtimes-fieldset-wrapper').hide();return false;"]
      );
    }
    
    $form['sitewide_maintenance_windows']['add'] = array(
      '#type' => 'submit',
      '#name' => 'addfield',
      '#value' => t('Add more field'),
      '#submit' => array(array($this, 'addfieldsubmit')),
      '#ajax' => array(
        'callback' => array($this, 'addfieldCallback'),
        'wrapper' => 'names-fieldset-wrapper',
        'effect' => 'fade',
      ),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }

  /**
    * Ajax submit to add new field.
    */
  public function addfieldsubmit(array &$form, FormStateInterface &$form_state) {
    $max = $form_state->get('fields_count') + 1;
    $form_state->set('fields_count',$max);
    $form_state->setRebuild(TRUE);
  }

  /**
    * Ajax callback to add new field.
    */
  public function addfieldCallback(array &$form, FormStateInterface &$form_state) {
    return $form['sitewide_maintenance_windows'];
  }
  
  

/*  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('fields_count');
    $remove_element = $form_state->getTriggeringElement();
    preg_match('/remove_name_(\d)/', $remove_element['#name'], $matches);
    dsm($matches);
    //dsm($form['sitewide_maintenance_windows']["konsens_mw_hm_from" . $matches[1]]);
    if (isset($matches[1])) {
      $config = \Drupal::service('config.factory')->getEditable('downtimes.settings');
      $config->set("konsens_mw_day_from_delete" . $matches[1], 1)->save();
      unset($form['sitewide_maintenance_windows']["konsens_mw_day_from" . $matches[1]]);
      unset($form['sitewide_maintenance_windows']["konsens_mw_hm_from" . $matches[1]]);
      unset($form['sitewide_maintenance_windows']["konsens_mw_minutes_from" . $matches[1]]);
      unset($form['sitewide_maintenance_windows']["konsens_mw_day_until" . $matches[1]]);
      unset($form['sitewide_maintenance_windows']["konsens_mw_hm_until" . $matches[1]]);
      unset($form['sitewide_maintenance_windows']["konsens_mw_minutes_until" . $matches[1]]);
      unset($form['sitewide_maintenance_windows']["remove_name_" . $matches[1]]);
    }
    $form_state->setRebuild();
  }
*/
  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $validateDays = array(1=>"Mon",2=>"Tue",3=>"Wed",4=>"Thu",5=>"Fri",6=>"Sat",7=>"Sun");
    $sitewide_maintenance_windows = $form_state->getValue('sitewide_maintenance_windows');
    foreach($sitewide_maintenance_windows as $key=>$field){
      if((string)$key != 'add' && $field['is_deleted'] == 0){
        if(array_search($field['day_from'],$validateDays) > array_search($field['day_to'],$validateDays)){
          $form_state->setErrorByName('sitewide_maintenance_windows',$this->t('From day should be before Until day'));
        }
        if(array_search($field['day_from'],$validateDays) == array_search($field['day_to'],$validateDays) && $field['time_from'] > $field['time_to']){
          $form_state->setErrorByName('sitewide_maintenance_windows',$this->t('From time should be before Until time'));
        }
      }
      
    }
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  /*
   * submit handler for the problems settings page
   * selected services for the individual groups are stored in the table "group_problems_view"
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //pr($form_state->getValue('sitewide_maintenance_windows'));exit;
    $config = \Drupal::service('config.factory')->getEditable('downtimes.settings');
    $config->set('maintenance_advance_time', $form_state->getValue('maintenance_advance_time'))
        ->save();
    $sitewide_maintenance_windows = $form_state->getValue('sitewide_maintenance_windows');
    unset($sitewide_maintenance_windows['add']);
    $data = [];
    foreach($sitewide_maintenance_windows as $key=>$field){
      unset($field['remove']);
      if($field['is_deleted'] == 0){
        $data[$key]['day_from'] = $field['day_from'];
        $data[$key]['day_to'] = $field['day_to'];
        $data[$key]['time_from'] = isset($field['time_from'])?$field['time_from']->format('H:i:s'):'';
        $data[$key]['time_to'] = isset($field['time_to'])?$field['time_to']->format('H:i:s'):'';
      }
    }
    $config->set('sitewide_maintenance_windows', $data)->save();
    parent::submitForm($form, $form_state);
  }
  
  /*

  // Add day dropdown to maintenance window
  function konsens_mw_field_day(array &$form, FormStateInterface $form_state, $name, $options, $data, $title, $add_prefix = 0) {
    $form['sitewide_maintenance_windows'][$name] = array(
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $data,
      '#title' => t($title),
    );
    if ($add_prefix) {
      $form['sitewide_maintenance_windows'][$name]['#prefix'] = '<div class=mw-item-set>';
    }
  }

  // Add Hour Minute dropdown to maintenance window
  function konsens_mw_field_hours_minutes(array &$form, FormStateInterface $form_state, $name, $data) {

    $date_format = 'H:i';
    $form['sitewide_maintenance_windows'][$name] = array(
      //'#title' => t($title),
      '#type' => 'datetime',
      '#date_format' => $date_format,
      '#date_label_position' => 'within',
      '#default_value' => $data,
    );
  }

  function sitewide_mw_remove_button(array &$form, FormStateInterface $form_state, $name) {
    $form['sitewide_maintenance_windows'][$name] = array(
      '#value' => "<a href='' class='mw-remove' id='$name'>Remove</a></div>",
    );
  }
*/
}
