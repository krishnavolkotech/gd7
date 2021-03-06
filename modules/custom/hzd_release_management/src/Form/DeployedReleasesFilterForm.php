<?php

namespace Drupal\hzd_release_management\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Unish\tablesUnitTest;

/**
 * Class DeployedReleasesFilterForm.
 *
 * @package Drupal\hzd_release_management\Form
 */
class DeployedReleasesFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deployed_releases_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->request = Drupal::request();
    $current_path = \Drupal::service('path.current')->getPath();

    $user = \Drupal::currentUser();
    $user_role = $user->getRoles(TRUE);
    $form['#method'] = 'get';
    if (\Drupal\cust_group\Controller\CustNodeController::isGroupAdmin(zrml) || array_intersect($user_role, array('site_administrator', 'administrator'))) {
      $states = get_all_user_state();
      $form['state'] = array(
          '#type' => 'select',
          '#options' => $states,
          '#default_value' => $this->request->get('state', ''),
          "#prefix" => "<div class = 'state_search_dropdown hzd-form-element'>",
          '#suffix' => '</div>',
          '#attributes' => array(
              'onchange' => 'this.form.submit()',
          ),
      );
    }
    $environment_data = non_productions_list();
    $form['environment'] = array(
        '#type' => 'select',
        '#default_value' => $this->request->get('environment'),
        '#options' => $environment_data,
        '#attributes' => array(
            'onchange' => 'this.form.submit()',
        ),
        "#prefix" => "<div class = 'hzd-form-element'>",
        '#suffix' => '</div>',
    );
    $form['service'] = $this->deployed_dependent_services($form, $form_state);
    $form['release'] = $this->deployed_dependent_releases($form, $form_state);
    $form['startdate'] = array(
        '#type' => 'textfield',
        '#attributes' => array(
            'class' => array("start_date"),
            'placeholder' => array(
                '<' . $this->t('Start Date')->render() . '>',
            ),
            'onchange' => 'this.form.submit()',
        ),
        '#default_value' => $this->request->get('startdate'),
        "#prefix" => "<div class = 'hzd-form-element'>",
        '#suffix' => '</div>',
    );
    $form['enddate'] = array(
        '#type' => 'textfield',
        '#attributes' => array(
            'class' => array("end_date"),
            'placeholder' => array(
                '<' . $this->t('End Date')->render() . '>',
            ),
            'onchange' => 'this.form.submit()',
        ),
        '#default_value' => $this->request->get('enddate'),
        "#prefix" => "<div class = 'hzd-form-element'>",
        '#suffix' => '</div>',
    );
    $default_limit = array(
        'all' => '<' . t('All') . '>',
        20 => 20,
        50 => 50,
        100 => 100,
    );
    if ($current_path == "/group/5/eingesetzte-releases/archiv") {
        unset($default_limit['all']);
    }

    if($this->request->get('limit')) {
        $limit_default = $this->request->get('limit');
    } else{
        $limit_default = DISPLAY_LIMIT;
    }
     $form['limit'] = array(
        '#type' => 'select',
        '#options' => $default_limit,
        '#default_value' => $limit_default,
        '#attributes' => array(
            'onchange' => 'this.form.submit()',
        ),
        "#prefix" => "<div class = 'limit_search_dropdown  hzd-form-element'>",
        '#suffix' => '</div>',
    );

    $current_path = \Drupal::service('path.current')->getPath();
    $form['reset_link'] =
    ['#type'=>'container','#attributes'=>['class'=>['reset_form', 'button','btn-default','btn']]];
    $form['reset_link']['link'] = [
        '#title' => $this->t('Reset'),
        '#type' => 'link',
        '#url' => Drupal\Core\Url::fromRouteMatch(\Drupal::routeMatch()),
        '#attributes' =>['class'=>['button','btn-default','btn']]
    ];
    $form['#exclude_from_print']=1;
    return $form;
  }

  public function deployed_dependent_services(&$form, FormState $form_state) {
    $environment = $this->request->get('environment');
    $services_releases = HzdreleasemanagementHelper::released_deployed_releases();
    $services_options = $services_releases['services'];
    asort($services_options, SORT_STRING | SORT_FLAG_CASE | SORT_NATURAL);
    $form['service'] = array(
        '#type' => 'select',
        '#default_value' => $this->request->get('service'),
        '#options' => $services_options,
        '#attributes' => array(
            'onchange' => 'this.form.submit()',
        ),
        "#prefix" => "<div class = 'hzd-form-element'>",
        '#suffix' => '</div>',
    );
    return $form['service'];
  }

  public function deployed_dependent_releases(array &$form, FormStateInterface $form_state) {
    $service = $this->request->get('service', 0);
    $deployedReleaseData[] = '<'.t('Release')->render().'>';
    if ($service) {
      $deployedReleases = \Drupal::entityQuery('node')
              ->condition('field_release_service', $service)
              ->condition('type', 'deployed_releases')
              ->execute();
      foreach ($deployedReleases as $release) {
        $actualReleaseId = node_get_field_data_fast([$release], 'field_earlywarning_release')[$release];
        $actualReleaseTitle = node_get_title_fast([$actualReleaseId])[$actualReleaseId];
        if ($actualReleaseTitle) {
          $deployedReleaseData[$actualReleaseId] = $actualReleaseTitle;
        }
      }
      natcasesort($deployedReleaseData);
    }

    $form['release'] = array(
        '#type' => 'select',
        '#default_value' => $this->request->get('release', 0),
        '#options' => $deployedReleaseData,
        '#attributes' => array(
            'onchange' => 'this.form.submit()',
        ),
        "#prefix" => "<div class = 'hzd-form-element'>",
        '#suffix' => '</div>',
    );

    return $form['release'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    pr($form_state->getValues());
    exit;
  }

  public function resetForm(array &$form, FormStateInterface $form_state) {
    pr($form_state->getValues());
    exit;
  }

}
