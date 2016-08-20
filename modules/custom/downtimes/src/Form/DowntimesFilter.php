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

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'downtimes_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $group = NULL) {
    if ($type == 'incidents') {
      $form_prefix == 'curr_incidents_form';
      $form['#prefix'] = "<div class =$form_prefix>";
      $type_header = "<h3 class = 'current_incidents_title'>" . t('Current Incidents') . "</h3><br>";
      $form['incidents_header_notes'] = [
        '#type' => 'markup',
        '#markup' => "<div class = 'downtime_notes'>" . \Drupal::config('downtimes.settings')->get('current_downtimes') . "</div>"
      ];
    }
    else if ($type == 'maintenance') {
      $form_prefix == 'curr_incidents_form_maintenance_filters';
      $form['#prefix'] = "<div class =$form_prefix>";
      $type_header = "<h3 class = 'current_maintainance_title'>" . t('Planned Maintenances') . "</h3><br>";
    }
    else if ($type == 'archived') {
      $form_prefix == 'archived_maintenance_search_results_wrapper';
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
      );
      $form['group'] = array(
        '#type' => 'hidden',
        '#valye' => $group,
      );
      $form['string'] = array(
        '#type' => 'textfield',
        '#weight' => 6,
        '#size' => 45,
        '#attributes' => array("class" => "search_string"),
        "#prefix" => "<div class = 'string_search'>",
        '#suffix' => '</div>',
      );
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
        'callback' => '::current_arhive_incident_results',
        'wrapper' => $form_prefix,
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
    if (isset($group)) {
      $where = " WHERE n.nid = GDV.service_id and group_id = " . $group->id() . " order by title ";
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
    ];
    $form['filter_startdate'] = [
      '#type' => 'textfield',
      '#title' => t('Start Date'),
      /* '#date_date_format' => $date_format,
        '#date_time_format' => $time_format, */
      '#description' => date($date_format, time()),
      '#default_value' => date($date_format, time()),
      '#required' => TRUE,
    ];
    $form['filter_enddate'] = [
      '#type' => 'textfield',
      '#title' => t('End Date'),
      '#description' => date($date_format, time()),
      '#default_value' => date($date_format, time()),
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
    $form['#attached']['drupalSettings'] = array('search_string' => t('Search Reason'), 'group_id' => $group);
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
    $result = array();
    $form_state->setValue('submitted', FALSE);
    $state_id = $form_state->getValue('states');
    $form_state->setRebuild(TRUE);
    
    $incidents_data = \Drupal::formBuilder()->getForm('\Drupal\downtimes\Form\DowntimesFilter', 'incidents', $group);
    $current_time = time();
    $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
    $string = 'incidents';
    $incident_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string, '', $state_id);

    $result = array();
    $result['incidents_form'] = $incidents_data;
    $result['incidents_reset_form'] = HzdcustomisationStorage::reset_form();
    $result['incidents_table'] = $incident_downtimes;
    return $result;
  }

  function ahah_problems_display($form, $form_state, $sql_where = NULL, $string = NULL, $limit = NULL) {
    $user_input = $form_state->getUserInput();
    //  if (isset($user_input['_triggering_element_name']) && $user_input['_triggering_element_name'] != 'service') {
    $values['values']['service'] = $form_state->getValue('service');
    $values['values']['function'] = $form_state->getValue('function');
    $values['values']['release'] = $form_state->getValue('release');
    // }
    $form_build_id = $this->getFormId();
    // FormCache::getCache($form_build_id, $form_state); 
    $service = $form_state->getValue('service');
    //Geting functions and release data

    $default_function_releases = HzdStorage::get_functions_release($string, $service);
    $form['function']['#options'] = isset($default_function_releases['functions']) ? $default_function_releases['functions'] : $this->t("Select Service");
//    $form['function']['#options'] = $default_function_releases['functions'];
    $form['function']['#value'] = $values['values']['function'];

    if (isset($user_input['_triggering_element_name']) && $user_input['_triggering_element_name'] != 'service') {
      $form['release']['#options'] = $default_function_releases['releases'];

      $form['release']['#value'] = $values['values']['release'];
    }
    else {
      $default_release[] = t("Select Release");
      $form['release']['#options'] = $default_release;
    }

    // FormCache::setCache($form_build_id, $form, $form_state);
    $_SESSION['sql_where'] = $sql_where;
    $_SESSION['limit'] = $limit;
    $form_state->setRebuild(TRUE);
    $result['content']['#prefix'] = "<div id = 'problem_search_results_wrapper'>";
    $result['content']['problems_filter_element'] = $form;
    $result['content']['problems_reset_element']['#prefix'] = "<div class = 'reset_form'>";
    $result['content']['problems_reset_element']['form'] = HzdproblemmanagementHelper::problem_reset_element();
    $result['content']['problems_reset_element']['#suffix'] = '</div><div style = "clear:both"></div>';
    $result['content']['problems_default_display'] = HzdStorage::problems_default_display($sql_where, $string, $limit);
    $result['content']['#suffix'] = "</div>";
    return $result;
  }

}
