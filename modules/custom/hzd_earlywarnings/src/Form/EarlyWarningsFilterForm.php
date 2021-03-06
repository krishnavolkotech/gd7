<?php

namespace Drupal\hzd_earlywarnings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\hzd_earlywarnings\HzdearlywarningsStorage;
use Drupal\hzd_earlywarnings\Controller\HzdEarlyWarnings;

if (!defined('KONSONS')) {
    define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
}


// TODO.
// $_SESSION['Group_id'] = 339;.
/**
 *
 */
class EarlyWarningsFilterForm extends FormBase
{
    
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
        
        $filter_value = HzdearlywarningsStorage::get_earlywarning_filters();
        $group_id = get_group_id();
        $form['#method'] = 'get';
        
        $wrapper = 'earlywarnings_results_wrapper';
        $services[] = '<' . $this->t('Service') . '>';
//
//    $path = '::search_earlywarning';
//    $type_path = '::search_type_earlywarning';
//
//    $earlywarning_filter_option = $_SESSION['earlywarning_filter_option'];
//    $request = \Drupal::request();
//    $page = $request->get('page');
        
        $release_type = $filter_value['release_type'];
        if (isset($group_id) && $group_id != RELEASE_MANAGEMENT) {
            $default_type = \Drupal::database()->query("SELECT release_type FROM "
                . "{default_release_type} WHERE group_id = :gid",
                array(":gid" => $group_id))->fetchField();
            $default_type = isset($release_type) ? $release_type :
                (isset($default_type) ? $default_type : KONSONS);
        } else {
            $default_type = $release_type ? $release_type : KONSONS;
        }

