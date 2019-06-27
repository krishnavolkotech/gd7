<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\ServiceNotificationsUserForm
 */

namespace Drupal\hzd_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\Controller\HzdNotifications;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_notifications\HzdNotificationsHelper;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;

//define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
class ServiceNotificationsUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'service_notifications_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL, $rel_type = NULL) {
    $form['rel_type'] = array('#type' => 'hidden', '#value' => $rel_type);
    $uid = $uid ? $uid : \Drupal::currentUser()->id();
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    $content_types = HzdNotificationsHelper::service_notifications_content_type($rel_type);
    $default_interval = HzdNotificationsHelper::_get_default_timeintervals($uid, $rel_type);
    $form['account'] = array('#type' => 'value', '#value' => $uid);
    if ($rel_type == KONSONS) {
      $types = array(1 => 'downtimes', 2 => 'problem', 3 => 'release', 4 => 'early_warnings');
      if (HzdreleasemanagementStorage::RWCommentAccess()) {
        $types[5] = 'release_comments';
      }
    }
    else {
      $types = array(3 => 'release', 4=>'early_warnings');
    }

    $form['subscriptions'] = array(
      '#type' => 'table',
      '#header' => array('Type', t('Notification Status')),
    );

    foreach($content_types as $content_key => $content) {
      $form['subscriptions'][$content_key]['subscriptions_type_' . $content_key] = array(
        '#markup' => $content,
        '#prefix' => "<div class = 'hzd_type'>",
        '#suffix' => "</div>"
      );
      $form['subscriptions'][$content_key]['subscriptions_interval_' . $content_key] = array(
        '#type' => 'radios',
        '#options' => $intervals,
        '#default_value' => isset($default_interval[$types[$content_key]]) ? $default_interval[$types[$content_key]] : -1,
        '#prefix' => "<div class = 'hzd_time_interval'>",
        '#suffix' => "</div>"
      );
    }
    $fno = count($form['subscriptions']) - 1;

    if ($rel_type == KONSONS) {
      //Getting the default time intervals for the planning files of release management
      $planning_files_default_interval = db_query("SELECT default_send_interval FROM {planning_files_notifications_default_interval} 
                                         WHERE uid = :uid", array(":uid" => $uid))->fetchField();
      $form['subscriptions'][$fno]['subscriptions_type_' . $fno] = array(
        '#markup' => t("Planning Files"),
        '#prefix' => "<div class = 'hzd_type'>",
        '#suffix' => "</div>"
      );
      $form['subscriptions'][$fno]['subscriptions_interval_' . $fno] = array(
        '#type' => 'radios',
        '#options' => $intervals,
        // $planning_files_default_interval is False/0/-1 
        '#default_value' => (empty($planning_files_default_interval) && $planning_files_default_interval !== FALSE) ? $planning_files_default_interval : -1,
        '#prefix' => "<div class = 'hzd_time_interval'>",
        '#suffix' => '</div>'
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
    drupal_set_message(t('Service mail preferences saved successfully'));
    $uid = $form_state->getValue('account');
    $rel_type = $form_state->getValue('rel_type');
    $mod_type = $types = HzdNotificationsHelper::hzd_get_content_type_name($rel_type);
    $content_types = HzdNotificationsHelper::service_notifications_content_type($rel_type);
    $subscriptions = $form_state->getValue('subscriptions');
    $default_interval = hzd_get_default_interval($uid, $rel_type);
    //$types = [1=>'downtimes',2=>'problem',3=>'release',4=>'early_warnings'];

    foreach ($subscriptions as $key => $content_value) {
      if(key_exists($key, $mod_type)) {
        if(key_exists($mod_type[$key], $default_interval)) {
          if ($content_value['subscriptions_interval_' . $key] == $default_interval[$mod_type[$key]]) {
            unset($content_types[$key]);
          }
        }
      }
    };

    $services = '';
    $user_notifications = '';
    foreach($content_types as $content_key => $content) {
      // get all services
      if($content_key == 2) {
        $services = hzd_get_all_services();
      } else {
        $services = hzd_get_all_services($rel_type);
      }
      //get default interval of each services and type
      $user_notifications = HzdNotificationsHelper::hzd_get_user_notifications($uid, $rel_type, $services, $default_interval);
      $int_val = $subscriptions[$content_key]['subscriptions_interval_' . $content_key];
      HzdNotificationsHelper::insert_default_user_intervel($types[$content_key], $int_val, $uid, $rel_type);
      if(isset($default_interval[$types[$content_key]]) && $default_interval[$types[$content_key]] != $int_val) {
        // get all services to update the interval
        HzdNotificationsHelper::hzd_modify_services($user_notifications, $types[$content_key], $uid, $default_interval[$types[$content_key]], $int_val);
      }
      foreach($services as $service){
        $data = \Drupal::database()->select('service_notifications','sn')
          ->fields('sn')
          ->condition('service_id',$service->nid)
          ->condition('type',$types[$content_key])
          ->execute()->fetchAllAssoc('send_interval');
          //pr($data);exit;
          $uids = [];
          
        $checkId = \Drupal::database()->select('service_notifications_override','sno')
          ->fields('sno',['sid'])
          ->condition('service_id',$service->nid)
          ->condition('type',$types[$content_key])
          ->condition('rel_type',$rel_type)
          ->condition('uid',$uid)
          ->execute()
          ->fetchField();
        if($checkId) {
            continue;
        }
          
        $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
        foreach($intervals as $interval=>$val){
          
          if(isset($data[$interval])){
            $uids = unserialize($data[$interval]->uids);
            //pr($data[$interval]);exit;
            foreach($uids as $userKey => $item){
              if($item == $uid){
                unset($uids[$userKey]);
              }
            }
            if($int_val == $interval){
              $uids[] = $uid;
            }
            \Drupal::database()
              ->update('service_notifications')
              ->fields(['uids'=>serialize($uids)])
              ->condition('sid',$data[$interval]->sid)->execute();
          }else{
            if($subscriptions[$content_key]['subscriptions_interval_'.$content_key] == $interval){
              $notifyData = ['uids'=>serialize([$uid]),'send_interval'=>$interval,'service_id'=>$service->nid,'type'=>$types[$content_key]];
              
            }else{
              $notifyData = ['uids'=>serialize([]),'send_interval'=>$interval,'service_id'=>$service->nid,'type'=>$types[$content_key]];
            }
            \Drupal::database()
              ->insert('service_notifications')
              ->fields($notifyData)->execute();
          }
        }
      }
    }
    // planning files notifications
    if($rel_type == KONSONS) {
      if (HzdreleasemanagementStorage::RWCommentAccess()) {
        $pf_int_val = $subscriptions[6]['subscriptions_interval_6'];
      } else {
        $pf_int_val = $subscriptions[5]['subscriptions_interval_5'];
      }
      $default_pf_interval = HzdNotificationsHelper::get_default_pf_timeintervals($uid);
      HzdNotificationsHelper::insert_default_pf_user_intervel($pf_int_val, $uid);
      if(($pf_int_val != $default_pf_interval) || ($default_pf_interval == '')) {
        //remove notifications from previous interval and update with user submitted interval
        HzdNotificationsHelper::hzd_modify_pf_notifications($uid, $pf_int_val, $default_pf_interval);
      }
      $data = \Drupal::database()->select('planning_files_notifications','pfn')
        ->fields('pfn')
        ->execute()->fetchAllAssoc('send_interval');
        $userChoiceInterval = $pf_int_val;
        $uids = null;
        //pr($data);exit;
      $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
      foreach($intervals as $interval=>$val){
        if(isset($data[$interval])){
          $uids = unserialize($data[$interval]->uids);
          //pr($data[$interval]);exit;
          if(($key = array_search($uid, $uids)) !== false) {
            unset($uids[$key]);
          }
          if($pf_int_val == $interval){
            $uids[] = $uid;
          }
          \Drupal::database()
            ->update('planning_files_notifications')
            ->fields(['uids'=>serialize($uids)])
            ->condition('id',$data[$interval]->id)->execute();
        }else{
  
          if($subscriptions[$content_key]['subscriptions_interval_'.$content_key] == $interval){
            $notifyData = ['uids'=>serialize([$uid]),'send_interval'=>$interval];
    
          }else{
            $notifyData = ['uids'=>serialize([]),'send_interval'=>$interval];
          }
          \Drupal::database()
            ->insert('planning_files_notifications')
            ->fields($notifyData)->execute();
        }
      }
      
    }
  }

}
