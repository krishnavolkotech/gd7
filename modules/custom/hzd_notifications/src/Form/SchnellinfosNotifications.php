<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\SchnellinfosNotifications
 */

namespace Drupal\hzd_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\Controller\HzdNotifications;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_notifications\HzdNotificationsHelper;
use Drupal\Core\Entity;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

class SchnellinfosNotifications extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schnellinfos_notifications_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL) {
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $options = \Drupal\field\Entity\FieldStorageConfig::loadByName('node','field_other_services')->getSetting('allowed_values');
    $uid = $uid ? $uid : \Drupal::currentUser()->id();
    $default_interval = HzdNotificationsHelper::get_default_quickinfo_timeintervals($uid);
    $form['account'] = array('#type' => 'value', '#value' => $uid);
    $form['schnellinfos'] = array(
      '#type' => 'table',
      '#header' => '',
    );

    foreach($options as $content_key => $content) {
      $form['schnellinfos'][$content_key]['subscriptions_type_' . $content_key] = array(
        '#markup' => $content,
        '#prefix' => "<div class = 'hzd_type'>",
        '#suffix' => "</div>"
      );
      $form['schnellinfos'][$content_key]['subscriptions_interval_' . $content_key] = array(
        '#type' => 'radios',
        '#options' => $intervals,
        '#default_value' => $default_interval ? $default_interval[$content] : -1,
        '#prefix' => "<div class = 'hzd_time_interval'>",
        '#suffix' => "</div>"
      );
    }

    $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('account');
    //DELETE the default intervals for the submitted user
    db_query("DELETE FROM {quickinfo_notifications_user_default_interval} where uid = :uid", array(":uid" => $uid));
    
    $options = \Drupal\field\Entity\FieldStorageConfig::loadByName('node','field_other_services')->getSetting('allowed_values');
    foreach($options as $content_key => $content) {
      $subscriptions = $form_state->getValue('schnellinfos');
      $int_val = $subscriptions[$content_key]['subscriptions_interval_' . $content_key];
      HzdNotificationsHelper::insert_default_quickinfo_user_intervel($content, $int_val, $uid);
    }
    drupal_set_message(t('Quickinfo subscriptions are inserted sucessfully'), 'status');
  }
}
