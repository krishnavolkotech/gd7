<?php

/**
 * @file
 * Contains \Drupal\hzd_release_management\Form\ReleaseFilterForm
 */

namespace Drupal\hzd_earlywarnings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_earlywarnings\Controller\HzdEarlyWarnings;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\hzd_earlywarnings\HzdearlywarningsStorage;

define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
define('RELEASE_MANAGEMENT', 339);

// TODO
$_SESSION['Group_id'] = 339;
class EarlyWarningsFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'earlywarnings_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $wrapper = 'earlywarnings_results_wrapper';
    $services[] = $this->t('Service');

    $path = '::search_earlywarning';
    $type_path = '::search_type_earlywarning';

    $release_type = \Drupal::request()->get('release_type');
    if(isset($group_id) && $group_id != RELEASE_MANAGEMENT) {
      $default_type = db_query("SELECT release_type FROM {default_release_type} WHERE group_id = :gid", array(":gid" => $group_id))->fetchField();
      $default_type = ($release_type ? $release_type : ($default_type ? $default_type : KONSONS));
    }
    else {
      $default_type = $release_type ? $release_type : KONSONS;
    }
    
    $services_obj = db_query("SELECT n.title, n.nid 
                     FROM {node_field_data} n, {group_releases_view} grv, {node__release_type} nrt 
                     WHERE n.nid = grv.service_id and n.nid = nrt.entity_id and grv.group_id = :gid and nrt.release_type_target_id = :tid 
                     ORDER BY n.title asc", array(":gid" => 339, ":tid" => $default_type))->fetchAll();

    foreach($services_obj as $services_data) {
      $services[$services_data->nid] = $services_data->title;
    }


    $container = \Drupal::getContainer();
    $terms = $container->get('entity.manager')->getStorage('taxonomy_term')->loadTree('release_type');
    $group_id = 339;

    foreach($terms as $key => $value) {
      $release_type_list[$value->tid] =$value->name;
    }

    if (isset($_SESSION['Group_id'])) {
      $rel_path = '::releases_type_search_results';
    }
    else {
      $rel_path = '::releases_search_results';
    }
    
  $form['release_type'] = array(
      '#type' => 'select',
      '#default_value' => $default_type,
      '#options' => $release_type_list,
      '#weight' => -9,
      '#ajax' => array(
          'callback' => $type_path,
          'wrapper' =>  $wrapper,
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
      ),
      "#prefix" => "<div class = 'release_type_dropdown  hzd-form-element'>",
      '#suffix' => '</div><div style="clear:both"></div>',    
    );

  
    $default_value_services = \Drupal::request()->get('services');
   $form['services'] = array(
      '#type' => 'select',
      '#options' => $services,
      '#default_value' => $default_value_services,
      '#weight' => -7,
      '#ajax' => array(
          'callback' => $path,
          'wrapper' =>  $wrapper,
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
     ),
      "#prefix" => "<div class = 'service_search_dropdown  hzd-form-element'>",
      '#suffix' => '</div>', 
    );

  
    $default_value_releases = \Drupal::request()->get('releases');
    $options = array('Release');
    $form['releases'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_value_releases,
      '#weight' => -3,
      '#ajax' => array(
         'callback' => $path,
         'wrapper' => $wrapper,
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
      ),
    "#prefix" => "<div class = 'releases_search_dropdown  hzd-form-element'>",
    '#suffix' => '</div>',
    );
    

    $form['filter_startdate'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Start Date'),
    // '#attributes' => array("class" => "start_date"), 
    '#attributes'=> array('class' => array("start_date")),
    '#default_value' => \Drupal::request()->get('filter_startdate'),
    '#size' => 15,
    '#weight' => 3,
      '#ajax' => array(
      'callback' => $path,
      'wrapper' =>  $wrapper,
      'event' => 'change',
      'method' => 'replace',
      'progress' => array(
        'type' => 'throbber',
        'message' => NULL,
        ),
    ),
    '#prefix' => "<div class = 'filter_start_date  hzd-form-element'>",
    '#suffix' => "</div>",    
  );
  
  $form['filter_enddate'] = array(
    '#type' => 'textfield',
    '#title' => t('End Date'),
    '#size' => 15,
    '#weight' => 4,
    '#attributes'=> array('class' => array("end_date")),
    // '#attributes' => array("class" => "end_date"),
    '#default_value' => \Drupal::request()->get('filter_enddate'),
    '#ajax' => array(
      'callback' => $path,
      'wrapper' =>  $wrapper,
      'event' => 'change',
      'method' => 'replace',
      'progress' => array(
        'type' => 'throbber',
        'message' => NULL,
       ),
    ),
    '#prefix' => "<div class = 'filter_end_date  hzd-form-element'>",
    '#suffix' => "</div>",
  );
  
  $default_limit = array(
    20 => 20,
    50 => 50,
    100 => 100,
    'all' => t('All'),
  );

  $form['limit'] = array(
    '#type' => 'select',
    '#options' => $default_limit,
    '#default_value' => \Drupal::request()->get('limit'),
    '#weight' => 8,
    '#ajax' => array(
      'callback' => $path,
      'wrapper' => $wrapper,
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
      ),
    "#prefix" => "<div class = 'limit_search_dropdown  hzd-form-element'>",
    '#suffix' => '</div>',    
  );

  return $form;
    
  }
  
   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