      $services_obj = \Drupal::database()->query("SELECT n.title, n.nid 
                     FROM {node_field_data} n, {group_releases_view} grv, 
                     {node__release_type} nrt 
                     WHERE n.nid = grv.service_id and n.nid = nrt.entity_id 
                     and grv.group_id = :gid and nrt.release_type_target_id = :tid 
                     ORDER BY n.title asc", array(
                ":gid" => $group_id,
                ":tid" => $default_type
            )
        )->fetchAll();

        foreach ($services_obj as $services_data) {
          $services[$services_data->nid] = $services_data->title;
        }


        
        $form['type'] = array(
            '#type' => 'hidden',
            '#default_value' => $type
        );
        $form['#prefix'] = "<div class = 'releases_filters'>";
        $form['#suffix'] = "</div>";
        
        $container = \Drupal::getContainer();
        $terms = $container->get('entity_type.manager')
            ->getStorage('taxonomy_term')->loadTree('release_type');
        
        foreach ($terms as $key => $value) {
            $release_type_list[$value->tid] = $value->name;
        }
        natcasesort($release_type_list);
        $form['release_type'] = array(
            '#type' => 'select',
            '#default_value' => $filter_value['release_type'] ?
                $filter_value['release_type'] : $default_type,
            '#options' => $release_type_list,
            '#weight' => -9,
//      '#ajax' => array(
//        'callback' => $type_path,
//        'wrapper' => $wrapper,
//        'event' => 'change',
//        'method' => 'replace',
//        'progress' => array(
//          'type' => 'throbber',
//        ),
//      ),
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => '<div class = "release_type_dropdown  hzd-form-element">',
            '#suffix' => '</div><div style="clear:both"></div>',
        );
        
        $default_value_services = $filter_value['services'];
        natcasesort($services);
        $form['services'] = array(
            '#type' => 'select',
            '#options' => $services,
            '#default_value' => $filter_value['services'] ?
                $filter_value['services'] : $default_value_services,
            '#weight' => -7,
//      '#ajax' => array(
//        'callback' => $path,
//        'wrapper' => $wrapper,
//        'event' => 'change',
//        'method' => 'replace',
//        'progress' => array(
//          'type' => 'throbber',
//        ),
//      ),
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => '<div class = "service_search_dropdown  hzd-form-element">',
            '#suffix' => '</div>',
        );
        if($default_value_services == 0){
          $default_value_services = -1;
        }
        $default_value_releases = $filter_value['releases'];
        $options = HzdreleasemanagementHelper::get_dependent_release($default_value_services);
        $form['releases'] = array(
            '#type' => 'select',
            '#options' => $options['releases'],
            '#default_value' => $filter_value['releases'] ?
                $filter_value['releases'] : $form_state->getValue('releases'),
            '#weight' => -3,
//      '#ajax' => array(
//        'callback' => $path,
//        'wrapper' => $wrapper,
//        'event' => 'change',
//        'method' => 'replace',
//        'progress' => array(
//          'type' => 'throbber',
//        ),
//      ),
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => '<div class = "releases_search_dropdown  hzd-form-element">',
            '#suffix' => '</div>',
        );
        
        $form['filter_startdate'] = array(
            '#type' => 'textfield',
            //    '#title' => $this->t('Start Date'),
            '#attributes' => array(
                'class' => array("start_date"),
                'placeholder' => array(
                    '<' . $this->t('Start Date') . '>'
                ),
                'onchange' => 'this.form.submit()',
            ),
            '#default_value' => $filter_value['filter_startdate'] ?
                $filter_value['filter_startdate'] :
                $form_state->getValue('filter_startdate'),
            '#size' => 15,
            '#weight' => 3,
//      '#ajax' => array(
//        'callback' => $path,
//        'wrapper' => $wrapper,
//        'event' => 'change',
//        'method' => 'replace',
//        'disable-refocus' => true,
//        'progress' => array(
//          'type' => 'throbber',
//        ),
//      ),
            '#prefix' => '<div class = "filter_start_date  hzd-form-element">',
            '#suffix' => '</div>',
        );
        
        $form['filter_enddate'] = array(
            '#type' => 'textfield',
//      '#title' => $this->t('End Date'),
            '#size' => 15,
            '#weight' => 4,
            '#attributes' => array(
                'class' => array("end_date"),
                'placeholder' => array(
                    '<' . $this->t('End Date') . '>'
                ),
                'onchange' => 'this.form.submit()',
            ),
            // '#attributes' => array("class" => "end_date"),.
            '#default_value' => $filter_value['filter_enddate'] ? $filter_value['filter_enddate'] :
                $form_state->getValue('filter_enddate'),
//      '#ajax' => array(
//        'callback' => $path,
//        'wrapper' => $wrapper,
//        'event' => 'change',
//        'disable-refocus' => true,
//        'method' => 'replace',
//        'progress' => array(
//          'type' => 'throbber',
//        ),
//      ),
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
            '#default_value' => $filter_value['limit'] ? $filter_value['limit']
                : $form_state->getValue('limit'),
            '#weight' => 8,
//      '#ajax' => array(
//        'callback' => $path,
//        'wrapper' => $wrapper,
//        'event' => 'change',
//        'method' => 'replace',
//        'progress' => array(
//          'type' => 'throbber',
//        ),
//      ),
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => '<div class = "limit_search_dropdown  hzd-form-element">',
            '#suffix' => '</div>',
        );
        $form['actions'] = array(
            '#type' => 'container',
            '#weight' => 100,
        );
        $form['actions']['reset'] = array(
            '#type' => 'button',
            '#value' => t('Reset'),
            '#weight' => 100,
            '#validate' => array(),
            '#attributes' => array(
	       'onclick' => 'reset_form_elements();return false;',
               'class'=>['button','btn-default','btn']
              ),
            '#prefix' => '<div class = "reset_form">',
            '#suffix' => '</div><div style = "clear:both"></div>',
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
//  public function search_type_earlywarning(array $form, FormStateInterface $form_state) {
//    $form_state->setValue('submitted', FALSE);
//    $form_build_id = $_POST['form_build_id'];
//    $request = \Drupal::request();
//
//    $startdate = $form_state->getValue('filter_startdate');
//    $enddate = $form_state->getValue('filter_enddate');
//    $type = $form_state->getValue('type');
//    $limit = $form_state->getValue('limit');
//    $release_type = $form_state->getValue('release_type');
//    $user_input = $form_state->getUserInput();
//    if (isset($user_input['_triggering_element_name']) && $user_input['_triggering_element_name'] != 'release_type') {
//      $service = $form_state->getValue('services');
//      $release = $form_state->getValue('releases');
//    }
//
//    $filter_options = array(
//      'service' => $service,
//      'release' => $release,
//      'limit' => $limit,
//      'startdate' => $startdate,
//      'enddate' => $enddate,
//      'release_type' => $release_type,
//    );
//    $_SESSION['earlywarning_filter_option'] = $filter_options;
//    $string = '';
//    $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
//    $form['services']['#options'] = $default_services['services'];
//    $form['services']['#value'] = array();
//
//    // Geting  release data.
//    $default_releases = HzdreleasemanagementHelper::get_dependent_release($service);
//    $form['releases']['#options'] = $default_releases['releases'];
//    $form['releases']['#value'] = array();
//
//    $output['content']['#prefix'] = '<div id ="earlywarnings_results_wrapper">';
//    $output['content']['earlywarnings_filter_form'] = $form;
//    $output['content']['reset_form']['#prefix'] = '<div class = "reset_form">';
//    $output['content']['reset_form'] = HzdreleasemanagementHelper::releases_reset_element();
//    $output['content']['reset_form']['#suffix'] = '</div><div style ="clear:both" ></div>';
//
//    if ($type == 'releaseWarnings') {
//      $output['content']['result_table']['#prefix'] = '<div class = "view_earlywarnings_output">';
//      $output['content']['result_table']['early_warning_table'] = HzdEarlyWarnings::release_earlywarnings_display_table($filter_options, $release_type);
//      $output['content']['result_table']['#suffix'] = '</div></div>';
//    }
//    else {
//      $output['content']['result_table']['#prefix'] = '<div class = "view_earlywarnings_output">';
//      $output['content']['result_table'] = HzdearlywarningsStorage::view_earlywarnings_display_table($filter_options, $release_type);
//      $output['content']['result_table']['#suffix'] = '</div></div>';
//    }
//    $output['content']['#attached']['drupalSettings']['data'] = $output;
//    $output['content']['#attached']['drupalSettings']['status'] = TRUE;
//    return $output;
//  }
//
//  /**
//   * Ajax call back for search early warnings filters.
//   */
//  public function search_earlywarning(array $form, FormStateInterface $form_state) {
//    $form_state->setValue('submitted', FALSE);
//    $form_build_id = $_POST['form_build_id'];
//
//    $type = $form_state->getValue('type');
//    $limit = $form_state->getValue('limit');
//    $startdate = $form_state->getValue('filter_startdate');
//    $enddate = $form_state->getValue('filter_enddate');
//    $release_type = $form_state->getValue('release_type');
//    $user_input = $form_state->getUserInput();
//    if (isset($user_input['_triggering_element_name']) && $user_input['_triggering_element_name'] != 'release_type') {
//      $service = $form_state->getValue('services');
//      $release = $form_state->getValue('releases');
//    }
//    $filter_options = array(
//      'service' => $service,
//      'release' => $release,
//      'limit' => $limit,
//      'startdate' => $startdate,
//      'enddate' => $enddate,
//      'release_type' => $release_type,
//    );
//    $_SESSION['earlywarning_filter_option'] = $filter_options;
//    $string = '';
//    $default_services = HzdreleasemanagementHelper::get_release_type_services($string, $release_type);
//    $form['services']['#options'] = $default_services['services'];
//    $form['services']['#value'] = $service;
//
//    // Geting  release data.
//    $default_releases = HzdreleasemanagementHelper::get_dependent_release($service);
//    $form['releases']['#options'] = $default_releases['releases'];
//    $form['releases']['#value'] = $release;
//
//    if (!array_key_exists($form_state->getValue('releases'), $default_releases['releases'])) {
//      $filter_options['release'] = 0;
//      $form['releases']['#value'] = 0;
//    }
//    else {
//      $form['releases']['#value'] = $form_state->getValue('releases');
//    }
//
//    $output['content']['#prefix'] = '<div id = "earlywarnings_results_wrapper">';
//    $output['content']['earlywarnings_filter_form'] = $form;
//    $output['content']['reset_form']['#prefix'] = '<div class = "reset_form">';
//    $output['content']['reset_form'] = HzdreleasemanagementHelper::releases_reset_element();
//    $output['content']['reset_form']['#suffix'] = '</div><div style = "clear:both" ></div>';
//    if ($type == 'releaseWarnings') {
//      $output['content']['result_table']['#prefix'] = '<div class = "view_earlywarnings_output">';
//      $output['content']['result_table']['early_warning_table'] = HzdEarlyWarnings::release_earlywarnings_display_table($filter_options, $release_type);
//      $output['content']['result_table']['#suffix'] = '</div></div>';
//    }
//    else {
//      $output['content']['result_table']['#prefix'] = '<div class = "view_earlywarnings_output">';
//      $output['content']['result_table'] = HzdearlywarningsStorage::view_earlywarnings_display_table($filter_options, $release_type);
//      $output['content']['result_table']['#suffix'] = '</div></div>';
//    }
//    $output['content']['#attached']['drupalSettings']['data'] = $output;
//    $output['content']['#attached']['drupalSettings']['status'] = TRUE;
//    return $output;
//  }
    
}
