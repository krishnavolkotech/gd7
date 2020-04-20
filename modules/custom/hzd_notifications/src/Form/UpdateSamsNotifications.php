<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\UpdateServiceSpecificNotifications
 */

namespace Drupal\hzd_notifications\Form; 

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\Controller\HzdNotifications;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_notifications\HzdNotificationsHelper;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;

//define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
class UpdateSamsNotifications extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_sams_notifications';
  }

  /**
   * {@inheritdoc}
   */
  //$vals->id, $uid, $vals->service, $vals->product, $vals->version, $vals->status
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL, $uid = NULL, $service = NULL, $class = NULL, $product = NULL, $status = NULL) {

    // pr($id);
    
    // $content_types = HzdNotificationsHelper::_get_content_types($service_id, FALSE, $rel_type);
    // if (HzdreleasemanagementStorage::RWCommentAccess()) {
    //   $types = array('downtimes' => 1, 'problem' => 2, 'release' => 3, 'early_warnings' => 4, 'release_comments' => 5);
    // } else {
    //   $types = array('downtimes' => 1, 'problem' => 2, 'release' => 3, 'early_warnings' => 4);
    // }
    // $intervals = HzdNotificationsHelper::hzd_notification_send_interval();

    $uid = $uid ? $uid : \Drupal::currentUser()->id();

    if ($class == 'ALL') {
      $class = 'Alle';
    }
    if ($product == 'ALL') {
      $product = 'Alle';
    }
    if ($status == 'ALL') {
      $status = 'Alle';
    }

    // pr($uid);
    // pr($id);
    $form['id'] = array(
      '#type' => 'hidden',
      '#value' => $id
    );

    $form['account'] = array(
      '#type' => 'hidden',
      '#value' => $uid
    );

    $form['services'] = array(
      '#type' => 'textfield',
      '#default_value' => $service,
      '#disabled' => TRUE,
      '#prefix' => "<div class = 'service_dropdown hzd-form-element col-md-3'>",
      '#suffix' => '</div>', 
    );

    $form['classes'] = array(
      '#type' => 'textfield',
      '#default_value' => $class,
      '#prefix' => "<div class = 'content-type hzd-form-element col-md-2'>",
      '#suffix' => '</div>',
      '#disabled' => TRUE,
    );

    $form['products'] = array(
      // '#type' => 'select',
      '#type' => 'textfield',
      // '#size' => 60,
      // '#options' => $formProduct,
      // '#options' => array($product),
      '#default_value' => $product,
      '#prefix' => "<div class = 'content-type hzd-form-element col-md-3'>",
      '#suffix' => '</div>',
      '#disabled' => TRUE,
    );

    $form['status'] = array(
      '#type' => 'textfield',
      '#default_value' => $status,
      '#prefix' => "<div class = 'content-type hzd-form-element col-md-2'>",
      '#suffix' => '</div>',
      '#disabled' => TRUE,
    );
    // $form['send_interval'] = array(
    //   '#type' => 'select',
    //   '#default_value' => $send_interval,
    //   '#attributes' =>  array('class' => ['notification_interval_' . $service_id]),
    //   '#options' => $intervals,
    //   '#prefix' => "<div class = 'send-interval hzd-form-element'>",
    //   '#suffix' => '</div>',
    // );

    // $form['submit'] = array(
    //   '#type' => 'submit',
    //   '#value' => t('Update'),
    //   '#name' => 'Update',
    // );

    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#name' => 'Delete',
      '#prefix' => "<div class = 'col-md-1'>",
      '#suffix' => '</div>',
      '#attributes' => array(
        'hzdaction' => 'delete',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Fehler: Daten konnten nicht gespeichert werden. Bitte probieren Sie es erneut.', 'error');
  }
}
