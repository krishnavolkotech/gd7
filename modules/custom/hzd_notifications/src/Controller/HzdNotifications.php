<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Controller\HzdNotifications
 */

namespace Drupal\hzd_notifications\Controller;
use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\hzd_notifications\HzdNotificationsHelper;

define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
define('EXEOSS', \Drupal::config('hzd_release_management.settings')->get('ex_eoss_service_term_id'));

/**
 * Class HzdNotifications
 * @package Drupal\hzd_notifications\Controller
 */
class HzdNotifications extends ControllerBase {

  // konsons notification settings
  public function service_notifications($user = NULL) {
    $output[]['#attached']['library'] = array('hzd_notifications/hzd_notifications');
    $rel_type = KONSONS;
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_notifications\Form\ServiceNotificationsUserForm', $user, $rel_type);
    $output[] = array('#markup' => "<div class = 'notifications_title'>". $this->t('Add new notification request') . "</div>");
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_notifications\Form\ServiceSpecificNotificationsUserForm', $user, $rel_type);
    $notifications_priority = db_query("SELECT service_id, type, send_interval FROM {service_notifications_override} WHERE uid = :uid AND rel_type = :rel_type", array(":uid" => $user, ":rel_type" => $rel_type))->fetchAll();
    if(count($notifications_priority) > 0) {
      $output[] = array('#markup' => "<div class = 'service_specific_notifications'><div class = 'notifications_title'>". $this->t('My current notification requests') . "</div>");
      foreach($notifications_priority as $vals) {
        $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_notifications\Form\UpdateServiceSpecificNotifications', $user, $vals->service_id, $vals->type, $vals->send_interval, $rel_type);
      }
      $output[] = array('#markup' => "</div>");
    }
    //$output[] = $this->users_list();
    //$output[] = $this->test1();
    return $output;
  }

  // exeoss notification settings
  public function exeoss_notifications($user = NULL) {
    $rel_type = EXEOSS;
    $output[]['#attached']['library'] = array('hzd_notifications/hzd_notifications');
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_notifications\Form\ServiceNotificationsUserForm', $user, $rel_type);
    $output[] = array('#markup' => "<div class = 'notifications_title'>". $this->t('Add new notification request') . "</div>");
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_notifications\Form\ServiceSpecificNotificationsUserForm', $user, $rel_type);
    $notifications_priority = db_query("SELECT service_id, type, send_interval FROM {service_notifications_override} WHERE uid = :uid AND rel_type = :rel_type", array(":uid" => $user, ":rel_type" => $rel_type))->fetchAll();
    if(count($notifications_priority) > 0) {
      $output[] = array('#markup' => "<div class = 'service_specific_notifications'><div class = 'notifications_title'>". $this->t('My current notification requests') . "</div>");
      foreach($notifications_priority as $vals) {
        $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_notifications\Form\UpdateServiceSpecificNotifications', $user, $vals->service_id, $vals->type, $vals->send_interval, $rel_type);
      }
      $output[] = array('#markup' => "</div>");
    }
    return $output;
  }

  public function notifications($user = NULL) {
    $output[] = array('#markup' => $this->t('My Notifications'));
    return $output;
  }
  
  public function rz_schnellinfos_notifications($user = NULL) {
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_notifications\Form\SchnellinfosNotifications', $user);
    return $output;
  }

  public function group_notifications($user = NULL) {
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_notifications\Form\GroupNotifications', $user);
    return $output;
  }

