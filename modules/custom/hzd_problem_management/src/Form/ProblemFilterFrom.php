<?php

/**
 * @file
 * Contains \Drupal\problem_management\Form\ProblemFilterFrom
 */

namespace Drupal\problem_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\problem_management\HzdStorage;
// use Drupal\problem_management\Inactiveuserhelper;
// use Drupal\Core\Datetime\DateFormatter;
use Drupal\hzd_customizations\HzdcustomisationStorage;
use Drupal\problem_management\HzdproblemmanagementHelper;
use Drupal\Core\Form\FormCache;
$_SESSION['Group_id'] = 825;

/**
 * Configure inactive_user settings for this site.
 */
class ProblemFilterFrom extends FormBase {

 //  protected $dateFormatter;
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'problem_filter_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'problem_management.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $string = NULL) {
  if ( isset($_SESSION['problems_query'])) {
   $serialized_data = unserialize($_SESSION['problems_query']);
   $default_value_service = $serialized_data['service'];
   $default_value_function = $serialized_data['function'];
   $default_value_release = $serialized_data['release'];
   $default_value_string = $serialized_data['string'];

   $default_value_limit = $serialized_data['limit'];
  }

  $form['#attached'] = array('library' => array('problem_management/problem_management'));
  
  if ($string == 'archived') {
    $filter_where = " and cfps.field_problem_status_value = 'geschlossen' ";
  }
  else {
    $filter_where = " and cfps.field_problem_status_value <> 'geschlossen' ";
  }

  //DEFAULT SERVICES
  $default_services[] = t("Select Service");
  /**
  $query = db_query("SELECT title, n.nid 
                     FROM {node} n, {group_problems_view} gpv 
                     WHERE n.nid = gpv.service_id and gpv.group_id = %d ORDER BY title ", $_SESSION['Group_id']);
  */

  $query = db_select('node_field_data', 'nfd');
  $query->join('group_problems_view', 'gpv', 'nfd.nid = gpv.service_id');
  $query->Fields('nfd', array('nid', 'title'));
  $query->condition('gpv.group_id', $_SESSION['Group_id'], '=');

  $service = $query->execute()->fetchAll();

  foreach ($service as $services) {
    $default_services[$services->nid] = $services->title;
  }
  
 //DEFAULT FUNCTIONS
  $default_function[] = t("Select Function");

  //DEFAULT RELEASES
  $default_release[] = t("Select Release");
  
  // $form['#prefix'] = 
  $request = \Drupal::request();
  
  $form['service'] = array(
    '#type' => 'select',
    '#options' => $default_services,
  //  '#default_value' => ($default_value_service? $default_value_service: $request->request->get('service')),
    '#weight' => -1,
    '#ajax' => array(
      'callback' => '::problem_search_results',
      'wrapper' => 'problem_search_results_wrapper',
      'method' => 'replace',
      'event' => 'change',
      'progress' => array(
        'type' => 'throbber', 
        'message' => NULL,
        ),
      ),
    "#prefix" => "<div class = 'service_search_dropdown'>",
    '#suffix' => '</div>',
  );


  $form['function'] = array(
    '#type' => 'select',
    '#options' => $default_function,
  //  '#default_value' => ($default_value_function? $default_value_function: $request->request->get('function')),
    '#weight' => 0,
    '#ajax' => array(
      'callback' => '::problem_search_results',
      'wrapper' => 'problem_search_results_wrapper',
      'method' => 'replace',
      'event' => 'change',
      'progress' => array(
          'type' => 'throbber', 
          'message' => NULL,
        ),
      ),
    "#prefix" => "<div class = 'function_search_dropdown'>",
    '#suffix' => '</div>',
    );

 $form['release'] = array(
    '#type' => 'select',
    '#options' => $default_release,
    // '#default_value' => ($default_value_release?$default_value_release: $request->request->get('release')),
    '#weight' => 1,
    '#ajax' => array(
      'callback' => '::problem_search_results',
      'wrapper' => 'problem_search_results_wrapper',
      'method' => 'replace',
      'event' => 'change',
      'progress' => array(
        'type' => 'throbber',
        'message' => NULL,
        ),
     ),
    "#prefix" => "<div class = 'release_search_dropdown'>",
    '#suffix' => '</div>',
    );
 
 $service_id = ($serialized_data['service']?$serialized_data['service']:$request->request->get('service'));
 // $service_id = 1166;
  if ($service_id) {
    $service = $service_id;   
    $default_function_releases = HzdStorage::get_functions_release($string, $service);
    // echo '<pre>';  print_r($default_function_releases);  exit;
    $form['function']['#options'] = ($default_function_releases['functions']?$default_function_releases['functions'] : $default_services[] = t("Select Service"));
    $form['function']['#options'] = $default_function_releases['functions'];
    $form['release']['#options'] = $default_function_releases['releases'];
  }
  
  $form['string'] = array(
    '#type' => 'textfield',
    '#weight' => 6,
    '#size' => 42,
    '#default_value' => ($default_value_string?$default_value_string:t('Search Title, Description, cause, Workaround, solution')),
    '#attributes' => array("class" => "search_string"),
    "#prefix" => "<div class = 'string_search'>",
    '#suffix' => '</div>',
    );

  $form['submit'] = array(
    '#type' => 'button',
    '#weight' => 7,
    '#ajax' => array(
      'callback' => '::problem_search_results',
      'wrapper' => 'problem_search_results_wrapper',
      'method' => 'replace',
      'event' => 'click',
      'progress' => array('type' => 'throbber'),
      ),
    '#attributes' => array("class" => "filter_submit"),
    // "#prefix" => "<div class = 'filter_submit'>",
    // '#suffix' => '</div>',
  
    '#prefix' => '<div class = "search_string_submit">',
    //'#suffix' => '</div><div style="clear:both;"></div>',
    '#suffix' => '</div>',
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
    '#default_value' => ($default_value_limit?$default_value_limit: $request->request->get('limit')),
    '#weight' => 8,
    '#ajax' => array(
      'callback' => '::problem_search_results',
      'wrapper' => 'problem_search_results_wrapper',
      'method' => 'replace',
      'event' => 'change',
      'progress' => array(
        'type' => 'throbber',
        'message' => NULL,
       ),
    ),
    "#prefix" => "<div class = 'limit_search_dropdown'>",
    '#suffix' => '</div>',
  );

  $form['#action'] = '/' .$path;
  
  return $form;
}

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
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
  public function problem_search_results(array &$form, FormStateInterface $form_state) {
     $result = array();
     // $form_state = array('values' => $_POST);
     $request = \Drupal::request();
     // $import_path = $form_state->getValue('import_path');
     $values['values']['service'] = $request->request->get('service'); //  $_REQUEST['service'];
     $values['values']['function'] = $request->request->get('function'); // $_REQUEST['function'];
     $values['values']['release'] = $request->request->get('release'); // $_REQUEST['release'];
     $values['values']['string'] = $request->request->get('string'); // $_REQUEST['string'];
     $values['values']['limit'] = $request->request->get('limit'); // $_REQUEST['limit'];
     $sql_where = HzdStorage::build_ahah_query($values);
     $page_limit = $form_state->getValue('limit');
     if (isset($page_limit)) { 
       $limit = $page_limit;
     } else {
       $limit = $values['values']['limit'];
     }
    
     /**
       if ($form_state['values']['limit']) {
       $limit = $form_state['values']['limit'];
      }*/
     $current_path = \Drupal::service('path.current')->getPath();
     $get_uri = explode('/', $current_path);
     $string = $get_uri['4'];
     $result = self::ahah_problems_display($form, $form_state, $sql_where['query'], $string, $limit);
     if (array_key_exists("sid",$sql_where)) {
       // print drupal_to_js(array('nid' => $sql_where['sid'], 'status' => TRUE));
       // exit();
         $result['#attached']['drupalSettings']['nid'] = $sql_where['sid'];
         $result['#attached']['drupalSettings']['status'] = TRUE;
     }
     else {
       // print drupal_to_js(array('data' => $output, 'status' => TRUE));
         $result['#attached']['drupalSettings']['data'] = $output;
         $result['#attached']['drupalSettings']['status'] = TRUE;
       // exit();
     }
    return $result;
  }

  function ahah_problems_display($form, $form_state, $sql_where = NULL, $string = NULL, $limit = NULL) {
    $request = \Drupal::request();
     // $import_path = $form_state->getValue('import_path');
    $values['values']['service'] = $request->request->get('service'); //  $_REQUEST['service'];
    $values['values']['function'] = $request->request->get('function');
    $values['values']['release'] = $request->request->get('function');
    $form_state->setValue('submitted', 'FALSE');
    $form_build_id = $this->getFormId();
    // $form = form_get_cache($form_build_id, $form_state);
    // FormCache::getCache($form_build_id, $form_state); 
    // core/modules/views/src/Form/ViewsExposedForm.php
    // echo $form_build_id; exit;
    $request = \Drupal::request();
    if ($_POST) {
      $service = $request->request->get('service'); 
    }
    //Geting functions and release data
    $default_function_releases = HzdStorage::get_functions_release($string, $service);

    $form['function']['#options'] = ($default_function_releases['functions']?$default_function_releases['functions'] : $default_services[] = t("Select Service"));
    $form['function']['#options'] = $default_function_releases['functions'];
    $form['function']['#value'] = $values['values']['function'];  
    $form['release']['#options'] = $default_function_releases['releases'];
    // $form['release']['#value'] = $values['values']['release']; 
    // FormCache::setCache($form_build_id, $form, $form_state);

    $_SESSION['sql_where'] = $sql_where;
    $_SESSION['limit'] = $limit;

    // $output .= drupal_get_form('problems_filter_form', $string, $options);
    // $output .=  "<div class = 'reset_form'>" . drupal_render(problem_reset_element()). "</div>";
    // $output .= '<div style = "clear:both"></div>';
    // $output .= problems_default_display($sql_where, $string, $limit);
    
    $result['content']['#prefix'] = "<div id = 'problem_search_results_wrapper'>" ;
    $result['content']['problems_filter_element'] = $form;
    $result['content']['problems_reset_element']['#prefix'] = "<div class = 'reset_form'>";
    $result['content']['problems_reset_element']['form'] = HzdproblemmanagementHelper::problem_reset_element();
    $result['content']['problems_reset_element']['#suffix'] = '</div><div style = "clear:both"></div>';
    $result['content']['problems_default_display'] = HzdStorage::problems_default_display($sql_where, $string, $limit);
    $result['content']['#suffix'] = "</div>";
    return $result;
  }
}
