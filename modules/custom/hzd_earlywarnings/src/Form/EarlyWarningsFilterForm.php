<?php

namespace Drupal\hzd_earlywarnings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\hzd_earlywarnings\HzdearlywarningsStorage;

if (!defined('KONSONS')) {
  define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
}
if (!defined('RELEASE_MANAGEMENT')) {
  define('RELEASE_MANAGEMENT', 32);
}

// TODO.
// $_SESSION['Group_id'] = 339;.
/**
 *
 */
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
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    }
    else {
      $group_id = $group;
    }

    $wrapper = 'earlywarnings_results_wrapper';
    $services[] = 'Service';

    $path = '::search_earlywarning';
    $type_path = '::search_type_earlywarning';

    $earlywarning_filter_option = $_SESSION['earlywarning_filter_option'];
    $request = \Drupal::request();
    $page = $request->get('page');

    $release_type = \Drupal::request()->get('release_type');
    if (isset($group_id) && $group_id != RELEASE_MANAGEMENT) {
      $default_type = db_query("SELECT release_type FROM {default_release_type} WHERE group_id = :gid", array(":gid" => $group_id))->fetchField();
      $default_type = ($release_type ? $release_type : ($default_type ? $default_type : KONSONS));
    }
    else {
      $default_type = $release_type ? $release_type : KONSONS;
    }

    $services_obj = db_query("SELECT n.title, n.nid 
                     FROM {node_field_data} n, {group_releases_view} grv, {node__release_type} nrt 
                     WHERE n.nid = grv.service_id and n.nid = nrt.entity_id and grv.group_id = :gid and nrt.release_type_target_id = :tid 
                     ORDER BY n.title asc", array(":gid" => $group_id, ":tid" => $default_type))->fetchAll();

    foreach ($services_obj as $services_data) {
      $services[$services_data->nid] = $services_data->title;
    }

    $form['type'] = array('#type' => 'hidden', '#default_value' => $type);
    $form['#prefix'] = "<div class = 'releases_filters'>";
    $form['#suffix'] = "</div>";

    $container = \Drupal::getContainer();
    $terms = $container->get('entity.manager')->getStorage('taxonomy_term')->loadTree('release_type');

    foreach ($terms as $key => $value) {
      $release_type_list[$value->tid] = $value->name;
    }
    $form['release_type'] = array(
      '#type' => 'select',
      '#default_value' => $earlywarning_filter_option['release_type'] ? $earlywarning_filter_option['release_type'] : $default_type,
      '#options' => $release_type_list,
      '#weight' => -9,
      '#ajax' => array(
        'callback' => $type_path,
        'wrapper' => $wrapper,
        'event' => 'change',
        'method' => 'replace',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      '#prefix' => '<div class = "release_type_dropdown  hzd-form-element">',
      '#suffix' => '</div><div style="clear:both"></div>',
    );

    $default_value_services = $form_state->getValue('service');
    $form['services'] = array(
      '#type' => 'select',
      '#options' => $services,
      '#default_value' => $earlywarning_filter_option['service'] ? $earlywarning_filter_option['service'] : $default_value_services,
      '#weight' => -7,
      '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,
        'event' => 'change',
        'method' => 'replace',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      '#prefix' => '<div class = "service_search_dropdown  hzd-form-element">',
      '#suffix' => '</div>',
    );

    $default_value_releases = $form_state->getValue('release');
    $options = HzdreleasemanagementHelper::get_dependent_release($default_value_services);
    $form['releases'] = array(
      '#type' => 'select',
      '#options' => $options['releases'],
      '#default_value' => $earlywarning_filter_option['release'] ? $earlywarning_filter_option['release'] : $default_value_releases,
      '#weight' => -3,
      '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,
        'event' => 'change',
        'method' => 'replace',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      '#prefix' => '<div class = "releases_search_dropdown  hzd-form-element">',
      '#suffix' => '</div>',
    );

    $form['filter_startdate'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Start Date'),
      '#attributes' => array('class' => array("start_date")),
      '#default_value' => $earlywarning_filter_option['startdate'] ? $earlywarning_filter_option['startdate'] : $form_state->getValue('filter_startdate'),
      '#size' => 15,
      '#weight' => 3,
      '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,
        'event' => 'change',
        'method' => 'replace',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      '#prefix' => '<div class = "filter_start_date  hzd-form-element">',
      '#suffix' => '</div>',
    );

    $form['filter_enddate'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('End Date'),
      '#size' => 15,
      '#weight' => 4,
      '#attributes' => array('class' => array("end_date")),
    // '#attributes' => array("class" => "end_date"),.
      '#default_value' => $earlywarning_filter_option['enddate'] ? $earlywarning_filter_option['enddate'] : $form_state->getValue('filter_enddate'),
      '#ajax' => array(
        'callback' => $path,
        'wrapper' => $wrapper,
        'event' => 'change',
        'method' => 'replace',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      '#prefix' => '<div class = "filter_end_date  hzd-form-element">',
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
      '#default_value' => $earlywarning_filter_option['limit'] ? $earlywarning_filter_option['limit'] : $form_state->getValue('limit'),
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
      '#prefix' => '<div class = "limit_search_dropdown  hzd-form-element">',
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
   * Ajax call back for search early warnings filters.
   */
  public function search_type_earlywarning(array $form, FormStateInterface $form_state) {
    $form_state->setValue('submitted', FALSE);
    $form_build_id = $_POST['form_build_id'];
    $request = \Drupal::request();

    $startdate = $form_state->getValue('filter_startdate');
    $enddate = $form_state->getValue('filter_enddate');
    $type = $form_state->getValue('type');
    $limit = $form_state->getValue('limit');
    $release_type = $form_state->getValue('release_type');
    $user_input = $form_state->getUserInput();
    if (isset($user_input['_triggering_element_name']) && $user_input['_triggering_element_name'] != 'release_type') {
      $service = $form_state->getValue('services');
      $release = $form_state->getValue('releases');
    }

    $filter_options = array(
      'service' => $service,
      'release' => $release,
      'limit' => $limit,
      'startdate' => $startdate,
      'enddate' => $enddate,
      'release_type' => $release_type,
    );
    $_SESSION['earlywarning_filter_option'] = $filter_options;
    $string = '';
    $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
    $form['services']['#options'] = $default_services['services'];
    $form['services']['#value'] = array();

    // Geting  release data.
    $default_releases = HzdreleasemanagementHelper::get_dependent_release($service);
    $form['releases']['#options'] = $default_releases['releases'];
    $form['releases']['#value'] = array();

    $output['content']['#prefix'] = '<div id ="earlywarnings_results_wrapper">';
    $output['content']['earlywarnings_filter_form'] = $form;
    $output['content']['reset_form']['#prefix'] = '<div class = "reset_form">';
    $output['content']['reset_form'] = HzdreleasemanagementHelper::releases_reset_element();
    $output['content']['reset_form']['#suffix'] = '</div><div style ="clear:both" ></div>';

    if ($type == 'releaseWarnings') {
      $output['content']['result_table']['#prefix'] = '<div class = "view_earlywarnings_output">';
      $output['content']['result_table'] = HzdearlywarningsStorage::release_earlywarnings_display_table($filter_options, $release_type);
      $output['content']['result_table']['#suffix'] = '</div></div>';
    }
    else {
      $output['content']['result_table']['#prefix'] = '<div class = "view_earlywarnings_output">';
      $output['content']['result_table'] = HzdearlywarningsStorage::view_earlywarnings_display_table($filter_options, $release_type);
      $output['content']['result_table']['#suffix'] = '</div></div>';
    }
    $output['content']['#attached']['drupalSettings']['data'] = $output;
    $output['content']['#attached']['drupalSettings']['status'] = TRUE;
    return $output;
  }

  /**
   * Ajax call back for search early warnings filters.
   */
  public function search_earlywarning(array $form, FormStateInterface $form_state) {
    $form_state->setValue('submitted', FALSE);
    $form_build_id = $_POST['form_build_id'];

    $type = $form_state->getValue('type');
    $limit = $form_state->getValue('limit');
    $startdate = $form_state->getValue('filter_startdate');
    $enddate = $form_state->getValue('filter_enddate');
    $release_type = $form_state->getValue('release_type');
    $user_input = $form_state->getUserInput();
    if (isset($user_input['_triggering_element_name']) && $user_input['_triggering_element_name'] != 'release_type') {
      $service = $form_state->getValue('services');
      $release = $form_state->getValue('releases');
    }
    $filter_options = array(
      'service' => $service,
      'release' => $release,
      'limit' => $limit,
      'startdate' => $startdate,
      'enddate' => $enddate,
      'release_type' => $release_type,
    );
    $_SESSION['earlywarning_filter_option'] = $filter_options;
    $string = '';
    $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
    $form['services']['#options'] = $default_services['services'];
    $form['services']['#value'] = $service;

    // Geting  release data.
    $default_releases = HzdreleasemanagementHelper::get_dependent_release($service);
    $form['releases']['#options'] = $default_releases['releases'];
    $form['releases']['#value'] = $release;

    if (!array_key_exists($form_state->getValue('releases'), $default_releases['releases'])) {
      $filter_options['release'] = 0;
      $form['releases']['#value'] = 0;
    }
    else {
      $form['releases']['#value'] = $form_state->getValue('releases');
    }

    $output['content']['#prefix'] = '<div id = "earlywarnings_results_wrapper">';
    $output['content']['earlywarnings_filter_form'] = $form;
    $output['content']['reset_form']['#prefix'] = '<div class = "reset_form">';
    $output['content']['reset_form'] = HzdreleasemanagementHelper::releases_reset_element();
    $output['content']['reset_form']['#suffix'] = '</div><div style = "clear:both" ></div>';
    if ($type == 'releaseWarnings') {
      $output['content']['result_table']['#prefix'] = '<div class = "view_earlywarnings_output">';
      $output['content']['result_table'] = HzdearlywarningsStorage::release_earlywarnings_display_table($filter_options, $release_type);
      $output['content']['result_table']['#suffix'] = '</div></div>';
    }
    else {
      $output['content']['result_table']['#prefix'] = '<div class = "view_earlywarnings_output">';
      $output['content']['result_table'] = HzdearlywarningsStorage::view_earlywarnings_display_table($filter_options, $release_type);
      $output['content']['result_table']['#suffix'] = '</div></div>';
    }
    $output['content']['#attached']['drupalSettings']['data'] = $output;
    $output['content']['#attached']['drupalSettings']['status'] = TRUE;
    return $output;
  }

}
