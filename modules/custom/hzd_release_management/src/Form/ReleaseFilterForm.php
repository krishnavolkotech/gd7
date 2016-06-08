<?php

/**
 * @file
 * Contains \Drupal\hzd_release_management\Form\ReleaseFilterForm
 */

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;
use Drupal\hzd_release_management\Controller\HzdReleases;
use Drupal\Core\Form\FormCache;

define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
define('RELEASE_MANAGEMENT', 339);

class ReleaseFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'release_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $wrapper = 'released_results_wrapper';    
    $services[] = $this->t('Service');  

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
    $tempstore = \Drupal::service('user.private_tempstore')->get('hzd_release_management');
    $group_id = $tempstore->get('Group_id');

    foreach($terms as $key => $value) {
      $release_type_list[$value->tid] =$value->name;
    }
    
    $form['#prefix'] = "<div class = 'releases_filters'>";
    $form['#suffix'] = "</div>";
    
    $path = '::releases_search_results';

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
      '#weight' => -1,
      '#ajax' => array(
          'callback' => $rel_path,
          'wrapper' =>  $wrapper,
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
          ),
      ),
      "#prefix" => "<div class = 'release_type_dropdown'>",
      '#suffix' => '</div><div style="clear:both"></div>',
      '#validated' => TRUE,
    );

  if ($type == 'deployed') {
      $states = get_all_user_state();
      $form['states'] = array(
        '#type' => 'select',
        '#options' => $states,
        '#default_value' => \Drupal::request()->get('states'),
        '#weight' => -11,
        '#ajax' => array(
          'callback' => $path,
          'wrapper' => $wrapper,
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
          ),
        ),
      "#prefix" => "<div class = 'state_search_dropdown'>",
      '#suffix' => '</div>',
      '#validated' => TRUE
      );

      $types = array('current' => t('Current'), 'archived' => t('Archived'), 'all' => t('All'));
      $form['deployed_type'] = array(
        '#type' => 'select',
        '#options' => $types,
        '#default_value' => \Drupal::request()->get('deployed_type') ? \Drupal::request()->get('deployed_type') : array('current'),
        '#weight' => 1,
        '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,          
        'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
          ),
        ),
      "#prefix" => "<div class = 'type_dropdown'>",
      '#suffix' => '</div>',
      '#validated' => TRUE,
      );

      $environment_data = HzdreleasemanagementStorage::get_environment_options(\Drupal::request()->get('states'));
      $form['environment_type'] = array(
        '#type' => 'select',
        '#default_value' => \Drupal::request()->get('environment_type') ? \Drupal::request()->get('environment_type') : array(1),
        '#options' => $environment_data,
        '#weight' => -10,
        '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
          ),
        ),
        '#validated' => TRUE,
      );
    }

    $timer = \Drupal::config('hzd_release_management.settings')->get('timer');
    $default_value_services = $timer ? $timer : \Drupal::request()->get('services');
    $form['services'] = array(
      '#type' => 'select',
      '#options' => $services,
      '#default_value' => $default_value_services,
      '#weight' => -7,
      '#ajax' => array(
          'callback' => $rel_path,
          'wrapper' =>  $wrapper,
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
          ),
     ),
      "#prefix" => "<div class = 'service_search_dropdown'>",
      '#suffix' => '</div>',
      '#validated' => TRUE,
    );

    $service = \Drupal::request()->get('services');
    $options = array('Release');
    /*if ($service) {
      $release = \Drupal::request()->get('releases');
      $def_releases = get_release($type, $service);
      $options = $def_releases['releases'];
    }
    else {
      $options = array('Release');
    }*/

    $form['r_type'] = array('#type' => 'hidden', '#value' => $type);
    
    $timer = \Drupal::config('hzd_release_management.settings')->get('timer');
    $default_value_releases = $timer?$timer:$release_type = \Drupal::request()->get('releases');

    $form['releases'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_value_releases,
      '#weight' => -3,
      '#ajax' => array(
         'callback' => $rel_path,
         'wrapper' => $wrapper,
          'event' => 'change',
          'method' => 'replace',
          'progress' => array(
            'type' => 'throbber',
          ),
      ),
    "#prefix" => "<div class = 'releases_search_dropdown'>",
    '#suffix' => '</div>',
    '#validated' => TRUE
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
        ),
    ),
    '#prefix' => "<div class = 'filter_start_date'>",
    '#suffix' => "</div>",
    '#validated' => TRUE,  
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
       ),
    ),
    '#prefix' => "<div class = 'filter_end_date'>",
    '#suffix' => "</div>",
    '#validated' => TRUE,
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
          ),
      ),
    "#prefix" => "<div class = 'limit_search_dropdown'>",
    '#suffix' => '</div>',    
  );

    return $form;
}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Implements callback for Ajax event on release type selection.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   service section of the form.
   */
  public function releases_search_results(array &$form, FormStateInterface $form_state) {
    // echo '<pre>';  echo ';skjdhfkjshdf';  exit;
    $form_state->setValue('submitted', FALSE);
    $form_build_id = $_POST['form_build_id'];
    // $form = form_get_cache($form_build_id, $form_state);
    // FormCache::getCache($form_build_id, $form_state); 
    $string = $form_state->getValue('r_type');
    //$string = $_REQUEST['type'];  

      $state = \Drupal::request()->get('states');
      $service = \Drupal::request()->get('services');
      $release = \Drupal::request()->get('releases');
      $start_date = strtotime(\Drupal::request()->get('filter_startdate'));
      $end_date = strtotime(\Drupal::request()->get('filter_enddate'));
      $limit = \Drupal::request()->get('limit');
      $deployed_type = \Drupal::request()->get('deployed_type');
      $release_type = \Drupal::request()->get('release_type');
      $env_type = \Drupal::request()->get('environment_type');
    if ($string != 'deployed') {
      //Geting  release data
      $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
      $form['services']['#options'] = $default_services['services'];
      if (array_key_exists($service, $default_services['services'])) {
        $form['services']['#value'] = $service;
      }
     
      $default_releases = HzdreleasemanagementHelper::get_release($string, $service);
      $form['releases']['#options'] = $default_releases['releases'];
      if (array_key_exists($release, $default_releases['releases'])) {
        $form['releases']['#value'] = $release;
      }
      else {
        $release = 0;
        $form['releases']['#value'] = $release;        
      }

      if ($service) {
        // $filter_where .= " and field_relese_services_nid = ". $service;
        
       $filter_where[] =  array(
           'field' => 'nfrs.field_relese_services_target_id',
           'value' => $service,
           'operator' => '=',
       );
      }
      if ($release) {
        // $filter_where .= " and n.title like '%" . $release . "%'";
       $filter_where[] =  array(
           'field' => 'n.nid',
           'value' => $release,
           'operator' => '=',
       );
      }
      if ($start_date) {
        // $filter_where .= " and field_date_value > ". $start_date;
       $filter_where[] =  array(
           'field' => 'field_date_value',
           'value' => $start_date,
           'operator' => '>',
       );
        $form['filter_startdate']['#value'] = date('d.m.Y', $start_date);
      }
      if ($end_date) {
        $form['filter_enddate']['#value'] = date('d.m.Y',$end_date);
       // $filter_where .= " and field_date_value between ". ($start_date?$start_date:0) . " and " . $end_date;
       $filter_where[] =  array(
           'field' => 'field_date_value',
           'value' => array($start_date, $end_date),
           'operator' => 'BETWEEN',
       );
      }
    }
    else {
        $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
        $env_options = HzdreleasemanagementStorage::get_environment_options($state);
        $form['services']['#options'] = $default_services['services'];
        if (array_key_exists($service, $default_services['services'])) {
          $form['services']['#value'] = $service;
        }
        $form['states']['#value'] = $state;
        $form['deployed_type']['#value'] = $deployed_type;
        $form['environment_type']['#value'] = (!array_key_exists($env_type ,$env_options)) ? 1 : $env_type;
        $form['environment_type']['#options'] = $env_options;
        if (!array_key_exists($env_type ,$env_options)) {
          $env_type = 1;
        }
      if ($service > 0) {
        $default_releases = HzdreleasemanagementHelper::get_dependent_release($service);
        $form['releases']['#options'] = $default_releases['releases'];

        if (array_key_exists($release, $default_releases['releases'])) {
          $form['releases']['#value'] = $release;
        }
        else {
          $release = 0;
          $form['releases']['#value'] = $release;        
        }
      }
    }
   $form_state->setValue('rebuild', TRUE);
   // form_set_cache($form_build_id, $form, $form_state);
    // FormCache::setCache($form_build_id, $form, $form_state);
   // $output .= drupal_get_form('release_filter_form', $string);
    $output[]['#prefix'] = "<div id = 'released_results_wrapper'>" ;
    $output[] = $form;
    //$output .= '<div style="clear:both;"></div>';
    $output[] = array('#markup' => "<div class = 'reset_form'>"); 
    $output[] = HzdreleasemanagementHelper::releases_reset_element();
    $output[] = array('#markup' =>'</div><div style = "clear:both"></div>');

    $_SESSION['filter_where'] = $filter_where;
    $_SESSION['release_limit'] = $limit;
    $_SESSION['release_type'] = $release_type;

    if ($string != 'deployed') {
      $output[] = array('#markup' => "<div class = 'releses_output'>");
      $output[] = HzdreleasemanagementStorage::releases_display_table($string, $filter_where, $limit, $release_type);
      echo '<pre>';  echo 'releases_kjsdhftyp';  exit;
      $output[] = array('#markup' => "</div>");
    }
    else {
      $filter_options = array('service' => $service, 'release' => $release, 'state' => $state, 'startdate' => $start_date, 'enddate' => $end_date, 'deployed_type' => $deployed_type, 'release_type' => $release_type, 'env_type' => $env_type);
      $_SESSION['filter_options'] = $filter_options;
      $output[] = array('#markup' => "<div class = 'deployed_releses_output'>");
      $output[] = HzdreleasemanagementStorage::deployed_releases_displaytable($filter_options, $limit, $release_type);
      $output[] = array('#markup' => "</div>");
    }
/**
    $output[] = array( => "<script>
                if ($.browser.msie){
                  if($.browser.version == '10.0') {
                  setTimeout(function(){
                  $(window).scrollTop(0);
                  },500);
                  }
                }                     
              </script>");
*/  $output[] = array('#markup' => "</div>");
    $output[] = array(
      '#attached' => array(
        'library' => array(
          'drupalSettings'=> array(
            'data' => $output
            )
          )
        )
      );
    $output[] = array(
      '#attached' => array(
        'library' => array(
          'drupalSettings' => array(
            'status' =>  TRUE
            )
          )
        )
      );

//    print drupal_to_js(array('data' => $output, 'status' => TRUE));
    return $output;
  }

  public function releases_type_search_results(array &$form, FormStateInterface $form_state) {
    $filter_where = array();
    /*$_REQUEST['services'] = 0;
    $_REQUEST['releases'] = 0;*/
    $form_state->setValue('submitted', FALSE);
    $form_build_id = $_POST['form_build_id'];
    $string = $form_state->getValue('r_type');
    $service = \Drupal::request()->get('services');
    $release = \Drupal::request()->get('releases');
    $start_date = strtotime(\Drupal::request()->get('filter_startdate'));
    $end_date = strtotime(\Drupal::request()->get('filter_enddate'));
    $limit = \Drupal::request()->get('limit');
    $release_type = \Drupal::request()->get('release_type');
    
    if ($string != 'deployed') {
      if($service) {
        // $filter_where .= " and field_relese_services_nid = ". $service;
        $filter_where[] =  array(
          'field' => 'nfrs.field_relese_services_target_id',
          'value' => $service,
          'operator' => '=',
        );
      }
      if($release) {
        // $filter_where .= " and n.title like '%" . $release . "%'";
        $filter_where[] = array(
          'field' => 'n.nid',
          'value' => $release,
          'operator' => '=',
        );
      }
      if($start_date) {
        // $filter_where .= " and field_date_value > ". $start_date;
        $filter_where[] = array(
          'and' => array(
            'field' => 'field_date_value',
            'value' => $start_date,
            'operator' => '>',
          )
        );
      }
      if($end_date) {
        // $filter_where .= " and field_date_value between ". ($start_date?$start_date:0) . " and " . $end_date;
        $filter_where[] = array(
          'field' => 'field_date_value between',
          'value' => array($start_date, $end_date),
          'operator' => 'BETWEEN',
        );
      }
      $default_services = array();
      //Geting  release data
      $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
      $form['services']['#options'] = $default_services['services'];
      if (array_key_exists($service, $default_services['services'])) {
        $form['services']['#value'] = $service;
      }

      $default_releases = HzdreleasemanagementHelper::get_release($string, $service);
      $form['releases']['#options'] = $default_releases['releases'];
        if (array_key_exists($release, $default_releases['releases'])) {
          $form['releases']['#value'] = $release;
        }
    }
    else {
        $state = \Drupal::request()->get('states');
        $deployed_type = \Drupal::request()->get('deployed_type');
        $env_type = \Drupal::request()->get('environment_type');
        $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
        $env_options = HzdreleasemanagementStorage::get_environment_options($state);

        $form['services']['#options'] = $default_services['services'];
        if (array_key_exists($service, $default_services['services'])) {
          $form['services']['#value'] = $service;
        }

        $default_releases = HzdreleasemanagementHelper::get_release($string, '');
        $form['releases']['#options'] = $default_releases['releases'];
        if (array_key_exists($release, $default_releases['releases'])) {
          $form['releases']['#value'] = $release;
        }

        $form['states']['#value'] = $state;
        $form['deployed_type']['#value'] = $deployed_type;
        $form['environment_type']['#value'] = $env_type;
        $form['environment_type']['#options'] = $env_options;

      if ($service > 0) {
        $default_releases = HzdreleasemanagementHelper::get_dependent_release($service);
        $form['releases']['#options'] = $default_releases['releases'];
        if (array_key_exists($release, $default_releases['releases'])) {
          $form['releases']['#value'] = $release;
        }
      }
    }
    $form['filter_startdate']['#value'] = '';
    $form['filter_enddate']['#value'] = '';
   // form_set_cache($form_build_id, $form, $form_state);
   // FormCache::setCache($form_build_id, $form, $form_state);
    $form_state->setValue('rebuild', TRUE);
    $output[]['#prefix'] = "<div id = 'released_results_wrapper'>" ;
    $output[] = $form;
    //$output .= '<div style="clear:both;"></div>';
    $output[] = array('#markup' => "<div class = 'reset_form'>");
    $output[] = HzdreleasemanagementHelper::releases_reset_element();
    $output[] = array('#markup' => '</div><div style = "clear:both"></div>');
    $_SESSION['filter_where'] = $filter_where;
    $_SESSION['release_limit'] = $limit;
    $_SESSION['release_type'] = $release_type;

    if ($string != 'deployed') {
      $output[] = array('#markup' => "<div class = 'releses_output'>");
      $output[] = HzdreleasemanagementStorage::releases_display_table($string, $filter_where, $limit, $release_type);
      echo '<pre>';  echo 'releases_type_search_results';  exit;
      $output[] = array('#markup' => "</div>");
    }
    else {
      $filter_options = array(
        'service' => $service, 
        'release' => $release, 
        'state' => $state, 
        'startdate' => $start_date, 
        'enddate' => $end_date, 
        'deployed_type' => $deployed_type, 
        'release_type' => $release_type, 
        'env_type' => $env_type);
      $_SESSION['filter_options'] = $filter_options;
      $output[] = array('#markup' => "<div class = 'deployed_releses_output'>");
      $output[] = HzdreleasemanagementStorage::deployed_releases_displaytable($filter_options, $limit, $release_type);
      $output[] = array('#markup' => "</div>");
    }
    $output[] = array('#markup' => "</div>");
    $output[]['#attached']['library']['drupalSettings']['data'] = $output;
    $output[]['#attached']['library']['drupalSettings']['status'] =  TRUE;
    return $output;
  }
}
