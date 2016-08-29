<?php

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_customizations\HzdcustomisationStorage;

/**
 * Class DowntimesFilter.
 *
 * @package Drupal\downtimes\Form
 */
class DowntimesFilter extends FormBase {
    
  const PAGE_LIMIT = 20;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'downtimes_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $group = \Drupal::routeMatch()->getParameter('group');
    if(is_object($group)){
      $group_id = $group->id();
    }else{
      $group_id = $group;
    }    
    
    $form['#attributes'] = array('class' => $type);
    if (isset($group_id)) {
      $path = "::downtimes_search_results";
    }
    else {
      $path = '::downtimes_search_results_public';
    }

    if ($type == 'incidents') {
      $wrapper = 'incidents_search_results_wrapper';
      $form_prefix = 'curr_incidents_form';     
      $form['#prefix'] = "<div class =$form_prefix>";
      $type_header = "<h3 class = 'current_incidents_title'>" . t('Current Incidents') . "</h3><br>";
      $form['incidents_header_notes'] = [
        '#type' => 'markup',
        '#markup' => "<div class = 'downtime_notes'>" . \Drupal::config('downtimes.settings')->get('current_downtimes') . "</div>"
      ];
    }
    else if ($type == 'maintenance') {
      $wrapper = 'maintenance_search_results_wrapper';
      $form_prefix = 'curr_incidents_form maintenance_filters';
      $form['#prefix'] = "<div class =$form_prefix>";
      $type_header = "<h3 class = 'current_maintainance_title'>" . t('Planned Maintenances') . "</h3><br>";
    }
    else if ($type == 'archived') {
      $wrapper = 'archived_maintenance_search_results_wrapper';
      $form_prefix = 'archived_maintenance_search_results_wrapper';
      $form['#prefix'] = "<div class =$form_prefix>";
      $types = array('select' => "<" . t("Type") . ">", t('Incidents'), t('Maintenance'));
      $type_header = "";

      $form['incidents_header_notes'] = [
        '#type' => 'markup',
        '#markup' => "<div class = 'downtime_notes'>" . \Drupal::config('downtimes.settings')->get('archived_downtimes') . "</div>"
      ];

      $form['type'] = array(
        '#type' => 'select',
        '#options' => $types,
        '#weight' => -1,
        "#prefix" => "<div class = 'type_search_dropdown'>",
        '#suffix' => '</div>',
        '#ajax' => array(
          'callback' => $path,
          'wrapper' => $wrapper,
          'method' => 'replace',
          'event' => 'change',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
        ),
      );

      $period = array(
        "<" . t('Time Period') . ">",
        t('Last Week'),
        t('Last Month'),
        t('Last Three Months'),
        t('Last Six Months'),
        t('Last 12 Months'),
        t('All'),
      );
      $form['time_period'] = array(
        '#type' => 'select',
        '#options' => $period,
        '#weight' => 5,
        '#attributes' => array("class" => "time_period_date"),
        "#prefix" => "<div class = 'time_period_search_dropdown'>",
        '#suffix' => '</div>',
                '#ajax' => array(
                    'callback' => $path,
                    'wrapper' => $wrapper,
                    'method' => 'replace',
                    'event' => 'change',
                    'progress' => array(
                        'type' => 'throbber',
                        'message' => NULL,
                    ),
                ),
            );
      $form['group'] = array(
        '#type' => 'hidden',
        '#valye' => $group_id,
      );
      $form['string'] = array(
        '#type' => 'textfield',
        '#weight' => 6,
        '#size' => 45,
        '#attributes' => array("class" => "search_string"),
        "#prefix" => "<div class = 'string_search'>",
        '#suffix' => '</div>',
      );
     
      $form['submit'] = array(
        '#type' => 'submit',
        '#weight' => 7,
        "#prefix" => "<div class = 'search_string_submit'>",
        '#suffix' => '</div>',
      );
            
      $form['#action'] = '/' . $path;
     
    }
        
    $date_format = 'd.m.Y - H:i';

    $form[$type . '_header'] = [
      '#type' => 'markup',
      '#markup' => $type_header
    ];
    
