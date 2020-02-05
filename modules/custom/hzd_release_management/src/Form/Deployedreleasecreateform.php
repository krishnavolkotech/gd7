<?php

namespace Drupal\hzd_release_management\Form;

use Drupal\group\Entity\Group;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\node\Entity\Node;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Url;

define('Zentrale_Release_Manager_Lander', 5);
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

    $wrapper = 'earlywarnings_posting';
    $environment_data = non_productions_list();
    $form['deployed_environment'] = array(
      '#type' => 'select',
      '#title' => t('Environment'),
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
    $form['previous_releases'] = $this->deployed_previous_releases_env($form, $form_state);
    
    $form['deployed_date'] = array(
      '#type' => 'textfield',
      '#title' => t('Date'),
      '#size' => 15,
      // '#date_date_format' => 'german_date',.
      '#required' => TRUE,
      '#maxlength' => '20',
      '#attributes' => array("class" => ["js-deployed-date"]),
      '#weight' => 6,
      '#placeholder' => '<' . t('Date')->render() . '>',

    );

    $form['installation_time'] = array(
      '#type' => 'textfield',
      '#title' => t('Installation Duration'),
      '#description' => 'Dauer des gesamten Softwareinstallations- und Konfigurationsprozesses mit der technischen Vor- und Nacharbeitungsphase bis zum Zeitpunkt der Betriebsfähigkeit.',
      '#size' => 8,
      '#placeholder' =>  t('hh:mm')->render(),
      '#weight' => 7,
    );

    $form['automated_deployment'] = array(
      '#type' => 'checkbox',
      '#description' => 'Automatisierte Installation und Konfiguration über Puppet',
      '#title' => t('Automated Deployment'),
      '#weight' => 8,
    );

    $form['abnormalities'] = array(
      '#type' => 'checkbox',
      '#title' => t('Abnormalities'),
      '#attributes' => array("class" => ["abnormalities"]),
      '#weight' => 9,
    );

    $form['abnormalities_desc'] = array(
      '#type' => 'textarea',
      '#title' => t('Description of Abnormalities'),
      '#attributes' => array("class" => ["abnormalities-desc"], 'style' => 'width:400px;'),
      '#maxlength' => 400,
      '#weight' => 10,
    );
    

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 11,
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
      '#title' => t('Service'),
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
      '#title' => t('Release'),
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

  
  /**
   * Ajax callback for filtering the early warnings dependent releases.
   */
  public function deployed_previous_releases_env(array $form, FormStateInterface $form_state) {
    $service = $form_state->getValue('deployed_services');
    $environment = $form_state->getValue('deployed_environment');

    if ($service != 0 && $environment != 0) {
      $default_releases = get_deployed_dependent_release($service, $environment);
    }
    else {
      $default_releases = array("0" => '<' . t('Previous Release')->render() . '>');
    }

    $form['previous_releases'] = array(
      '#type' => 'select',
      '#title' => t('Previous Release'),
      '#default_value' => 0,
      '#description' => 'In der Umgebung unmittelbar vorhereingesetztes Release',
      '#options' => $default_releases,
      '#weight' => -4,
      "#prefix" => "<div id = 'deployed_previous_release'>",
      '#suffix' => '</div>',
      '#name' => 'previous_releases',
      '#validated' => TRUE,
      '#required' => TRUE,
    );

    return $form['previous_releases'];
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
    $installation_time = $form_state->getValue('installation_time');
    $entered_date = "";
    /**
     *  to do date  format
     */
    // 2016-09-08
    // $deployed_date = strtotime($deployed_date);
    //   $deployed_date =  \Drupal::service('date.formatter')->format($deployed_date, $type = 'medium', 'd.m.y');.

    if ($installation_time) {
        if(!preg_match("/^(?:2[0-4]|[01][1-9]|10):([0-5][0-9])$/", $installation_time)) {
            $form_state->setErrorByName('installation_time', t("Invalid Time Format"));
        }
    }

    $abnormalities = $form_state->getValue('abnormalities');
    $abnormalities_desc = $form_state->getValue('abnormalities_desc');
    if ($abnormalities) {
      if (!trim($abnormalities_desc)) {
        $form_state->setErrorByName('abnormalities_desc', t("Abnormalities Description Required."));
      }
    }
    
    $deployed_date = $form_state->getValue('deployed_date');
    
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

    if ($form_state->getValue('previous_releases') === "0") {
      $form_state->setErrorByName('previous_releases', t("Please Select Previous Release"));
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
      'field_previous_release' => array(
        '0' => array(
          'value' => $form_state->getValue('previous_releases'),
        ),
      ),
      'field_installation_duration' => array(
        '0' => array(
          'value' => $form_state->getValue('installation_time'),
        ),
      ),
      'field_automated_deployment' => array(
        '0' => array(
          'value' => $form_state->getValue('automated_deployment'),
        ),
      ),
      'field_abnormalities' => array(
        '0' => array(
          'value' => $form_state->getValue('abnormalities'),
        ),
      ),
      'field_abnormality_description' => array(
        '0' => array(
          'value' => $form_state->getValue('abnormalities_desc'),
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

      if ($node->field_environment->value == 1) {
          // If environment is Production, delete cache for deployed releases overview table
          // $cids = ['deployedReleasesOverview459', 'deployedReleasesOverview460'];
          // \Drupal::cache()->deleteMultiple($cids);
          \Drupal\Core\Cache\Cache::invalidateTags(array('deployedReleasesOverview'));
      }

      drupal_set_message(t('Release has been deployed sucessfully'), 'status');
      $url = Url::fromRoute('hzd_release_management.deployed_releases', ['group' => Zentrale_Release_Manager_Lander]);
      $form_state->setRedirectUrl($url);
    }

  }

}
