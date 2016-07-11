<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\ScheduledmaintenanceForm.
 */

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
   // drupal_add_js(drupal_get_path('module', 'downtimes') . '/sitewide_maintenance_windows.js');

    $form['#attached']['library'] = array('downtimes/downtimes.sitewardjs');

    $form['maintenance_advance_time'] = array(
      '#type' => 'textfield',
      '#title' => t('Minimum time to schedule a maintenance in advance (in minutes)'),
      '#default_value' => \Drupal::config('downtimes.settings')->get('maintenance_advance_time'),
      '#required' => TRUE,
    );

   $form['sitewide_maintenance_windows'] = array(
     '#title' => t("KONSENS-wide Maintenance Windows"),
     // The prefix/suffix provide the div that we're replacing, named by
     // #ajax['wrapper'] above.
     '#prefix' => '<div id="sitewide_maintenance_windows">',
     '#suffix' => '</div>',
     '#type' => 'fieldset',
   );
  
   $num_items = !empty($form_state->getValues('howmany')) ? $form_state->getValues('howmany') : 0;
   $val_inc = $num_items;

   if(empty($form_state->getValues('remove-item'))) {
     $num_items ++;
   } else {
     $num_items--;
   }

   $form['sitewide_maintenance_windows']['howmany'] = array('#type' => 'hidden','#value' => $num_items);
   $form['sitewide_maintenance_windows']['remove-item'] = array('#type' => 'hidden','#value' => 0);
  // $arr = array(t("Mon") => "Mon", t("Tue") => "Tue", t("Wed") => "Wed", t("Thu") => "Thu", t("Fri") => "Fri", t("Sat") => "Sat", t("Sun") => "Sun");
   $arr = array(
     "Mon" => t("Mon"), 
     "Tue" => t("Tue"), 
     "Wed" => t("Wed"), 
     "Thu" => t("Thu"), 
     "Fri" => t("Fri"), 
     "Sat" => t("Sat"), 
     "Sun" => t("Sun")
   );
   $post = $form_state->getValue('post');
   if(isset($post) && !empty($post) ) {
   $values_array = array();
   $v_index = 1;
   $first_month_first_day = date('Y-01-01');
   for($v_inc = 1; $v_inc <= $val_inc; $v_inc++) {
     if($form_state->getValues('remove-item') == $v_inc) {
       continue;
     }
     $data = $form_state->getValues("konsens_mw_day_from$v_inc");
     if(isset($data)) {
       $hm_from = date_create($first_month_first_day.' '.$form_state->getValues('hour').':'.$form_state->getValues('minute'));
       $hm_from = date_format($hm_from, 'Y-m-d H:i:s');
       $hm_until = date_create($first_month_first_day.' '.$form_state->getValues('hour').':'.$form_state->getValues('minute'));
       $hm_until = date_format($hm_until, 'Y-m-d H:i:s');
       $values_array[$v_index] = array(
           'day_from' => $form_state->getValues("konsens_mw_day_from$v_inc"),
           'hm_from'  => $hm_from,
           'day_until' => $form_state->getValues("konsens_mw_day_until$v_inc"),
           'hm_until'  => $hm_until
       );
	$v_index++;
      }
    }
    for ($inc = 1; $inc <= $num_items; $inc++) {    
      if(!isset($values_array[$inc])) {
	$values_array[$inc] = array('day_from' => $form_state->getValues("konsens_mw_day_from$inc"),
                                    'hm_from'  => $form_state->getValues("konsens_mw_hm_from$inc"),
                                    'day_until' => $form_state->getValues("konsens_mw_day_until$inc"),
                                    'hm_until'  => $form_state->getValues("konsens_mw_day_until$inc"));
	
      }

        $this->konsens_mw_field_day($form, $form_state, "konsens_mw_day_from".$inc, $arr, $values_array[$inc]['day_from'], 'From', 1);
        $this->konsens_mw_field_hours_minutes($form, $form_state,  "konsens_mw_hm_from".$inc, $values_array[$inc]['hm_from']);
        $this->konsens_mw_field_day($form, $form_state,  "konsens_mw_day_until".$inc, $arr, $values_array[$inc]['day_until'], 'Until');
        $this->konsens_mw_field_hours_minutes($form, $form_state,  "konsens_mw_hm_until".$inc, $values_array[$inc]['hm_until']);
        $this->sitewide_mw_remove_button($form, $form_state,  "remove-$inc");
    }
  } else {
    $inc = 0;

    $howmany = \Drupal::config('downtimes.settings')->get('howmany');

    if(!empty($howmany)) {
      for($inc = 1; $inc <= $howmany; $inc++) {
          $this->konsens_mw_field_day($form, $form_state,  "konsens_mw_day_from".$inc, $arr, \Drupal::config('downtimes.settings')->get("konsens_mw_day_from$inc"), 'From', 1);
          $this->konsens_mw_field_hours_minutes($form, $form_state,  "konsens_mw_hm_from".$inc, \Drupal::config('downtimes.settings')->get("konsens_mw_hm_from$inc"));
          $this->konsens_mw_field_day($form, $form_state,  "konsens_mw_day_until".$inc, $arr, \Drupal::config('downtimes.settings')->get("konsens_mw_day_until$inc"), 'Until');
          $this->konsens_mw_field_hours_minutes($form, $form_state,  "konsens_mw_hm_until".$inc, \Drupal::config('downtimes.settings')->get("konsens_mw_hm_until$inc"));
          $this->sitewide_mw_remove_button($form, $form_state,  "remove-$inc");
      }
      $form['sitewide_maintenance_windows']['howmany']['#value'] = $howmany;
    }else {
      foreach ($arr as $key => $val) {
	    if(  \Drupal::config('downtimes.settings')->get("konsens_maintenance_windows_$val") ) {
          $inc++;
          $this->konsens_mw_field_day($form, $form_state,  "konsens_mw_day_from".$inc, $arr, $val, 'From', 1);
          $date_time =  new DateTime("01-01-2015 ".\Drupal::config('downtimes.settings')->get('konsens_maintenance_windows_' . $val . '_hours', '').':'.\Drupal::config('downtimes.settings')->get('konsens_maintenance_windows_' . $val . '_minutes', '')); 
          $date_time = date_format($date_time, 'Y-m-d H:i:s');
          $this->konsens_mw_field_hours_minutes($form, $form_state,  "konsens_mw_hm_from".$inc, $date_time);
          $date_time =  new DateTime("01-01-2015 ". \Drupal::config('downtimes.settings')->get('konsens_maintenance_windows_' . $val . '_hours1', '').':'. \Drupal::config('downtimes.settings')->get("konsens_maintenance_windows_' . $val . '_minutes1'"));
          $date_time = date_format($date_time, 'Y-m-d H:i:s');
          $this->konsens_mw_field_day($form, $form_state,  "konsens_mw_day_until".$inc, $arr, $val, 'Until');
          $this->konsens_mw_field_hours_minutes($form, $form_state, "konsens_mw_hm_until".$inc, $date_time);
          $this->sitewide_mw_remove_button($form, $form_state,  "remove-$inc");
          $form['sitewide_maintenance_windows']['howmany']['#value'] = $inc;
        }
	
      }
    }
    if($inc == 0) {
      $inc++;

        $this->konsens_mw_field_day($form, $form_state, "konsens_mw_day_from".$inc, $arr, '', 'From', 1);
        $this->konsens_mw_field_hours_minutes($form, $form_state , "konsens_mw_hm_from".$inc, '');
        $this->konsens_mw_field_day($form, $form_state, "konsens_mw_day_until".$inc, $arr, '', 'Until');
        $this->konsens_mw_field_hours_minutes($form, $form_state, "konsens_mw_hm_until".$inc, '');

      $form['sitewide_maintenance_windows']['howmany']['#value'] = $inc;
    }
  }
  $form['addmore'] = array(
			   '#type' => 'button',
			   '#default_value' => t('Add more'),
			   '#prefix' => '<div class="addmore-div">',
			   '#suffix' => '</div>',
			   '#ahah' => array(
					    'path' => 'sitewide_mw_callback',
					    'wrapper' => 'sitewide_maintenance_windows',
					    'event' => 'click', // default value: does not need to be set explicitly.
					    ),
			   );
  /*$form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    );
    
    return $form;*/
  
  //$arr = array(t("Mon") => "Mon", t("Tue") => "Tue", t("Wed") => "Wed", t("Thu") => "Thu", t("Fri") => "Fri", t("Sat") => "Sat", t("Sun") => "Sun");
  /*foreach ($arr as $key => $val) {
    $form['konsens_maintenance_windows_' . $val] = array(
    '#type' => 'checkbox',
    '#prefix' => ($key == t('Mon')) ? '<div class="konsens-wide-maintenance-time"><b>' . t('KONSENS-wide Maintenance Windows:') . '</b>': '<div class="konsens-wide-maintenance-time">',
    '#default_value' => variable_get('konsens_maintenance_windows_' . $val, NULL),
    '#title' => $key,
    );
    append_konsens_hours_minutes($form, 'konsens_maintenance_windows_' . $val);
    }*/
