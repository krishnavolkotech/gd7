<?php

namespace Drupal\hzd_release_inprogress_comments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_release_inprogress_comments\HzdReleaseCommentsStorage;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;


if (!defined('KONSONS')) {
  define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
}


// TODO.
// $_SESSION['Group_id'] = 339;.

/**
 *
 */
class ReleaseCommentsFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'releasecomment_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {

    $filter_value = HzdReleaseCommentsStorage::get_releasecomments_filters();
    $group_id = get_group_id();
    $form['#method'] = 'get';
    $services[] = '<' . $this->t('Service') . '>';

    $release_type = $filter_value['release_type'];
    if (isset($group_id) && $group_id != RELEASE_MANAGEMENT) {
      $default_type = db_query("SELECT release_type FROM "
        . "{default_release_type} WHERE group_id = :gid",
        array(":gid" => $group_id))->fetchField();
      $default_type = isset($release_type) ? $release_type :
        (isset($default_type) ? $default_type : KONSONS);
    } else {
      $default_type = $release_type ? $release_type : KONSONS;
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
      $services[$services_data->nid] = $services_data->title;
    }

    $form['type'] = array(
      '#type' => 'hidden',
      '#default_value' => $type
    );
    $form['#prefix'] = "<div class = 'releases_filters'>";
    $form['#suffix'] = "</div>";

    $container = \Drupal::getContainer();
    $terms = $container->get('entity.manager')
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
      '#attributes' => array(
        'onchange' => 'this.form.submit()',
      ),
      '#prefix' => '<div class = "service_search_dropdown  hzd-form-element">',
      '#suffix' => '</div>',
    );
    if ($default_value_services == 0) {
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
      '#attributes' => array(
        'onchange' => 'this.form.submit()',
      ),
      '#prefix' => '<div class = "releases_search_dropdown  hzd-form-element">',
      '#suffix' => '</div>',
    );

    $form['filter_startdate'] = array(
      '#type' => 'textfield',
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
      '#prefix' => '<div class = "filter_start_date  hzd-form-element">',
      '#suffix' => '</div>',
    );

    $form['filter_enddate'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#weight' => 4,
      '#attributes' => array(
        'class' => array("end_date"),
        'placeholder' => array(
          '<' . $this->t('End Date') . '>'
        ),
        'onchange' => 'this.form.submit()',
      ),
      '#default_value' => $filter_value['filter_enddate'] ? $filter_value['filter_enddate'] :
        $form_state->getValue('filter_enddate'),
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
      '#attributes' => array('onclick' => 'reset_form_elements();return false;'),
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

}
