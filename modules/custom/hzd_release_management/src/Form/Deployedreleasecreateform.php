<?php

namespace Drupal\hzd_release_management\Form;

use Drupal\group\Entity\Group;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\node\Entity\Node;
use Drupal\group\Entity\GroupContent;


/**
 *
 */
class Deployedreleasecreateform extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deployedreleases_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'] = array(
      'hzd_release_management/deployed_releases',
    );
//    $group = \Drupal::routeMatch()->getParameter('group');
//    if (is_object($group)) {
//      $group_id = $group->id();
//    }
//    else {
//      $group_id = $group;
//    }

//    $services_releases = HzdreleasemanagementHelper::released_deployed_releases();
//    $services_data = $services_releases['services'];
//pr($services_data);exit;
    $wrapper = 'earlywarnings_posting';

    $environment_data = non_productions_list();
    $form['deployed_environment'] = array(
      '#type' => 'select',
      '#default_value' => 1,
      '#options' => $environment_data,
      '#weight' => -6,
      '#ajax' => array(
        'callback' => '::releases_ajax_callback',
        'wrapper' => 'deployed_release_form_warapper',
        'event' => 'change',
        'method' => 'html',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
    );

    $form['deployed_services'] = $this->deployed_dependent_services($form, $form_state);
    $form['deployed_releases'] = $this->deployed_dependent_releases_env($form, $form_state);

    /*$form['deployed_releases'] = array(
      '#type' => 'select',
      '#default_value' => 0,
      '#options' => [0=>$this->t("< Release >")],
      '#weight' => -4,
      "#prefix" => "<div id = 'deployed_dependent_release'>",
      '#suffix' => '</div>',
//      '#validated' => TRUE,
    );*/
    /**
     * $form['releases'] = array(
     * '#type' => 'select',
     * '#options' => $options,
     * '#default_value' => $default_value_releases,
     * '#weight' => -6,
     * '#ajax' => array(
     * 'callback' => $rel_path,
     * 'wrapper' => $wrapper,
     * 'event' => 'change',
     * 'method' => 'replace',
     * 'progress' => array(
     * 'type' => 'throbber',
     * ),
     * ),
     * "#prefix" => "<div class = 'releases_search_dropdown hzd-form-element'>",
     * '#suffix' => '</div>',
     * '#validated' => TRUE
     * );
     */
    // $date_format = 'd.m.Y';.
    $form['deployed_date'] = array(
      '#type' => 'textfield',
//      '#title' => t('Date'),
      '#size' => 15,
      // '#date_date_format' => 'german_date',.
      '#required' => TRUE,
      '#maxlength' => '20',
      '#attributes' => array("class" => ["js-deployed-date"]),
      '#weight' => -3,
      '#placeholder' => '<' . t('Date')->render() . '>',

    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => -2,
    );

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function deployed_dependent_services(array $form, FormStateInterface $form_state) {
    $environment = $form_state->getValue('deployed_environment', 1);

    if ($environment != 0) {
      $services_releases = HzdreleasemanagementHelper::released_deployed_releases();
//    $form['deployed_services']['#options'] = $services_releases['services'];
//    $form['deployed_services']['#default_value'] = 0;
      $deployed_options = $services_releases['services'];
      natcasesort($deployed_options);
    }
    else {
      $deployed_options[] = '<'.t('Service')->render().'>';
    }

    $form['deployed_services'] = array(
      '#type' => 'select',
      '#default_value' => $form_state->getValue('deployed_services', NULL),
      '#options' => $deployed_options,
      '#weight' => -5,
      '#ajax' => array(
        'callback' => '::releases_ajax_callback',
        'wrapper' => 'deployed_release_form_warapper',
        'event' => 'change',
        'method' => 'html',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
    );

//    $form_state->setRebuild(TRUE);

    return $form['deployed_services'];
  }

  /**
   * Ajax callback for filtering the early warnings dependent releases.
   */
  public function deployed_dependent_releases(array &$form, FormStateInterface $form_state) {
//    $service = $form_state->getValue('deployed_services');
    $environment = $form_state->getValue('deployed_environment');
    $services_releases = HzdreleasemanagementHelper::released_deployed_releases();
    $services_options = $services_releases['releases'];
    $services_options[0] = t('< @release >', ['@release' => 'Release']);
    natcasesort($services_options);
//    array_unshift($services_releases['releases'], '');
    // $form_state->setValue('submitted', FALSE);
    // Geting  release data.
//    if ($service != 0 && $environment != 0) {
    $default_releases = get_undeployed_dependent_release('', $environment);
//    }
//    else {
    $default_releases[] = '<' . t("Release") . '>';
//    }
//pr($default_releases);exit;
    $form['deployed_releases']['#options'] = $services_options;

    //$form_state->setRebuild(TRUE);

    return $form['deployed_releases'];
  }

  /**
   * Ajax callback for filtering the early warnings dependent releases.
   */
  public function deployed_dependent_releases_env(array $form, FormStateInterface $form_state) {
    $service = $form_state->getValue('deployed_services');
    $environment = $form_state->getValue('deployed_environment');

    // $form_state->setValue('submitted', FALSE);.
    if ($service != 0 && $environment != 0) {
      $default_releases = get_undeployed_dependent_release($service, $environment);
    }
    else {
      $default_releases = array("0" => '<' . t('Release')->render() . '>');
    }

//    $form['deployed_releases']['#options'] =$default_releases;
//    $form['deployed_releases']['#default_value'] = 0;

    $form['deployed_releases'] = array(
      '#type' => 'select',
      '#default_value' => 0,
      '#options' => $default_releases,
      '#weight' => -4,
      "#prefix" => "<div id = 'deployed_dependent_release'>",
      '#suffix' => '</div>',
      '#name' => 'deployed_releases',
      '#validated' => TRUE,
    );


    //$form_state->setRebuild(TRUE);

    return $form['deployed_releases'];
  }

  public function releases_ajax_callback(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);

    $trig_el = $form_state->getTriggeringElement()['#array_parents'][0];
    if ($trig_el == 'deployed_environment') {
      $default_releases = array("0" => '<' . t('Release')->render() . '>');
      $form['deployed_services']['#value'] = 0;
      $form['deployed_releases']['#options'] = $default_releases;
      $form['deployed_releases']['#value'] = 0;
    }

    return $form;
  }

  /**
   * {@inheritDoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $deployed_date = $form_state->getValue('deployed_date');
    /**
     *  to do date  format
     */
    // 2016-09-08
    // $deployed_date = strtotime($deployed_date);
    //   $deployed_date =  \Drupal::service('date.formatter')->format($deployed_date, $type = 'medium', 'd.m.y');.
    $today_date = mktime(23, 59, 59, date('m', time()), date('d', time()), date('y', time()));
    if ($deployed_date) {
      $date = explode('.', $deployed_date);
      if (!empty($date) && count($date) !== 3) {
        $form_state->setErrorByName('deployed_date', t("Enter correct date."));
      }
      $entered_date = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
    }

    if ($entered_date > $today_date) {
      $form_state->setErrorByName('deployed_date', t("Deployed date should not be greater than present date."));
    }

    if (!$form_state->getValue('deployed_services')) {

      $form_state->setErrorByName('deployed_services', t("Please Select service"));
    }

    if (!$form_state->getValue('deployed_releases')) {

      $form_state->setErrorByName('deployed_releases', t("Please Select releases"));
    }

    if (!$form_state->getValue('deployed_environment')) {
      $form_state->setErrorByName('deployed_environment', t("Please Select environment"));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $group = \Drupal::routeMatch()->getParameter('group');

    if (is_object($group)) {
      $group_id = $group->id();
    }
    else {
      $group_id = $group;
    }

    $user = \Drupal::currentUser();

    $query = \Drupal::database()->select('cust_profile', 'cp');
    $query->addField('cp', 'state_id');
    $query->condition('cp.uid', $user->id(), '=');
    $user_state = $query->execute()->fetchField();

    // $date_array = explode('.', $form_state->getValue('deployed_date'));
    //    $deployed_date = mktime(0, 0, 0, $date_array[1], $date_array[0], $date_array[2]);
    // print_r($form_state->getValue());
    // echo '<pre>'; print_r($_POST);   exit;
    // dpm($deployed_date);
    $deployed_date = $form_state->getValue('deployed_date');
    $deployed_date = date("Y-m-d", strtotime($deployed_date));
    // Echo $deployed_date; exit;.
    $node_array = array(
      'type' => 'deployed_releases',
      'title' => array(
        '0' => array(
          'value' => $form_state->getValue('deployed_services') . '_service_' . $form_state->getValue('deployed_releases'),
        ),
      ),
      'uid' => array(
        '0' => array(
          'target_id' => $user->id(),
        ),
      ),
      'status' => array(
        '0' => array(
          'value' => 1,
        ),
      ),
      'field_archived_release' => array(
        '0' => array(
          'value' => 0,
        ),
      ),
      'field_date_deployed' => array(
        '0' => array(
          'value' => $deployed_date,
        ),
      ),
      'field_earlywarning_release' => array(
        '0' => array(
          'value' => $form_state->getValue('deployed_releases'),
        ),
      ),
      'field_environment' => array(
        '0' => array(
          'value' => $form_state->getValue('deployed_environment'),
        ),
      ),
      'field_release_service' => array(
        '0' => array(
          'value' => $form_state->getValue('deployed_services'),
        ),
      ),
      'field_user_state' => array(
        '0' => array(
          'value' => $user_state,
        ),
      ),
    );

    $node = Node::create($node_array);
    $node->save();

    $nid = $node->id();
    if ($nid) {
      $group = Group::load(RELEASE_MANAGEMENT);

      $group_content = GroupContent::create([
        'type' => $group->getGroupType()
          ->getContentPlugin('group_node:release')
          ->getContentTypeConfigId(),
        'gid' => RELEASE_MANAGEMENT,
        'entity_id' => $node->id(),
        'request_status' => 1,
        'label' => $form_state->getValue('deployed_services') . '_service_' . $form_state->getValue('deployed_releases'),
        'uid ' => 1,
      ]);
      $group_content->save();
      drupal_set_message(t('Release has been deployed sucessfully'), 'status');
    }

  }

}