  public function delete_notifications() {
    $service = \Drupal::request()->get('service');
    $type = \Drupal::request()->get('content_type');
    $interval = \Drupal::request()->get('interval');
    $uid = \Drupal::request()->get('uid');
    $rel_type = \Drupal::request()->get('rel_type');
    $content_types =  array('1' => 'downtimes',  'problem', 'release', 'early_warnings');
    db_delete('service_notifications_override')
      ->condition('service_id', $service)
      ->condition('type', $content_types[$type])
      ->condition('uid', $uid)
      ->condition('send_interval', $interval)
      ->execute();
    error_log($type);
    // get user default interval of a particlural type
    $default_intval = HzdNotificationsHelper::hzd_default_content_type_intval($uid, $content_types[$type], $rel_type);
    
    // remove the default interval of particular service and update the overrided interval
    HzdNotificationsHelper::hzd_update_content_type_intval($service, $default_intval, $uid, $content_types[$type], $interval);
    
    
    $output[] = array(
      '#attached' => array(
        'library' => array(
          'drupalSettings'=> array(
            'data' => 'sucess'
            )
          )
        )
      );
    $output[] = array(
      '#attached' => array(
        'library' => array(
          'drupalSettings' => array(
            'status' =>  TRUE
            )
          )
        )
      );
    //$output[] = array('#markup' => $this->t('Delete Notifications Comes Here'));
    return $output;
  }

  public function notification_templates() {
    $output[] = array('#markup' => $this->t('Notifications Templates'));
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_notifications\Form\NotificationsTemplates');
    return $output;
  }

  // migrate priority table
  /*function test1() {
    $distinct_uids = db_query("select distinct uid from service_notifications_priority order by uid asc")->fetchCol();
    foreach($distinct_uids as $uid) {
      $service_sql = db_query("SELECT value, nf.sid FROM notifications_fields nf, service_notifications_priority snp WHERE snp.sid = nf.sid and  nf.field = 'service' and snp.uid = :uid", array(":uid" => $uid))->fetchAll();
      foreach($service_sql as $vals) {
        $service_type = db_query("SELECT value, intval FROM notifications_fields  WHERE sid= :sid and field= :field" , array(":sid" => $vals->sid, "field" => 'type'))->fetchAll();
        $send_intval = db_query("select send_interval from notifications where sid = :sid", array(":sid" => $vals->sid))->fetchField();
        $rel_type = db_query("select release_type_target_id from node__release_type where entity_id = :eid", array(":eid" => $vals->value))->fetchField();
        foreach($service_type as $service_data) {
          $service_record = array('service_id' => $vals->value, 'type' => $service_data->value, 'send_interval' => $send_intval, 'uid' => $uid, 'rel_type' => $rel_type);
          db_insert('service_notifications_override')->fields($service_record)->execute();
        }
      }
    }
  }*/
  