//     $form['#validate'][] = 'scheduled_maintenance_validate';
    return parent::buildForm($form, $form_state);
  }

 /**
   * {@inheritDoc}
   */

  public function validateForm(array &$form, FormStateInterface $form_state) {

    if($form_state->getValues('op') != 'Add more') {
      if (!is_numeric($form_state->getValues('maintenance_advance_time'))) {
      //  form_set_error($form_state->getValues('maintenance_advance_time'), t("Enter Minimum time to schedule a maintenance in Numbers"));
         $form_state->setErrorByName('maintenance_advance_time', $this->t("Enter Minimum time to schedule a maintenance in Numbers"));
      }
    
      $arr = array(
        t("Mon") => "Mon", 
        t("Tue") => "Tue", 
        t("Wed") => "Wed", 
        t("Thu") => "Thu", 
        t("Fri") => "Fri", 
        t("Sat") => "Sat", 
        t("Sun") => "Sun");

    $howmany = $form_state->getValues('howmany');

    for($inc = 1; $inc <= $howmany; $inc++) {
      $from_hour = date('H', strtotime($form_state->getValue("konsens_mw_hm_from$inc")));
      $from_minute = date('i', strtotime($form_state->getValue("konsens_mw_hm_from$inc")));
      $until_hour = date('H', strtotime($form_state->getValue("konsens_mw_hm_until$inc")));
      $until_minute = date('i', strtotime($form_state->getValue("konsens_mw_hm_until$inc")));

      if($from_hour == '' || $until_hour == '') {
	$form_state->setErrorByName("konsens_mw_hm_from$inc", t("Select Hours"));
      }
      if($from_minute == '' || $until_minute == '') {
	$form_state->setErrorByName("konsens_mw_hm_until$inc", t("Select Minutes"));
      }

      if($form_state['values']["konsens_mw_day_from$inc"] == $form_state['values']["konsens_mw_day_until$inc"]) {
	if($from_hour != '' && $from_minute != '' && $until_hour != '' && $until_minute != '') {
	  $from_time  = ($from_hour * 60) + $from_minute;
	  $to_time = ($until_hour * 60) + $until_minute;
	    if($from_time >= $to_time) {
	      $form_state->setErrorByName("konsens_mw_hm_until$inc", t("End Date must be greater than Start Date."));
	    }
	  }
        }
      }

    }


    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */

  /*
   * submit handler for the problems settings page
   * selected services for the individual groups are stored in the table "group_problems_view"
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {

     parent::submitForm($form, $form_state);
  }

	// Add day dropdown to maintenance window
	function konsens_mw_field_day(array &$form, FormStateInterface $form_state, $name, $options, $data, $title, $add_prefix = 0) {
	  $form['sitewide_maintenance_windows'][$name] = array(
							       '#type' => 'select',
							       '#options' => $options,
							       '#default_value' => $data,
							       '#title' => t($title),
							       );
	  if($add_prefix) {
	    $form['sitewide_maintenance_windows'][$name]['#prefix'] = '<div class=mw-item-set>';
	  }
	  
	}

	// Add Hour Minute dropdown to maintenance window
	function konsens_mw_field_hours_minutes(array &$form, FormStateInterface $form_state, $name, $data) {
	  
	  $date_format = 'H:i';
	  $form['sitewide_maintenance_windows'][$name] = array(
							       //'#title' => t($title),
							       '#type' => 'date_select',
							       '#date_format' => $date_format,
							       '#date_label_position' => 'within',
							       '#default_value' => $data,
							       );
	  
	}

    function sitewide_mw_remove_button(array &$form, FormStateInterface $form_state) {
        $form['sitewide_maintenance_windows'][$name] = array(
            '#value' => "<a href='' class='mw-remove' id='$name'>Remove</a></div>",
        );
    }

}

