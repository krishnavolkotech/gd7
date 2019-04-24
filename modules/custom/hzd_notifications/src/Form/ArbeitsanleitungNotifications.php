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

class ArbeitsanleitungNotifications extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arbeitsanleitung_notifications_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $options = [ARBEITSANLEITUNGEN => 'Arbeitsanleitungen'];
    $uid = is_object($user) ? $user->id() : $user;
    $default_interval = HzdNotificationsHelper::get_default_arbeitsanleitung_timeintervals($uid);
    $form['account'] = array('#type' => 'value', '#value' => $uid);
    $form['arbeitsanleitung'] = array(
      '#type' => 'table',
      '#header' => '',
    );
    foreach($options as $content_key => $content) {
      $form['arbeitsanleitung'][$content_key]['subscriptions_type_' . $content_key] = array(
        '#markup' => $content,
        '#prefix' => "<div class = 'hzd_type'>",
        '#suffix' => "</div>"
      );
      $form['arbeitsanleitung'][$content_key]['subscriptions_interval_' . $content_key] = array(
        '#type' => 'radios',
        '#options' => $intervals,
        '#default_value' => isset($default_interval[$uid]) ? $default_interval[$uid] : -1,
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
    $default_send_interval = HzdNotificationsHelper::get_default_arbeitsanleitung_timeintervals($uid);
    $options = [ARBEITSANLEITUNGEN => 'Arbeitsanleitung'];
    //DELETE the previous default intervals for the submitted user
    db_query("DELETE FROM {arbeitsanleitung_notifications__user_default_interval} where uid = :uid", array(":uid" => $uid));

    foreach ($options as $content_key => $content) {
      $subscriptions = $form_state->getValue('arbeitsanleitung');
      $int_val = $subscriptions[$content_key]['subscriptions_interval_' . $content_key];
      // insert arbeitsanleitung default interval
      HzdNotificationsHelper::insert_default_arbeitsanleitung_user_intervel($uid, $int_val);

      if ($int_val != $default_send_interval[$uid]) {
        //remove notifications from previous interval and update with user submitted interval
//        HzdNotificationsHelper::hzd_modify_quickinfo_notifications($content, $default_send_interval[$content], $int_val, $uid);
      }
    }

    drupal_set_message(t('Arbeitsanleitung subscriptions are inserted sucessfully'), 'status');
  }
}
