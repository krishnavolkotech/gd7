<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\GroupNotifications
 */

namespace Drupal\hzd_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\Controller\HzdNotifications;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_notifications\HzdNotificationsHelper;
use Drupal\Core\Entity;

class GroupNotifications extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_notifications_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL) {
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $uid = $uid ? $uid : \Drupal::currentUser()->id();
    $user_groups = HzdNotificationsHelper::hzd_user_groups_list($uid);

    $default_intval = HzdNotificationsHelper::hzd_user_group_default_interval($uid);
    if(count($user_groups) > 0) {
      $form['account'] = array('#type' => 'value', '#value' => $uid);
      $form['subscription'] = array(
        '#type' => 'table',
        '#header' => '',
      );
      
      foreach($user_groups as $gid => $label) {
        $form['subscription'][$gid]['subscriptions_type_' . $gid] = array(
          '#markup' => $label,
          '#prefix' => "<div class = 'hzd_type'>",
          '#suffix' => "</div>"
        );
        $form['subscription'][$gid]['subscriptions_interval_' . $gid] = array(
          '#type' => 'radios',
          '#options' => $intervals,
          '#default_value' => isset($default_intval[$gid]) ? $default_intval[$gid] : '-1',
          '#prefix' => "<div class = 'hzd_time_interval'>",
          '#suffix' => "</div>"
        );
      }
      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      );
    }
    else {
      $form['empty_groups'] = array(
        '#markup' => 'No Groups',
      );
    }
    return $form;
  }

  /*
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('account');
    db_delete('group_notifications_user_default_interval')->condition('uid', $uid)->execute();
    $user_groups = HzdNotificationsHelper::hzd_user_groups_list($uid);
    foreach($user_groups as $gid => $label) {
      $subscriptions = $form_state->getValue('subscription');
      $int_val = $subscriptions[$gid]['subscriptions_interval_' . $gid];
      if(isset($int_val)) {
        HzdNotificationsHelper::insert_default_group_user_intervel($gid, $label, $int_val, $uid);
      }
    }
  }
}
