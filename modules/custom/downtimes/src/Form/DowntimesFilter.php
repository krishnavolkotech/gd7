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
class DowntimesFilter extends FormBase
{
    
    const PAGE_LIMIT = 20;
    
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        if (isset($_SESSION['downtime_type'])) {
            return $_SESSION['downtime_type'];
        }
        return 'downtimes_filter';
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
        //   $form['#method']  = 'get';
        
        $filterData = \Drupal::request()->query;
        $group_id = get_group_id();
//        $filter_value = HzdcustomisationStorage::get_downtimes_filters();
        $form['#attributes'] = array('class' => [$type]);
        $form['#method'] = 'get';
        $form['#token'] = FALSE;
        if (isset($filter_value['downtime_type']) &&
            $filter_value['downtime_type'] == $type
        ) {
//            $selected_type = $filter_value['type'];
//            $time_period = $filter_value['time_period'];
//            $selected_state = $filter_value['states'];
//            $services_effected = $filter_value['services_effected'];
//            $filter_startdate = $filter_value['filter_startdate'];
//            $filter_enddate = $filter_value['filter_enddate'];
//            $search_string = $filter_value['string'];
        }
        $form['first_row'] = ['#type' => 'container', '#attributes' => ['class' => ['downtimes_form_row first_row']], '#weight' => 0];
        $form['second_row'] = ['#type' => 'container', '#attributes' => ['class' => ['downtimes_form_row second_row']], '#weight' => 2];
        if ($type == 'incidents') {
//            $wrapper = 'incidents_search_results_wrapper';
            $form_prefix = 'curr_incidents_form';
            $form['#prefix'] = "<div class =$form_prefix>";
//            $type_header = "<h3 class = 'current_incidents_title'>" . t('Current Incidents') . "</h3>";
            $form['incidents_header_notes'] = [
                '#type' => 'markup',
                '#markup' => "<div class = 'downtime_notes'>" . \Drupal::config('downtimes.settings')->get('current_downtimes') . "</div>"
            ];
        } else if ($type == 'maintenance') {
//            $wrapper = 'maintenance_search_results_wrapper';
            $form_prefix = 'curr_incidents_form maintenance_filters';
            $form['#prefix'] = "<div class =$form_prefix>";
            $type_header = "<h3 class = 'current_maintainance_title'>" . t('Planned Maintenances') . "</h3>";
        } else if ($type == 'archived') {
//            $selected_type = $filter_value['type'];
//            $time_period = $filter_value['time_period'];
//            $selected_state = $filter_value['states'];
//            $services_effected = $filter_value['services_effected'];
//            $filter_startdate = $filter_value['filter_startdate'];
//            $filter_enddate = $filter_value['filter_enddate'];
//            $search_string = $filter_value['string'];

//            $wrapper = 'archived_search_results_wrapper';
//            $form_prefix = 'archived_maintenance_search_results_wrapper';
//            $form['#prefix'] = "<div class =$form_prefix>";
            $types = array('select' => "<" . t("Type") . ">", t('Incidents'), t('Maintenance'));
            $type_header = "";
            
            $form['incidents_header_notes'] = [
                '#type' => 'markup',
                '#markup' => "<div class = 'downtime_notes'>" . \Drupal::config('downtimes.settings')->get('archived_downtimes') . "</div>",
//                '#prefix' => $form['#prefix']
            ];
            
            
            $form['first_row']['type'] = array(
                '#type' => 'select',
                '#options' => $types,
                '#title' => 'Type',
                '#weight' => -1,
//        '#ajax' => array(
//          'callback' => $path,
//          'wrapper' => $wrapper,
//          'method' => 'replace',
//          'event' => 'change',
//          'progress' => array(
//            'type' => 'throbber',
//            'message' => NULL,
//          ),
//        ),
                '#attributes' => array(
                    'onchange' => 'this.form.submit()',
                ),
                '#default_value' => $filterData->get('type', 'select'),
                '#prefix' => '<div class = "type_search_dropdown hzd-form-element">',
                '#suffix' => '</div><div style="clear:both"></div>',
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
            $form['second_row']['time_period'] = array(
                '#type' => 'select',
                '#options' => $period,
                '#title' => 'Period',
                '#weight' => 5,
                '#attributes' => array("class" => ["time_period_date"]),
                '#states' => [
                    'enabled' => [
                        ':input[name="filter_startdate"]' => array('filled' => FALSE),
                        ':input[name="filter_enddate"]' => array('filled' => FALSE),
                    ]
                ],
//        '#ajax' => array(
//          'callback' => $path,
//          'wrapper' => $wrapper,
//          'method' => 'replace',
//          'event' => 'change',
//          'progress' => array(
//            'type' => 'throbber',
//            'message' => NULL,
//          ),
//        ),
                '#attributes' => array(
                    'onchange' => 'this.form.submit()',
                ),
                '#prefix' => '<div class = "time_period_search_dropdown hzd-form-element">',
                '#suffix' => '</div>',
                '#default_value' => $filterData->get('time_period', 0),
            );
            $form['group'] = array(
                '#type' => 'hidden',
                '#value' => $group_id,
            );
            $form['search_string'] = ['#type' => 'container', '#attributes' => ['class' => ['search']], '#weight' => 1];
            $form['search_string']['string'] = array(
                '#type' => 'textfield',
                '#weight' => 6,
                '#size' => 45,
                '#attributes' => array("class" => ['search_string']),
                '#prefix' => '<div class = "string_search hzd-form-element">',
                '#suffix' => '</div>',
                '#default_value' => $filterData->get('string', ''),
                '#weight' => 1,
            );
            
            $form['search_string']['submit'] = array(
                '#type' => 'button',
                '#weight' => 2,
//        '#ajax' => array(
//          'callback' => $path,
//          'wrapper' => $wrapper,
//          'method' => 'replace',
//          'event' => 'click',
//          'progress' => array(
//            'type' => 'throbber',
//            'message' => NULL,
//          ),
//        ),
                "#prefix" => "<div class = 'search_string_submit'>",
                '#suffix' => '</div>',
            );
            
            //$form['#action'] = '/' . $path;
        }
        
        $date_format = 'd.m.Y - H:i';

//    $form[$type . '_header'] = [
//      '#type' => 'markup',
//      '#markup' => '<div class="filter_title_header">' . $type_header . '</div>',
//    ];
        $form['first_row']['states'] = [
            '#type' => 'select',
//      '#title' => t('States'),
            '#description' => t('Wählen Sie das Land aus, in dem die Wartungsarbeiten ausgeführt werden. Mehrfachauswahl ist möglich.'),
            '#options' => get_all_user_state(),
//      '#ajax' => array(
//        'callback' => $path,
//        'wrapper' => $wrapper,
//        'method' => 'replace',
//        'event' => 'change',
//        'disable-refocus' => true,
//        'progress' => array(
//          'type' => 'throbber',
//          'message' => NULL,
//        ),
//      ),
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => '<div class = "hzd-form-element">',
            '#suffix' => '</div>',
            '#default_value' => $filterData->get('states', 0),
        ];
        
        $services['0'] = '<' . t('Service') . '>';
        $select = "SELECT title, n.nid ";
        $from = " FROM {node_field_data} n, {group_downtimes_view} GDV ";
        
        if (isset($group_id)) {
            $where = " WHERE n.nid = GDV.service_id and group_id = " . $group_id . " order by title ";
        } else {
            $where = " WHERE n.nid = GDV.service_id order by title ";
        }
        
        $sql = $select . $from . $where;
        $services_obj = db_query($sql)->fetchAll();
        foreach ($services_obj as $services_data) {
            $services[$services_data->nid] = $services_data->title;
        };
        
        $form['first_row']['services_effected'] = [
            '#type' => 'select',
//      '#title' => t('Services Effected'),
            '#options' => $services,
//      '#ajax' => array(
//        'callback' => $path,
//        'wrapper' => $wrapper,
//        'method' => 'replace',
//        'event' => 'change',
//        'disable-refocus' => true,
//        'progress' => array(
//          'type' => 'throbber',
//          'message' => NULL,
//        ),
//      ),
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => '<div class = "hzd-form-element">',
            '#suffix' => '</div>',
            '#default_value' => $filterData->get('services_effected', 1),
        ];
        $form['second_row']['filter_startdate'] = [
            '#type' => 'textfield',
//      '#title' => t('Start Date'),
            '#placeholder' => '<' . t('Start Date') . '>',
            /* '#date_date_format' => $date_format,
              '#date_time_format' => $time_format, */
            '#description' => date($date_format, time()),
            '#states' => [
                'enabled' => [
                    ':input[name="time_period"]' => array('value' => 0),
                ]
            ],
//      '#ajax' => array(
//        'callback' => $path,
//        'wrapper' => $wrapper,
//        'method' => 'replace',
//        'disable-refocus' => true,
//        'event' => 'change',
//        'progress' => array(
//          'type' => 'throbber',
//          'message' => NULL,
//        ),
//      ),
            
            '#attributes' => array(
                'class' => array("start_date"),
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => '<div class = "hzd-form-element">',
            '#suffix' => '</div>',
            '#default_value' => $filterData->get('filter_startdate', ''),
        ];
        $form['second_row']['filter_enddate'] = [
            '#type' => 'textfield',
            '#placeholder' => '<' . t('End Date') . '>',
            '#description' => date($date_format, time()),
            '#states' => [
                'enabled' => [
                    ':input[name="time_period"]' => array('value' => 0),
                ]
            ],
//      '#ajax' => array(
//        'callback' => $path,
//        'wrapper' => $wrapper,
//        'method' => 'replace',
//        'disable-refocus' => true,
//        'event' => 'change',
//        'progress' => array(
//          'type' => 'throbber',
//          'message' => NULL,
//        ),
//      ), 
            '#attributes' => array(
                'class' => array("end_date"),
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => '<div class = "hzd-form-element">',
            '#suffix' => '</div>',
            '#default_value' => $filterData->get('filter_enddate', ''),
        ];
        /* $form['submit'] = [
          '#type' => 'submit',
          '#value' => t('Submit'),
          '#weight' => 50
          ]; */
        
        //$default_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string);
        /* $form[$type . '_table'] = [
          '#type' => 'markup',
          '#markup' => "<div id = '" . $type . "_search_results_wrapper'>" . $default_downtimes . "</div>"
          ]; */
        $form['actions']['#weight'] = 100;
        $form['actions']['reset'] = array(
            '#type' => 'button',
            '#value' => t('Reset'),
            '#weight' => 100,
//      '#validate' => array(),
            '#attributes' => array(
                'onclick' => 'reset_form_elements(); return false;'
            ),
        );
//        $form['#suffix'] = "</div>";
        $form['#attached']['library'] = array(
            //     'downtimes/downtimes.newdowntimes',
            'downtimes/downtimes.currentincidents',
            'downtimes/downtimes',
        );
        $form['#attached']['drupalSettings'] = array(
            'search_string' => t('Search Reason'),
            'group_id' => $group_id,
        );
        
        return $form;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $form_state->cleanValues();
        parent::validateForm($form, $form_state);
    }
    
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
    }
    
    public function current_arhive_incident_results(array &$form, FormStateInterface $form_state) {
        
        $group_id = get_group_id();
        $result = array();
        $form_state->setValue('submitted', FALSE);
        $state_id = $form_state->getValue('states');
        $form_state->setRebuild(TRUE);
        
        $incidents_data = \Drupal::formBuilder()->getForm('\Drupal\downtimes\Form\DowntimesFilter', 'incidents', $group_id);
        $current_time = time();
        $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
        $string = 'incidents';
        $incident_downtimes = HzdcustomisationStorage::current_incidents('maintenance', $string, '', $state_id);
        
        $result = array();
        $result['incidents_form_render']['incidents_form'] = $incidents_data;
        $result['incidents_form_render']['incidents_reset_form'] = HzdcustomisationStorage::reset_form();
        $result['incidents_table_render']['#prefix'] = "<div id = 'incidents_search_results_wrapper'>";
        $result['incidents_table'] = $incident_downtimes;
        $result['incidents_table_render']['#suffix'] = "</div>";
        return $result;
    }
    
    static public function current_incidents_search($options, $type = '') {
        $sql = '';
        if (isset($options['state_id'])) {
            $state_id = $options['state_id'];
        }
        if (isset($options['service_id'])) {
            $service_id = $options['service_id'];
        }
        if (isset($options['start_date'])) {
            $start_date = $options['start_date'];
        }
        if (isset($options['end_date'])) {
            $end_date = $options['end_date'];
        }
        
        if (isset($options['time_period'])) {
            $time_period = $options['time_period'];
        }
        if (isset($time_period)) {
            switch ($time_period) {
                case '1':
                    //last week
                    $from_date = mktime(0, 0, 0, date('m', time()), date('d', time()) - 7, date('Y'));
                    break;
                case '2':
                    //last month
                    $from_date = mktime(0, 0, 0, date('m', time()), date('d', time()) - 30, date('Y'));
                    break;
                case '3':
                    //last 3 months
                    $from_date = mktime(0, 0, 0, date('m', time()), date('d', time()) - 90, date('Y'));
                    break;
                case '4':
                    //last 6 months
                    $last_day = self::lastDayOfMonth(date('m', strtotime('last month')), date('y', strtotime('last month')));
                    $from_date = mktime(0, 0, 0, date('m', $last_day) - 5, 01, date('y', $last_day));
                    //$to_date = $last_day;
                    break;
                case '5':
                    //last 12 months
                    $last_day = self::lastDayOfMonth(date('m', strtotime('last month')), date('y', strtotime('last month')));
                    $from_date = mktime(0, 0, 0, date('m', $last_day) - 11, 01, date('y', $last_day));
                    //$to_date = $last_day;
                    break;
            }
        }
        if (isset($time_period)) {
            if (isset($from_date)) {
                if ($type == 'archived') {
                    $to_date = time();
                    $sql .= " and if (startdate_planned >= $from_date , startdate_planned >= $from_date, ri.end_date >= $from_date) ";
                } else {
                    $to_date = time();
                    $sql .= " and if (startdate_planned >= $from_date , startdate_planned >= $from_date, enddate_planned >= $from_date) ";
                }
            }
        } else {
            if (isset($start_date) && isset($end_date) && $type != 'archived') {
//        dpm($start_date);
//        dpm($end_date);
                $sql .= " and (( (sd.startdate_planned BETWEEN $start_date AND $end_date) "
                    . "OR (sd.enddate_planned BETWEEN $start_date AND $end_date) )"
                    . " OR ( sd.startdate_planned < $start_date AND sd.enddate_planned > $end_date ) )";
            } else {
                if (isset($start_date) && !isset($end_date)) {
                    $sql .= " and sd.startdate_planned >= $start_date";
                }
                if (isset($end_date) && !isset($start_date)) {
                    if ($type == 'archived') {
                        $sql .= " and (ri.end_date <= $end_date OR ri.end_date = 0 OR ri.end_date = '' )";
                    } else {
                        $sql .= " and (sd.enddate_planned <= $end_date OR sd.enddate_planned = 0 OR sd.enddate_planned = '' )";
                    }
                }
            }
        }
        
        if (isset($service_id) && $service_id > 1) {
            $service = $service_id;
        }
        if (isset($state_id) && $state_id > 1) {
            $sql .= " and ( sd.state_id LIKE '" . $state_id . ",%' or sd.state_id LIKE '%," . $state_id . ",%' or sd.state_id LIKE '%," . $state_id . "' )";
        }
        $incidents_parameters = array();
        if (isset($sql)) {
            $incidents_parameters['sql_where'] = $sql;
        }
        if (isset($service)) {
            $incidents_parameters['service'] = $service;
        }
        if (isset($state_id)) {
            $incidents_parameters['state'] = $state_id;
        }
        return $incidents_parameters;
    }
    
    static public function get_search_parameters($type, $options) {
        $filter_value = HzdcustomisationStorage::get_downtimes_filters();
        /*
         * to do filter and code optimazation
         */
        switch ($type) {
            case 'maintenance':
                $sql_where = "  and scheduled_p = 1 and resolved = 0";
                $incidents_parameters = self::current_incidents_search($options);
                $sql_where .= $incidents_parameters['sql_where'];
                $service = $incidents_parameters['service'];
                $state = $incidents_parameters['state'];
                $string = 'maintenance';
                $search_parameters = array(
                    'sql_where' => $sql_where,
                    'service' => $service,
                    'string' => $string,
                    'state' => $state
                );
                break;
            
            case 'incidents':
                $current_time = time();
                $sql_where = " and scheduled_p = 0
                   and resolved = 0 ";
                $string = 'incidents';
                $incidents_parameters = self::current_incidents_search($options);
                $sql_where .= $incidents_parameters['sql_where'];
                $state = $incidents_parameters['state'];
                $search_parameters = array(
                    'sql_where' => $sql_where,
                    'string' => $string,
                    'state' => $state
                );
                if (isset($service)) {
                    $service = $incidents_parameters['service'];
                    $search_parameters['service'] = $service;
                }
                break;
            
            case 'archived':
                $string = 'archived';
                $sql_where = " and resolved = 1";
                if ($filter_value['string']) {
                    if ($filter_value['string'] != t('Search Reason')) {
                        $search_string = $filter_value['string'];
                        $sql_where .= " and description like '%%$search_string%%' ";
                    }
                }
                if ($filter_value['time_period'] && $filter_value['time_period'] != 6) {
                    $options['time_period'] = $filter_value['time_period'];
                }
                if (isset($filter_value['downtimes_type']) &&
                    $filter_value['downtimes_type'] != 'select'
                    && $filter_value['downtimes_type'] != ''
                ) {
                    $type_filter = $filter_value['downtimes_type'];
                    $sql_where .= " and scheduled_p = $type_filter ";
                }
                
                $incidents_parameters = self::current_incidents_search($options, 'archived');
                $sql_where .= $incidents_parameters['sql_where'];
                $service = $incidents_parameters['service'];
                $state = $incidents_parameters['state'];
                $search_parameters = array(
                    'sql_where' => $sql_where,
                    'service' => $service,
                    'state' => $state,
                    'string' => $string,
                    'search_string' => $search_string
                );
                break;
        }
        return $search_parameters;
    }
    
    static public function get_form_options() {
        /*
         * to do filters for all and code optimization
         */
        $filter_value = HzdcustomisationStorage::get_downtimes_filters();
        if ($filter_value['states']) {
            $options['state_id'] = $filter_value['states'];
        }
        
        if ($filter_value['services_effected']) {
            $options['service_id'] = $filter_value['services_effected'];
        }
        
        if ($filter_value['filter_startdate']) {
            $start_date = $filter_value['filter_startdate'];
            $date = explode('.', $start_date);
            $day = $date[0];
            $month = $date[1];
            $year = $date[2];
            if ($start_date) {
                $filter_start_date = mktime(0, 0, 0, $month, $day, $year);
                $options['start_date'] = $filter_start_date;
            }
        }
        if ($filter_value['filter_enddate']) {
            $end_date = $filter_value['filter_enddate'];
            $date = explode('.', $end_date);
            $day = $date[0];
            $month = $date[1];
            $year = $date[2];
            if ($end_date) {
                $filter_end_date = mktime(23, 59, 59, $month, $day, $year);
                $options['end_date'] = $filter_end_date;
            }
        }
        
        return $options;
    }

//  function downtimes_search_results(array &$form, FormStateInterface $form_state, $form_id) {
//    $result = array();
//    $options = self::get_form_options($form_state);
//    $type = $form_state->getValue('downtime_type');
//    $search_parameters = self::get_search_parameters($type, $options, $form_state);
//    $sql_where = $search_parameters['sql_where'];
//
//    $limit = $form_state->getValue('limit') ? $form_state->getValue('limit') : $this->PAGE_LIMIT;
//    $string = $search_parameters['string'];
//    $service = $search_parameters['service'];
//    $state = $search_parameters['state'];
//    $search_string = $search_parameters['search_string'];
//    $_SESSION['incident_sql_where'] = $sql_where;
//    $_SESSION['incident_service'] = $service;
//    $_SESSION['incident_state'] = $state;
//    $_SESSION['incident_search_string'] = $search_string;
//    $_SESSION['incident_limit'] = $limit;
//
//    if ($string == 'archived') {
//      $downtimes_type = $form_state->getValue('type');
//      $time_period = $form_state->getValue('time_period');
//      $states = $form_state->getValue('states');
//      $services_effected = $form_state->getValue('services_effected');
//      $filter_startdate = $form_state->getValue('filter_startdate');
//      $filter_enddate = $form_state->getValue('filter_enddate');
//      $search_string = $form_state->getValue('string');
//      $result['incidents_table_render']['#attached']['drupalSettings'] = array(
//        'downtimes_type' => $downtimes_type,
//        'time_period' => $time_period,
//        'states' => $states,
//        'services_effected' => $services_effected,
//        'filter_startdate' => $filter_startdate,
//        'filter_enddate' => $filter_enddate,
//        'search_string' => $search_string,
//        'string' => $string,
//      );
//    }
//
//    $incident_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string, $service, $search_string, $limit, $state, $form_state->getValue('filter_enddate'));
//    $form_state->setRebuild(TRUE);
//    $result['incidents_table_render']['#prefix'] = "<div id = '" . $type . "_search_results_wrapper'>";
//    $result['incidents_table_render']['incidents_table'] = $incident_downtimes;
//    $result['incidents_table_render']['#suffix'] = "</div>";
//    // dpm($result);
//    $response = $result;
//    return $response;
//  }

//  function downtimes_search_results_public(array &$form, FormStateInterface $form_state, $type) {
//    $limit = $form_state->getValue('limit') ? $form_state->getValue('limit') : $this->PAGE_LIMIT;
//    $string = $form_state->getValue('string');
//    $service = $form_state->getValue('services_effected');
//    $state = $form_state->getValue('states');
//    $search_string = $form_state->getValue('search_string');
//    $filter_startdate = $form_state->getValue('filter_startdate');
//    $filter_enddate = $form_state->getValue('filter_enddate');
//    $current_time = time();
//    $sql_where = " and sd.scheduled_p = 0 and sd.resolved = 0 and sd.startdate_planned <= $current_time";
//    $incident_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $type, $service, '', '', $state);
//
//    $form_state->setRebuild(TRUE);
//    $result = array();
//
//    $result['incidents_table_render']['#prefix'] = "<div id = 'incidents_search_results_wrapper'>";
//    $result['incidents_table_render']['incidents_table'] = $incident_downtimes;
//    $result['incidents_table_render']['#suffix'] = "</div>";
//
//    $response = $result;
//    return $response;
//  }
    
    /*
     * Returns search query parametars for the incidents
     */
    
    static public function lastDayOfMonth($month = '', $year = '') {
        if (empty($month)) {
            $month = date('m');
        }
        if (empty($year)) {
            $year = date('Y');
        }
        $result = strtotime("{$year}-{$month}-01");
        $result = strtotime('-1 second', strtotime('+1 month', $result));
        return $result;
    }
    
}
