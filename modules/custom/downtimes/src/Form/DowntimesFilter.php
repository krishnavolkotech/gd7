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
      $form['#prefix'] = "<div class ='curr_incidents_form'>";
      $current_time = time();
      $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
      $string = 'incidents';
      $type_header = "<h3 class = 'current_incidents_title'>" . t('Current Incidents') . "</h3><br>";
      $form['incidents_header_notes'] = [
        '#type' => 'markup',
        '#markup' => "<div class = 'downtime_notes'>" . \Drupal::config('downtimes.settings')->get('current_downtimes') . "</div>"
      ];
    }
    else if ($type == 'maintenance') {
      $form['#prefix'] = "<div class ='curr_incidents_form maintenance_filters'>";
      $sql_where = "  and sd.scheduled_p = 1 and sd.resolved = 0 ";
      $string = 'maintenance';

      $type_header = "<h3 class = 'current_maintainance_title'>" . t('Planned Maintenances') . "</h3><br>";
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
      '#options' => HzdcustomisationStorage::get_published_services(),
    ];

    $services[1] = '<' . t('Service') . '>';
    $select = "SELECT title, n.nid ";
    $from = " FROM {node_field_data} n, {group_downtimes_view} GDV ";
    if (isset($group)) {
      $where = " WHERE n.nid = GDV.service_id and group_id = $group order by title ";
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
    ];

    $default_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string);
    $form[$type . '_table'] = [
      '#type' => 'markup',
      '#markup' => "<div id = '" . $type . "_search_results_wrapper'>" . $default_downtimes . "</div>"
    ];
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
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }
  }

}
