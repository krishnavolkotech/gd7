<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\ServiceSpecificNotificationsUserForm
 */

namespace Drupal\hzd_notifications\Form; 

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\Controller\HzdNotifications;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_notifications\HzdNotificationsHelper;
use Drupal\hzd_sams\HzdSamsStorage;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\user\Entity\User;

//define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
class SamsNotifications extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'set_sams_service_specific_notifications';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL) {
    // Target for Ajax Callback
    $form['#prefix'] = "<div id='abo-wrapper'>";
    $form['#suffix'] = "</div>";

    $filterData = $this->updateFilter($form_state);
    
    if ($uid == NULL) {
      $uid = \Drupal::currentUser()->id();
    }
    
    $form['services'] = array(
      '#type' => 'select',
      '#options' => $filterData['services'],
      '#weight' => 0,
      "#prefix" => "<div class='service_dropdown hzd-form-element col-md-3'>",
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::full_callback', // don't forget :: when calling a class method.
        'event' => 'change',
        'wrapper' => 'abo-wrapper', // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'bar',
        ],
      ],
    );

    $classes = [
      0 => '<' . $this->t('All classes') . '>',
      1 => 'BIBLIOTHEK',
      2 => 'ENTWICKLUNGSVERSION',
      3 => 'MOCK',
      4 => 'SCHEMA',
      5 => 'NORM',
    ];

    if ($form_state->hasValue('services')) {
      // Sicherstellen, dass ein Verfahren gewählt wurde.
      $serviceId = $form_state->getValue('services');
      $serviceName = \Drupal::state()->get('samsFilterServices')[$serviceId];
      if ($serviceName === 'REM') {
        // Ergänzt Klassenauswahl 'DSL' für 'REM'.
        $classes[6] = 'DSL';
      }
      if ($serviceName === 'ARC') {
        // Ergänzt Klassenauswahl 'SEU' für 'ARC'.
        $classes[7] = 'SEU';
      }
    }

    $form['classes'] = array(
      '#type' => 'select',
      '#options' => $classes,
      '#weight' => 1,
      "#prefix" => "<div class='service_dropdown hzd-form-element col-md-2'>",
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::full_callback', // don't forget :: when calling a class method.
        'event' => 'change',
        'wrapper' => 'abo-wrapper', // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'bar',
        ],
      ],
    );

    $form['products'] = array(
      '#type' => 'select',
      '#options' => $filterData['products'],
      '#default_value' => 0,
      '#weight' => 2,
      "#prefix" => "<div class='service_dropdown hzd-form-element col-md-3'>",
      '#suffix' => '</div>',
    );

    $status = [
      '<' . $this->t('All status') . '>',
      'FINAL',
      'TEST',
      'RC',
      'DEPRECATED',
    ];

    // REM + DSL => FINAL
    if ($form_state->hasValue('services')) {
      $serviceId = $form_state->getValue('services');
      $serviceName = \Drupal::state()->get('samsFilterServices')[$serviceId];
      if ($serviceName === 'REM') {
        if ($form_state->hasValue('classes')) {
          $classId = $form_state->getValue('classes');
          if ($classId == 6) {
            // Reduziert Statusauswahl auf "FINAL", wenn REM + DSL ausgewählt wurden.
            $status = ['<' . $this->t('All status') . '>', 'FINAL',];
          }
        }
      }
    }

    $form['status'] = array(
      '#type' => 'select',
      '#options' => $status,
      '#weight' => 3,
      "#prefix" => "<div class='service_dropdown hzd-form-element col-md-2'>",
      '#suffix' => '</div>',
    );


    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 4,
      "#prefix" => "<div class='col-md-1'>",
      '#suffix' => '</div>',
    );

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //TODO: Errortypen korrekt implementieren - robin, 12.11.19
    // ??? fehlt nichts mehr - 03.02.2020
    $service = $form_state->getValue('services');
    if($service == 0) {
      $form_state->setErrorByName('services', $this->t('Select Service'));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $product = NULL;
    $uid = \Drupal::routeMatch()->getParameters()->get('user');
    // $uid = \Drupal::currentUser()->id();

    $serviceId = $form_state->getValue('services');
    $service = \Drupal::state()->get('samsFilterServices')[$serviceId];

    $connection = \Drupal::database();
    $query = $connection->select('sams_notifications__user_default_interval', 'snudi');
    $query->fields('snudi', ['id']);
    $query->condition('uid', $uid);
    $query->condition('service', $service);

    $class = 'ALL';
    if($form_state->hasValue('classes')) {
      $classId = $form_state->getValue('classes');
      switch($classId) {
        case 0:
          $class = 'ALL';
          break;
        case 1:
          $class = 'BIBLIOTHEK';
          break;
        case 2:
          $class = 'ENTWICKLUNGSVERSION';
          break;
        case 3:
          $class = 'MOCK';
          break;
        case 4:
          $class = 'SCHEMA';
          break;
        case 5:
          $class = 'NORM';
          break;
        case 6:
          // Sonderlocke REM
          $class = 'DSL';
          break;
        case 7:
          // Sonderlocke ARC
          $class = 'SEU';
          break;
        default:
          $class = 'UNKNOWN';
      }
      $query->condition('class', $class);
    }

    $product = 'ALL';
    if($form_state->hasValue('products')) {
      $productId = $form_state->getValue('products');
      if ($productId > 0) {
        $product = \Drupal::state()->get('samsFilterProducts')[$productId];
      }
      $query->condition('product', $product);
    }

    $status = 'ALL';
    if($form_state->hasValue('status')) {
      $statusId = $form_state->getValue('status');
      switch($statusId) {
        case 0:
          $status = 'ALL';
          break;
        case 1:
          $status = 'FINAL';
          break;
        case 2:
          $status = 'TEST';
          break;
        case 3:
          $status = 'RC';
          break;
        case 4:
          $status = 'DEPRECATED';
          break;
        default:
          $status = 'UNKNOWN';
      }
      $query->condition('status', $status);
    }

    $hitId = $query->execute()->fetchField();

    $preferences = array(
      'uid' => $uid,
      'service' => $service,
      'class' => $class,
      'product' => $product,
      'status' => $status,
    );
    // pr($preferences);
    // exit;
    if($hitId) {
      $update = $connection->update('sams_notifications__user_default_interval')
        ->fields($preferences)
        ->condition('id', $hitId)
        ->execute();
    }
    else {
      $insert = $connection->insert('sams_notifications__user_default_interval')
        ->fields($preferences)
        ->execute();
    }

    // file_put_contents('drupal_debug.txt',"$uid $service $product $class");
    // file_put_contents('drupal_debug.txt',json_encode($result));

    // \Drupal::messenger()->addMessage(json_encode($preferences));
    \Drupal::messenger()->addMessage(t('Service mail preferences saved successfully'));
  }

  /**
   * AJAX callback function.
   * 
   * @param array $form
   *  Form that gets returned to the caller
   * 
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *  Current formstate
   */
  public function full_callback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Updates filters based on formstate
   * 
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *  The current formstate
   */
  public function updateFilter(FormStateInterface $form_state) {
    $service = NULL;
    $class = '';
    $product = NULL;
    if($form_state->getValue('services')) {
      $service = $form_state->getValue('services');
    }

    if($form_state->getValue('classes')) {
      $class = $form_state->getValue('classes');
    }

    // if($form_state->getValue('products')) {
    //   $product = $form_state->getValue('products');
    // }

    // $debug = "Service: " . $service . "\n"
    //   . "Produkt: ". $product . "\n"
    //   . "Version: ". "NULL" . "\n"
    //   . "Klasse: ". $class . "\n"
    //   . "Status: ''";
    // ksm($debug);
    // file_put_contents('drupal_debug.log',json_encode($debug));
    $storage = new HzdSamsStorage();
    $storage->fetch($service, '', NULL, $class, '');
    $samsData = $storage->getFilterData();
    $filterData['services'] = \Drupal::state()->get('samsFilterServices');
    $filterData['services'][0] = '<' . $this->t('Service')->render() . '>';

    // Produkte werden immer gefüllt
    $filterData['products'] = array('<' . $this->t('All products') . '>');
    if (is_array($samsData) && array_key_exists('products', $samsData) && count($samsData['products']) > 0) {
      $stateProducts = \Drupal::state()->get('samsFilterProducts');
      foreach ($samsData['products'] as $product) {
        $unkeyedProducts[] = $product;
      }
      foreach ($unkeyedProducts as $product) {
        $foundProducts[array_search($product, $stateProducts)] = $product;
      }
      if ($foundProducts) {
        $filterData['products'] += $foundProducts;
      }
    }
    return $filterData;
  }
}