  function hzd_get_default_interval($uid, $rel_type) {
    $default_intval_per_user = db_query("SELECT service_type, default_send_interval FROM {service_notifications_user_default_interval} 
                               WHERE uid = :uid and rel_type = :type", array(":uid" => $uid, ":type" => $rel_type))->fetchAll();
    $default_interval = array();
    foreach($default_intval_per_user as $val) {
      $default_interval[$val->service_type] = $val->default_send_interval;
    }
    return $default_interval;
  }
  
  
  function hzd_get_all_services($rel_type) {
    $query = db_select('node_field_data', 'n');
    $query->leftJoin('node__field_release_name', 'nfrn', 'n.nid = nfrn.entity_id');
    $query->leftJoin('node__field_problem_name', 'nfpn', 'n.nid = nfpn.entity_id');
    $query->leftJoin('node__field_enable_downtime', 'nfed', 'n.nid = nfed.entity_id');
    $query->leftJoin('node__release_type', 'nrt', 'n.nid = nrt.entity_id');
    $query->condition('n.type', 'services', '=')
          ->condition('nrt.release_type_target_id', $rel_type, '=')
          ->fields('n', array('nid'))
          ->fields('nfrn', array('field_release_name_value'))
          ->fields('nfpn', array('field_problem_name_value'))
          ->fields('nrt', array('release_type_target_id'))
          ->fields('nfed', array('field_enable_downtime_value'));
    $result = $query->execute()->fetchAll();
    return $result;
  }
  
  
  function users_list() {
    
    $service = 22555;
    $type = 'early_warnings';
    $send_interval = '-1';

    
    $uids_query = db_query("SELECT uids FROM {service_notifications} WHERE service_id = :sid AND type = :type AND send_interval = :intval", 
                   array(":sid" => $service, ":type" => $type, ":intval" => $send_interval))->fetchField();
    $uids_list = unserialize($uids_query);
    pr($uids_list);exit;
        /*$this->test(1, 459);
        $this->test(1, 460);*/
     
  }
  
  function test($uid, $rel_type) {
  
    //$rel_type = 459;
    //$uid = 1;
    // get user default intervals
    $default_interval = $this->hzd_get_default_interval($uid, $rel_type);

    // get all services
    $services = $this->hzd_get_all_services($rel_type);

    // user all services interval
    foreach($services as $services_info) {
      if($services_info->field_enable_downtime_value && $services_info->release_type_target_id == KONSONS) {
        if(isset($default_interval['downtimes'])) {
          $user_notifications[$services_info->nid]['downtimes'] =  $default_interval['downtimes'];
        }
      }
      if($services_info->field_problem_name_value && $services_info->release_type_target_id == KONSONS) {
        if(isset($default_interval['problem'])) {
          $user_notifications[$services_info->nid]['problem'] =  $default_interval['problem'];
        }
      }
      if($services_info->field_release_name_value) {
        if(isset($default_interval['release'])) {
          $user_notifications[$services_info->nid]['release'] =  $default_interval['release'];
        }
      }
      if(isset($default_interval['early_warnings'])) {
        $user_notifications[$services_info->nid]['early_warnings'] =  $default_interval['early_warnings'];
      }
    }

    // get priority of user services.
    $get_override_services = db_query("SELECT service_id, send_interval, type FROM {service_notifications_override} 
                             WHERE uid = :uid and rel_type = :rel_type", array(":uid" => $uid, ":rel_type" => $rel_type))->fetchAll();
    if(count($get_override_services) > 0) {
      foreach($get_override_services as $get_override_services_vals) {
        $user_notifications[$get_override_services_vals->service_id][$get_override_services_vals->type] = $get_override_services_vals->send_interval;
      }
    }

    foreach($services as $service_vals) {
      if($service_vals->nid == 474) {
      if($service_vals->field_enable_downtime_value && $service_vals->release_type_target_id == KONSONS) {
        $this->insert_user_service_notifications('downtimes', $service_vals->nid, $uid, $user_notifications);
      }
      /*if($service_vals->field_problem_name_value && $service_vals->release_type_target_id == KONSONS) {
        $this->insert_user_service_notifications('problem', $service_vals->nid, $uid, $user_notifications);
      }
      if($service_vals->field_release_name_value) {
        $this->insert_user_service_notifications('release', $service_vals->nid, $uid, $user_notifications);
      }
      $this->insert_user_service_notifications('early_warnings', $service_vals->nid, $uid, $user_notifications);*/
      }
    }
    
    
    
    
    
    
  }
  
  function insert_user_service_notifications($type, $nid, $uid, $user_notifications) {  
    $interval = array('-1', 0, 86400, 604800);
    foreach($interval as $vals) {
      $uids_list = array();
      $uids_query = db_query("SELECT uids FROM {service_notifications} WHERE service_id = :sid AND type = :type AND send_interval = :intval", 
                   array(":sid" => $nid, ":type" => $type, ":intval" => $vals))->fetchField();
      $uids_list = unserialize($uids_query);
      
      if(($key = array_search($uid, $uids_list)) !== false) {
        unset($uids_list[$key]);
      }
      if(isset($user_notifications[$nid][$type]) && ($user_notifications[$nid][$type] == $vals)) {
        $uids_list[] = $uid;
      }
      
      $serialized_uid = serialize($uids_list);

      print_r($vals);
      print "<pre>";
      pr($serialized_uid);
	    db_update('service_notifications')->fields(array('uids' => $serialized_uid))
	    ->condition('service_id', $nid)
	    ->condition('type', $type)
	    ->condition('send_interval', $vals)
      ->execute();
	    
    }
    exit;
  }
  
}
