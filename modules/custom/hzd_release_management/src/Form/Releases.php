<?php

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Released.
 *
 * @package Drupal\hzd_release_management\Controller
 */
class Releases extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'released_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $wrapper = 'released_results_wrapper';
    $container = \Drupal::getContainer();
    $terms = $container->get('entity_type.manager')->getStorage('taxonomy_term')->loadTree('release_type');
    $default_type = 459;
    foreach ($terms as $key => $value) {
      $release_type[$value->tid] = $value->name;
    }
    natcasesort($release_type);
    $form['release_type'] = array(
      '#type' => 'select',
      '#default_value' => 459,
      '#options' => $release_type,
      '#weight' => -9,
      '#ajax' => array(
        'callback' => '::release_result',
        'event' => 'change',
        'wrapper' => $wrapper,
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      "#prefix" => "<div class = 'release_type_dropdown'>",
      '#suffix' => '</div><div style="clear:both"></div>',
    );

    $services[] = '<' . $this->t('Service') . '>';
    $services_obj = \Drupal::database()->query("SELECT n.title, n.nid 
                     FROM {node_field_data} n, {group_releases_view} grv, {node__release_type} nrt 
                     WHERE n.nid = grv.service_id and n.nid = nrt.entity_id and grv.group_id = :gid and nrt.release_type_target_id = :tid 
                     ORDER BY n.title asc", array(":gid" => RELEASE_MANAGEMENT, ":tid" => $default_type))->fetchAll();

    foreach ($services_obj as $services_data) {
      $services[$services_data->nid] = $services_data->title;
    }
    natcasesort($services);
    $form['services'] = array(
      '#type' => 'select',
      '#default_value' => '',
      '#options' => $services,
      '#weight' => -7,
      '#ajax' => array(
        'callback' => '::release_result',
        'event' => 'change',
        'wrapper' => $wrapper,
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      "#prefix" => "<div class = 'service_search_dropdown'>",
      '#suffix' => '</div>',
    );

    $options[] = '<' . t("Releases") . '>';
    $service = \Drupal::request()->get('services');
    if ($service) {
      $release = \Drupal::request()->get('releases');
      $def_releases = get_release($string, $service);
      $options = $def_releases['releases'];
    }
    natcasesort($options);
    $form['releases'] = array(
      '#type' => 'select',
      '#default_value' => '',
      '#options' => $options,
      '#weight' => -3,
      '#ajax' => array(
        'callback' => '::release_result',
        'event' => 'change',
        'wrapper' => $wrapper,
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      "#prefix" => "<div class = 'releases_search_dropdown'>",
      '#suffix' => '</div>',
    );

    $form['filter_startdate'] = array(
      '#type' => 'textfield',
      // '#title' => $this->t('Start Date'),
      '#default_value' => '',
      '#size' => 15,
      '#weight' => 3,
      '#attributes' => array(
        "class" => array("start_date"),
	'placeholder' => array(
          $this->t('Start Date')
        ),
      ),
      '#ajax' => array(
        'callback' => '::release_result',
        'disable-refocus' => true,
        'event' => 'change',
        'wrapper' => $wrapper,
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      '#prefix' => "<div class = 'filter_start_date'>",
      '#suffix' => "</div>",
    );

    $form['filter_enddate'] = array(
      '#type' => 'textfield',
      // '#title' => t('End Date'),
      '#size' => 15,
      '#weight' => 4,
      '#default_value' => '',
      '#attributes' => array(
        "class" => array("end_date"),
        'placeholder' => array(
	  $this->t('End Date')
	),
      ),
      '#ajax' => array(
        'callback' => '::release_result',
        'disable-refocus' => true,
        'event' => 'change',
        'wrapper' => $wrapper,
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      '#prefix' => "<div class = 'filter_end_date'>",
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
      '#default_value' => '',
      '#weight' => 8,
      '#ajax' => array(
        'callback' => '::release_result',
        'event' => 'change',
        'wrapper' => $wrapper,
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      "#prefix" => "<div class = 'limit_search_dropdown'>",
      '#suffix' => '</div>',
    );

    $form['release_result'] = array(
      '#prefix' => '<div id="released_results_wrapper">',
      '#suffix' => '</div>',
      '#markup' => '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   *
   */
  public function release_result(array &$form, FormStateInterface $form_state) {
    /*$body_weight = $form_state->getValue('body_weight');
    $body_height = $form_state->getValue('body_height');
    $weight_unit = $form_state->getValue('weight_units');
    $height_unit = $form_state->getValue('height_units');

    if ((is_numeric($body_weight)) && (is_numeric($body_height))) {
    $body_weight = $this->convert_weight_kgs($body_weight, $weight_unit);
    $body_height = $this->convert_height_mts($body_height, $height_unit);
    $bmi = 1.3*$body_weight/pow($body_height,2.5);
    $bmi = round($bmi, 2);
    $bmi_std = $body_weight/($body_height*$body_height);
    $bmi_std = round($bmi_std, 2);
    $bmi_text = $this->get_bmi_text($bmi);
    $output = t("Your BMI value according to the Quetelet formula is");
    $output .= " <b>". $bmi_std ."</b><br>";
    $output .= t("Your adjusted BMI value according to Nick Trefethen of
    <a href='http://www.ox.ac.uk/media/science_blog/130116.html' target='_blank'>Oxford University's Mathematical Institute</a> is");
    $output .= " <b>". $bmi ."</b><br>". $bmi_text;
    }
    else {
    $output = "Please enter numeric values for weight and height fields";
    }*/
    $output = 'test';
    $element = $form['release_result'];
    $element['#markup'] = $output;
    return $element;
  }

}