/*
*Ajax call back for search early warnings filters
*/
function search_type_earlywarning(array $form, FormStateInterface $form_state) {
  // $form_state = array('submitted' => FALSE);
  $form_state->setValue('submitted', FALSE);
  $form_build_id = $_POST['form_build_id'];
  // $form = form_get_cache($form_build_id, $form_state); 
  // $type =  $_REQUEST['type'];
  $request = \Drupal::request();
  $type =  $request->request->get('type');
  $service = $request->request->get('services');
  $release = $request->request->get('releases');
  $limit =  $request->request->get('limit');
  $startdate =  $request->request->get('filter_startdate');
  $enddate =  $request->request->get('filter_enddate');
  $release_type = $request->request->get('release_type');

  $filter_options = array(
    'service' => $service, 
    'release' => $release,
    'limit' => $limit, 
    'startdate' => $startdate,
    'enddate' => $enddate,
    'release_type' => $release_type
  );

  $string = '';
  $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
  $form['services']['#options'] = $default_services['services'];
  $form['services']['#value'] = array();
  //Geting  release data
  $default_releases = HzdreleasemanagementHelper::get_dependent_release($service);
  $form['releases']['#options'] = $default_releases['releases'];
  $form['releases']['#value'] = array();    
 // form_set_cache($form_build_id, $form, $form_state);

  $output[] = "<div id = 'earlywarnings_results_wrapper'>";
  $output[] = $form;  
  $output[] =  "<div class = 'reset_form'>";
  $output[] = HzdreleasemanagementHelper::releases_reset_element();
  $output[] = "</div>";
  $output[] =  "<div style = 'clear:both' ></div>";  
  if ($type == 'releaseWarnings') {
    $output[] = "<div class = 'view_earlywarnings_output'>";
    $output[] = HzdearlywarningsStorage::release_earlywarnings_display_table($filter_options, $release_type); 
    $output[] = "</div></div>";
  }
  else {
    $output[] = "<div class = 'view_earlywarnings_output'>";
    $output[] = HzdearlywarningsStorage::view_earlywarnings_display_table($filter_options, $release_type);
    $output[] = "</div></div>";
  }
//  print drupal_to_js(array('data' => $output, 'status' => TRUE));
//  exit();
  $output['#attached']['drupalSettings']['data'] = $output;
  $output['#attached']['drupalSettings']['status'] = TRUE;
  return $output;
}


function search_earlywarning(array $form, FormStateInterface $form_state) {
  $form_state->setValue('submitted', FALSE);
  $form_build_id = $_POST['form_build_id'];
  // $form = form_get_cache($form_build_id, $form_state);
  $request = \Drupal::request();
  $type =  $request->request->get('type');
  $service = $request->request->get('services');
  $release = $request->request->get('releases');
  $limit =  $request->request->get('limit');
  $startdate =  $request->request->get('filter_startdate');
  $enddate =  $request->request->get('filter_enddate');
  $release_type = $request->request->get('release_type');


  $filter_options = array(
    'service' => $service, 
    'release' => $release,
    'limit' => $limit, 
    'startdate' => $startdate,
    'enddate' => $enddate,
    'release_type' => $release_type
  );

  $string = '';
  $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
  $form['services']['#options'] = $default_services['services'];
  $form['services']['#value'] = $service;

  //Geting  release data
  $default_releases = HzdreleasemanagementHelper::get_dependent_release($service);
  $form['releases']['#options'] = $default_releases['releases'];
  $form['releases']['#value'] = $release;



  if (!array_key_exists($form_state->getValue('releases'), $default_releases['releases'])) {
    $filter_options['release'] =  0;
    $form['releases']['#value'] = 0;  
  }
  else {
    // $form['releases']['#value'] = $_POST['releases'];  
    $form['releases']['#value'] = $form_state->getValue('releases');
  }

  // form_set_cache($form_build_id, $form, $form_state);

  $output[]['#prefix'] = "<div id = 'earlywarnings_results_wrapper'>";
  $output[] = $form;
  $output[] = "<div class = 'reset_form'>";
  $output[] = HzdreleasemanagementHelper::releases_reset_element();
  $output[] = "</div>";
  $output[] = "<div style = 'clear:both' ></div>";  
  if ($type == 'releaseWarnings') {
    $output[] = "<div class = 'view_earlywarnings_output'>";
    $output[] = HzdearlywarningsStorage::release_earlywarnings_display_table($filter_options, $release_type);
    $output[] = "</div></div>";
  }
  else {
    $output[] = "<div class = 'view_earlywarnings_output'>";
    $output[] = HzdearlywarningsStorage::view_earlywarnings_display_table($filter_options, $release_type);
    $output[] = "</div></div>";
  }
  // print drupal_to_js(array('data' => $output, 'status' => TRUE));
  // exit();
  $output['#attached']['drupalSettings']['data'] = $output;
  $output['#attached']['drupalSettings']['status'] = TRUE;
  return $output;
}

}