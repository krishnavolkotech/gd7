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
    if (count($user_groups) > 0) {
      $form['account'] = array('#type' => 'value', '#value' => $uid);
      $form['subscription'] = array(
        '#type' => 'table',
        '#header' => array(t('Group Name'), t('Notification Status')), 
        '#attributes' => ['class' => ['subscription_vals']],
      );
/*      $form['subscription']['text']['label'] = array(
        '#markup' => $this->t('All (de) activate'),
        '#prefix' => "<div class = 'hzd_type'>",
        '#suffix' => "</div>"
      );
      $form['subscription']['text']['options'] = array(
        '#type' => 'radios',
        '#options' => $intervals,
//        '#default_value' => '-1',
        '#prefix' => "<div class = 'hzd_time_interval dummy_selects'>",
        '#suffix' => "</div>",
      );*/
      foreach ($user_groups as $gid => $label) {
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
    drupal_set_message(t('Group mail preferences saved successfully'));
    $uid = $form_state->getValue('account');
    //db_delete('group_notifications_user_default_interval')->condition('uid', $uid)->execute();
    $user_groups = HzdNotificationsHelper::hzd_user_groups_list($uid);
    $subscriptions = $form_state->getValue('subscription');
    //foreach($user_groups as $gid => $label) {
    //  $int_val = $subscriptions[$gid]['subscriptions_interval_' . $gid];
    //  if(isset($int_val)) {
    //    //HzdNotificationsHelper::insert_default_group_user_intervel($gid, $label, $int_val, $uid);
    //  }
    //}
    
    $data = NULL;
    foreach ($user_groups as $gid => $label) {
      $defaultData = \Drupal::database()
        ->select('group_notifications_user_default_interval', 'gnudi')
        ->fields('gnudi', ['id'])
        ->condition('group_id', $gid)
        ->condition('uid', $uid)
        ->execute()
        ->fetchField();
      if (empty($defaultData)) {
        \Drupal::database()
          ->insert('group_notifications_user_default_interval')
          ->fields([
            'uid' => $uid,
            'group_id' => $gid,
            'default_send_interval' => $subscriptions[$gid]['subscriptions_interval_' . $gid]
          ])
          ->execute();
      }
      else {
        \Drupal::database()
          ->update('group_notifications_user_default_interval')
          ->fields([
            'uid' => $uid,
            'group_id' => $gid,
            'default_send_interval' => $subscriptions[$gid]['subscriptions_interval_' . $gid]
          ])
          ->condition('id', $defaultData)
          ->execute();
      }
      $data = \Drupal::database()->select('group_notifications', 'gn')
        ->fields('gn', ['id', 'uids', 'send_interval'])
        ->condition('group_id', $gid)
        ->execute()->fetchAllAssoc('send_interval');
      $userChoiceInterval = $subscriptions[$gid]['subscriptions_interval_' . $gid];
      $uids = NULL;
      //pr($data);exit;
      $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
      foreach ($intervals as $interval => $value) {
        if (isset($data[$interval])) {
          $uids = unserialize($data[$interval]->uids);
          //pr($data[$interval]);exit;
          foreach ((array)$uids as $userKey => $item) {
            if ($item == $uid) {
              unset($uids[$userKey]);
            }
          }
          if ($userChoiceInterval == $interval) {
            $uids[] = $uid;
          }
          \Drupal::database()
            ->update('group_notifications')
            ->fields(['uids' => serialize($uids)])
            ->condition('id', $data[$interval]->id)->execute();
        }
        else {
  
          if($subscriptions[$gid]['subscriptions_interval_'.$gid] == $interval){
            $notifyData = [
              'uids' => serialize([$uid]),
              'send_interval' => $interval,
              'group_id' => $gid,
              'group_name'=>$gid
            ];
          }else{
            $notifyData = [
              'uids' => serialize([]),
              'send_interval' => $interval,
              'group_id' => $gid,
              'group_name'=>$gid
            ];
          }
          
          \Drupal::database()
            ->insert('group_notifications')
            ->fields($notifyData)->execute();
        }
      }
      //pr($uids);exit;
    }
  }
  
}
