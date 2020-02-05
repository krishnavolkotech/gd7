<?php

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;


if (!defined('KONSONS'))
    define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));


class DeployedinfoFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deployed_info_filter_form';
  }
    
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $filter_value = HzdreleasemanagementStorage::get_release_filters();
    $group_id = get_group_id();
    $form['#method'] = 'get';
        
    $wrapper = 'released_results_wrapper';
    $services[] = '<' . $this->t('Service')->render() . '>';
    
    $release_type = $filter_value['release_type'];
    if (!$release_type) {
        if (isset($group_id) && $group_id != RELEASE_MANAGEMENT) {
            $default_type = db_query("SELECT release_type FROM {default_release_type} "
            . "WHERE group_id = :gid", array(":gid" => $group_id))->fetchField();
            $default_type = (isset($default_type) ? $default_type : KONSONS);
        } else {
            $default_type = KONSONS;
        }
    }
    else {
      $default_type = $release_type;
    }
    
    $services_obj = db_query("SELECT n.title, n.nid
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
        $serviceNode = $services_data->nid;
        $services[$services_data->nid] = node_get_title_fast([$serviceNode])[$serviceNode];
    }
    
    $container = \Drupal::getContainer();
    $terms = $container->get('entity.manager')
                       ->getStorage('taxonomy_term')->loadTree('release_type');
    
    foreach ($terms as $key => $value) {
        $release_type_list[$value->tid] = $value->name;
    }
    
    $form['#prefix'] = "<div class = 'releases_filters'>";
    $form['#suffix'] = "</div>";
    
    if ($type == 'deployed') {
        if (!$filter_value['deployed_type']) {
            $filter_value['deployed_type'] = "current";
        }
        
        $states = get_all_user_state();
        $form['states'] = array(
            '#type' => 'select',
            '#options' => $states,
            '#default_value' => isset($filter_value['states']) ? $filter_value['states']: $form_state->getValue('states'),
            '#weight' => -28,
            "#prefix" => "<div class = 'state_search_dropdown hzd-form-element'>",
            '#suffix' => '</div>',
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
        );
        
        $environment_data = HzdreleasemanagementStorage::get_environment_options(\Drupal::request()->get('states'));
        $form['environment_type'] = array(
            '#type' => 'select',
            '#default_value' => isset($filter_value['environment_type']) ?
            $filter_value['environment_type'] : $form_state->getValue('environment_type'),
            '#options' => $environment_data,
            '#weight' => -26,
            '#validated' => TRUE,
            "#prefix" => "<div class = 'env-type hzd-form-element'>",
            '#suffix' => '</div>',
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
        );
    }
    
    natcasesort($release_type_list);
    $form['release_type'] = array(
        '#type' => 'select',
        '#default_value' => $default_type,
        '#options' => $release_type_list,
        '#weight' => -25,
        "#prefix" => "<div class = 'release_type_dropdown hzd-form-element'>",
        '#suffix' => '</div><div style="clear:both"></div>',
        '#attributes' => array(
            'onchange' => 'jQuery(\'select[name="services"]\').prop(\'selectedIndex\',0);jQuery(\'select[name="releases"]\').prop(\'selectedIndex\',0);this.form.submit()',
        ),
    );
    
    $timer = \Drupal::config('hzd_release_management.settings')->get('timer');
    $default_value_services = $filter_value['services'];
    if (!$default_value_services) {
        $default_value_services = isset($timer) ? $timer : $form_state->getValue('services');
    }
    asort($services, SORT_STRING | SORT_FLAG_CASE | SORT_NATURAL);
    $form['services'] = array(
        '#type' => 'select',
        '#options' => $services,
        '#default_value' => $default_value_services,
        '#weight' => -7,
        "#prefix" => "<div class = 'service_search_dropdown hzd-form-element'>",
        '#suffix' => '</div>',
        '#attributes' => array(
            'onchange' => 'jQuery(\'select[name="releases"]\').prop(\'selectedIndex\',0);this.form.submit()',
        ),
    );
    
    $service = $filter_value['services'];
    $options = array('<' . $this->t('Release') . '>');
    if ($service) {
        $def_releases = get_release($type, $service);
        $options = $def_releases['releases'];
        // Adding Inprogress release type in filter only on deployed form
        if($type == 'deployed') {
            $progress_data = get_release('progress', $service);
            $progress_options = $progress_data['releases'];
            unset($progress_options[0]);
            $options = $options + $progress_options;
        }
    }
    
    $form['r_type'] = array(
        '#type' => 'hidden',
        '#value' => $type
    );
    
    $timer = \Drupal::config('hzd_release_management.settings')->get('timer');
    $default_value_releases = $filter_value['releases'];
    if (!$default_value_releases) {
        $default_value_releases = isset($timer) ? $timer : $form_state->getValue('releases');
    }
    natcasesort($options);
    $form['releases'] = array(
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $default_value_releases,
        '#weight' => -6,
        "#prefix" => "<div class = 'releases_search_dropdown hzd-form-element'>",
        '#suffix' => '</div>',
        '#attributes' => array(
            'onchange' => 'this.form.submit()',
        ),
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
        '#default_value' => isset($filter_value['limit']) ?
        $filter_value['limit'] : $form_state->getValue('limit'),
        '#weight' => 8,
        '#attributes' => array(
            'onchange' => 'this.form.submit()',
        ),
        "#prefix" => "<div class = 'limit_search_dropdown hzd-form-element'>",
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
        '#attributes' => array('onclick' => 'reset_form_elements();return false;'),
        '#prefix' => '<div class = "reset_form">',
        '#suffix' => '</div><div style = "clear:both"></div>',
    );
    $form['#exclude_from_print'] = 1;
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