    $form['states'] = [
      '#type' => 'select',
      '#title' => t('States'),
      '#description' => t('Wählen Sie das Land aus, in dem die Wartungsarbeiten ausgeführt werden. Mehrfachauswahl ist möglich.'),
      '#options' => HzdcustomisationStorage::get_states(),
      '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,
        'method' => 'replace',
        'event' => 'change',
        'progress' => array(
          'type' => 'throbber',
          'message' => NULL,
        ),
      ),
    ];

    $services[1] = '<' . t('Service') . '>';
    $select = "SELECT title, n.nid ";
    $from = " FROM {node_field_data} n, {group_downtimes_view} GDV ";
    
    if (isset($group_id)) {
      $where = " WHERE n.nid = GDV.service_id and group_id = " . $group_id . " order by title ";
    }
    else {
      $where = " WHERE n.nid = GDV.service_id order by title ";
    }
    
    $sql = $select . $from . $where;

    $services_obj = db_query($sql)->fetchAll();
    foreach ($services_obj as $services_data) {
      $services[$services_data->nid] = $services_data->title;
    };
    
    
    $form['services_effected'] = [
      '#type' => 'select',
      '#title' => t('Services Effected'),
      '#options' => $services,
      '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,
        'method' => 'replace',
        'event' => 'change',
        'progress' => array(
          'type' => 'throbber',
          'message' => NULL,
        ),
      ),
    ];
    $form['filter_startdate'] = [
      '#type' => 'textfield',
      '#title' => t('Start Date'),
      /* '#date_date_format' => $date_format,
        '#date_time_format' => $time_format, */
      '#description' => date($date_format, time()),
      '#default_value' => date($date_format, time()),
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,
        'method' => 'replace',
        'event' => 'change',
        'progress' => array(
          'type' => 'throbber',
          'message' => NULL,
        ),
      ),
    ];
    $form['filter_enddate'] = [
      '#type' => 'textfield',
      '#title' => t('End Date'),
      '#description' => date($date_format, time()),
      '#default_value' => date($date_format, time()),
      '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,
        'method' => 'replace',
        'event' => 'change',
        'progress' => array(
          'type' => 'throbber',
          'message' => NULL,
        ),
      ),
    ];
      
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#weight' => 50
    ];

    //$default_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string);
    /* $form[$type . '_table'] = [
      '#type' => 'markup',
      '#markup' => "<div id = '" . $type . "_search_results_wrapper'>" . $default_downtimes . "</div>"
      ]; */
    $form['#suffix'] = "</div>";
    $form['#attached']['library'][] = 'downtimes.newdowntimes';
    $form['#attached']['library'][] = 'downtimes.currentincidents';
    $form['#attached']['drupalSettings'] = array('search_string' => t('Search Reason'), 'group_id' => $group_id);
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public function current_arhive_incident_results(array &$form, FormStateInterface $form_state) {

    $group = \Drupal::routeMatch()->getParameter('group');
    if(is_object($group)){
      $group_id = $group->id();
    }else{
      $group_id = $group;
    }
    $result = array();
    $form_state->setValue('submitted', FALSE);
    $state_id = $form_state->getValue('states');
    $form_state->setRebuild(TRUE);
    
    $incidents_data = \Drupal::formBuilder()->getForm('\Drupal\downtimes\Form\DowntimesFilter', 'incidents', $group_id);
    $current_time = time();
    $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
    $string = 'incidents';
    $incident_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string, '', $state_id);

    $result = array();
    $result['incidents_form_render']['incidents_form'] = $incidents_data;
    $result['incidents_form_render']['incidents_reset_form'] = HzdcustomisationStorage::reset_form();
    $result['incidents_table_render']['#prefix'] = "<div id = 'incidents_search_results_wrapper'>";
    $result['incidents_table'] = $incident_downtimes;
    $result['incidents_table_render']['#suffix']  = "</div>"; 
    return $result;
  }

  function downtimes_search_results(array &$form, FormStateInterface $form_state, $type) { 
    $limit = $form_state->getValue('limit') ? $form_state->getValue('limit') : $this->PAGE_LIMIT;
    $string = $form_state->getValue('string');
    $service = $form_state->getValue('services_effected');
    $state = $form_state->getValue('states');
    $search_string = $form_state->getValue('search_string');
    $filter_startdate = $form_state->getValue('filter_startdate');
    $filter_enddate = $form_state->getValue('filter_enddate');
    $current_time = time();
    $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
    $incident_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $type, $service, '', '',$state);   
    
    $form_state->setRebuild(TRUE);
    $result = array();

    $result['incidents_table_render']['#prefix'] = "<div id = 'incidents_search_results_wrapper'>";
    $result['incidents_table_render']['incidents_table'] = $incident_downtimes;
    $result['incidents_table_render']['#suffix']  = "</div>"; 
    
    $response = $result;
    return $response;
  }
  
  function downtimes_search_results_public(array &$form, FormStateInterface $form_state, $type) {   
    $limit = $form_state->getValue('limit') ? $form_state->getValue('limit') : $this->PAGE_LIMIT;
    $string = $form_state->getValue('string');
    $service = $form_state->getValue('services_effected');
    $state = $form_state->getValue('states');
    $search_string = $form_state->getValue('search_string');
    $filter_startdate = $form_state->getValue('filter_startdate');
    $filter_enddate = $form_state->getValue('filter_enddate');
    dpm($state);
    dpm($service);    
    $current_time = time();
    $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
    $incident_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $type, $service, '', '',$state);   
    
    $form_state->setRebuild(TRUE);
    $result = array();

    $result['incidents_table_render']['#prefix'] = "<div id = 'incidents_search_results_wrapper'>";
    $result['incidents_table_render']['incidents_table'] = $incident_downtimes;
    $result['incidents_table_render']['#suffix']  = "</div>"; 
    
    $response = $result;
    return $response;
  }
  
}
