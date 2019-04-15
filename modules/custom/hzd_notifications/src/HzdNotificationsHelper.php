<?php

namespace Drupal\hzd_notifications;

use Drupal\Core\Url;

//define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));

class HzdNotificationsHelper {
  
  // default send intervals array
  static function hzd_notification_send_interval() {
    return array(-1 => t('No'),
      0 => t('Yes'),
//      86400 => 'Daily',
//      604800 => 'Weekly'
    );
  }
  
  
  /*
   * service notifications content type
   */
  static function service_notifications_content_type($rel_type = KONSONS) {
    if ($rel_type == KONSONS) {
      return array(1 => t('Incidents and Maintenances'), 2 => t('Problems'), 3 => t('Releases'), 4 => t('Early Warnings'));
    } else {
      return array(3 => t('Releases'), 4 => t('Early Warnings'));
    }
  }
  
  // Update service notifications user default interval
  static function insert_default_user_intervel($type, $int_val, $uid, $rel_type) {
    \Drupal::database()->merge('service_notifications_user_default_interval')
      ->key(array(
        'rel_type' => $rel_type,
        'uid' => $uid,
        'service_type' => $type
      ))
      ->fields(array(
        'default_send_interval' => $int_val,
      ))
      ->execute();
  }
  
  // insert default planning files notifications intervals of user
  static function insert_default_pf_user_intervel($pf_int_val, $uid) {
    \Drupal::database()->merge('planning_files_notifications_default_interval')
      ->key(array(
        'uid' => $uid,
        'planning_file_type' => 'planning_files'
      ))
      ->fields(array(
        'default_send_interval' => $pf_int_val,
      ))
      ->execute();
  }
  
  // get planning files default interval
  static function get_default_pf_timeintervals($uid) {
    $query = \Drupal::database()->select('planning_files_notifications_default_interval', 'pf');
    $query->condition('pf.uid', $uid, '=')
      ->fields('pf', array('default_send_interval'));
    $result = $query->execute()->fetchField();
    return $result;
  }
  
  // update user planning files notifications
  static function hzd_modify_pf_notifications($uid, $pf_int_val, $default_pf_interval) {
    // get users list of previous default interval of user
    $uids = self::hzd_get_user_pf_interval($default_pf_interval);
    if (is_array($uids) && (count($uids) > 0)) {
      if (in_array($uid, $uids)) {
        $diff = array_diff($uids, array($uid));
        $serialized_uid = serialize($diff);
        //update user planning files notifications
        self::hzd_update_pf_notifications($default_pf_interval, $serialized_uid);
      }
    }
    
    // get users list of user submitted default interval of user
    $default_uids = self::hzd_get_user_pf_interval($pf_int_val);
    if (is_array($default_uids) && (count($default_uids) > 0)) {
      if (!in_array($uid, $default_uids)) {
        $default_uids[] = $uid;
        $serialized_users = serialize($default_uids);
        //update user planning files notifications
        self::hzd_update_pf_notifications($pf_int_val, $serialized_users);
      }
    }
  }
  
  // get default interval of user
  static function _get_default_timeintervals($uid, $rel_type) {
    return \Drupal::database()->select('service_notifications_user_default_interval', 'snudi')
      ->fields('snudi', ['service_type', 'default_send_interval'])
      ->condition('rel_type', $rel_type)
      ->condition('uid', $uid)
      ->execute()
      ->fetchAllKeyed(0,1);
  }
  
  // get list of all services of a release type
  static function _services_list($rel_type = KONSONS) {
    $services_query = \Drupal::database()->query("SELECT n.nid, n.title FROM node_field_data n, node__release_type nrt WHERE n.nid = nrt.entity_id and nrt.release_type_target_id = :tid AND n.type = :type ORDER BY n.title ASC", array(":tid" => $rel_type, ":type" => 'services'))->fetchAll();
    $services = array();
    foreach ($services_query as $services_info) {
      $services[$services_info->nid] = $services_info->title;
    }
    natcasesort($services);
    return $services;
  }
  
  /*
   * Return content types for the service related
   */
  static function _get_content_types($service, $default = NULL, $rel_type = KONSONS) {
    if (!$default) {
      $content_types = array('0' => t('Content Type'));
    }
    
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->leftJoin('node__field_release_name', 'nfrn', 'n.nid = nfrn.entity_id');
    $query->leftJoin('node__field_problem_name', 'nfpn', 'n.nid = nfpn.entity_id');
    $query->leftJoin('node__field_enable_downtime', 'nfed', 'n.nid = nfed.entity_id');
    $query->condition('n.nid', $service, '=')
      ->condition('n.type', 'services', '=')
      ->fields('nfrn', array('field_release_name_value'))
      ->fields('nfpn', array('field_problem_name_value'))
      ->fields('nfed', array('field_enable_downtime_value'));
    $result = $query->execute()->fetchAll();
    
    foreach ($result as $services_info) {
      if ($services_info->field_enable_downtime_value) {
        if ($rel_type == KONSONS)
          $content_types[1] = t('Current Incidents and Planned Maintenances');
      }
      if ($services_info->field_problem_name_value) {
        if ($rel_type == KONSONS)
          $content_types[2] = t('Problems');
      }
      if ($services_info->field_release_name_value) {
        $content_types[3] = t('Releases');
      }
    }
    if ($service) {
      $content_types[4] = t('Early Warnings');
    }
    return $content_types;
  }
  
  /*
   * Returns the default time intervals
   */
  static function get_default_quickinfo_timeintervals($uid) {
    $time_interval = \Drupal::database()->query("SELECT default_send_interval as send_interval, affected_service as value 
             FROM {quickinfo_notifications_user_default_interval} WHERE uid = :uid", array(":uid" => $uid))->fetchAllKeyed(1, 0);
    return $time_interval;
  }
  
  /*
   * Inserting user default intervel
   */
  static function insert_default_quickinfo_user_intervel($type, $intervel, $uid) {
    $quickinfo_record = array('uid' => $uid, 'affected_service' => $type, 'default_send_interval' => $intervel);
    db_insert('quickinfo_notifications_user_default_interval')->fields($quickinfo_record)->execute();
  }

  /*
 * Returns the default time intervals
 */
  static function get_default_arbeitsanleitung_timeintervals($uid) {
    $time_interval = \Drupal::database()->query("SELECT default_send_interval as send_interval , uid
             FROM {arbeitsanleitung_notifications__user_default_interval} WHERE uid = :uid", array(":uid" => $uid))->fetchAllKeyed(1, 0);
    return $time_interval;
  }

  /*
   * Inserting user default intervel
   */
  static function insert_default_arbeitsanleitung_user_intervel($uid, $intervel) {
    $arbeitsanleitung_record = array('uid' => $uid, 'default_send_interval' => $intervel);
    return db_insert('arbeitsanleitung_notifications__user_default_interval')->fields($arbeitsanleitung_record)->execute();
  }
  
  // get default interval of a particular content type
  static function hzd_default_content_type_intval($uid, $type, $rel_type) {
    $intval = \Drupal::database()->query("SELECT default_send_interval FROM {service_notifications_user_default_interval} 
              WHERE uid = :uid AND service_type = :type AND rel_type = :rel_type",
      array(":uid" => $uid, ":type" => $type, ":rel_type" => $rel_type))->fetchField();
    return $intval;
  }
  
  // get content types list of release type
  static function hzd_get_content_type_name($rel_type = KONSONS) {
    if ($rel_type == KONSONS) {
      $types = array(1 => 'downtimes', 2 => 'problem', 3 => 'release', 4 => 'early_warnings');
    } else {
      $types = array(3 => 'release', 4 => 'early_warnings');
    }
    return $types;
  }
  
  // update the overrided content type interval
  static function hzd_update_content_type_intval($service, $send_interval, $uid, $type, $default_intval) {
    if(empty($type)){
      return;
    }
    
//pr($service . " " . $send_interval . " " . $uid . " " . $type . " " . $default_intval);
//exit;

    $data = \Drupal::database()->select('service_notifications', 'sn')
      ->fields('sn')
      ->condition('service_id', $service)
      ->condition('type', $type)
      ->execute()->fetchAllAssoc('send_interval');
    $uids = null;
    //pr($data);exit;
    $intervals = self::hzd_notification_send_interval();
    foreach ($intervals as $interval=>$val) {
      if (isset($data[$interval])) {
        $uids = unserialize($data[$interval]->uids);
        //pr($data[$interval]);exit;
        if (($key = array_search($uid, $uids)) !== false) {
          unset($uids[$key]);
        }
        if ($send_interval == $interval) {
          $uids[] = $uid;
        }
        \Drupal::database()
          ->update('service_notifications')
          ->fields(['uids' => serialize($uids)])
          ->condition('sid', $data[$interval]->sid)->execute();
      } else {
        $notifyData = ['uids' => serialize([$uid]), 'send_interval' => $interval, 'type' => $type, 'service_id' => $service];
        \Drupal::database()
          ->insert('service_notifications')
          ->fields($notifyData)->execute();
      }
    }
    
    /*
        // get uids list of default user interval of service
        $uids_list = self::hzd_get_user_service_interval($service, $type, $default_intval);
        if(($key = array_search($uid, (array)$uids_list)) !== false) {
          unset($uids_list[$key]);
        }
        $serialized_uid = serialize($uids_list);
        // update old default send interval of service id
        self::hzd_update_users_service_notifications($service, $type, $default_intval, $serialized_uid);
    
        // update new default send interval of service id
        $new_uids_list = self::hzd_get_user_service_interval($service, $type, $send_interval);
        if(($key = array_search($uid, $new_uids_list)) !== false) {
          
        }
        else {
          $new_uids_list[] = $uid;
          $new_serialized_uid = serialize($new_uids_list);
    
          // update old default send interval of service id
          self::hzd_update_users_service_notifications($service, $type, $send_interval, $new_serialized_uid);
        }
        */
  }
  
  // get uids list of default user interval of service
  static function hzd_get_user_service_interval($service, $type, $default_intval) {
    $uids_query = \Drupal::database()->select('service_notifications', 'sn')
      ->fields('sn', ['uids'])
      ->condition('service_id', $service)
      ->condition('type', $type)
      ->condition('send_interval', $default_intval)
      ->execute()->fetchField();
    $uids_list = [];
    //pr($uids_query->__toString());exit;
    if (!empty($uids_query))
      $uids_list = unserialize($uids_query);
    return $uids_list;
  }
  
  // update user service notifications
  static function hzd_update_users_service_notifications($service, $type, $default_intval, $serialized_uid) {
    \Drupal::database()->update('service_notifications')->fields(array('uids' => $serialized_uid))
      ->condition('service_id', $service)
      ->condition('type', $type)
      ->condition('send_interval', $default_intval)
      ->execute();
  }
  
  // update user quickinfo notifications
  static function hzd_modify_quickinfo_notifications($content, $intval, $default_intval, $uid) {
    // get users list of previous default interval of user
    $uids = self::hzd_get_user_quickinfo_interval($content, $intval);
    if (is_array($uids) && (count($uids) > 0)) {
      if (in_array($uid, $uids)) {
        $diff = array_diff($uids, array($uid));
        $serialized_uid = serialize($diff);
        //update user quickinfo notifications
        self::hzd_update_quickinfo_notifications($content, $intval, $serialized_uid);
      }
    }
    
    // get users list of user submitted default interval of user
    $default_uids = self::hzd_get_user_quickinfo_interval($content, $default_intval);
    if (is_array($default_uids) && (count($default_uids) > 0)) {
      if (!in_array($uid, $default_uids)) {
        $default_uids[] = $uid;
        $serialized_users = serialize($default_uids);
        //update user quickinfo notifications
        self::hzd_update_quickinfo_notifications($content, $default_intval, $serialized_users);
      }
    }
  }
  
  // get users list from quickinfo notifications table
  static function hzd_get_user_quickinfo_interval($content, $intval) {
    $serialized_uids_query = \Drupal::database()->query("SELECT uids FROM {quickinfo_notifications} WHERE cck = :cck AND send_interval = :intval",
      array(":cck" => $content, ":intval" => $intval))->fetchField();
    $uids = unserialize($serialized_uids_query);
    return $uids;
  }
  
  // update quickinfo notifications table
  static function hzd_update_quickinfo_notifications($content, $intval, $serialized_uid) {
    \Drupal::database()->update('quickinfo_notifications')->fields(array('uids' => $serialized_uid))
      ->condition('cck', $content)
      ->condition('send_interval', $intval)
      ->execute();
  }
  
  // get users list from pf notifications table
  static function hzd_get_user_pf_interval($intval) {
    $serialized_uids_query = \Drupal::database()->query("SELECT uids FROM {planning_files_notifications} WHERE send_interval = :intval",
      array(":intval" => $intval))->fetchField();
    $uids = unserialize($serialized_uids_query);
    return $uids;
  }
  
  // update pf notifications table
  static function hzd_update_pf_notifications($intval, $serialized_uid) {
    \Drupal::database()->update('planning_files_notifications')->fields(array('uids' => $serialized_uid))
      ->condition('send_interval', $intval)
      ->execute();
  }
  
  //get default interval of each services and type
  static function hzd_get_user_notifications($uid, $rel_type, $services, $default_interval) {
    $user_notifications = array();
    foreach ($services as $services_info) {
      if ($services_info->field_enable_downtime_value && $services_info->release_type_target_id == KONSONS) {
        if (isset($default_interval['downtimes'])) {
          $user_notifications[$services_info->nid]['downtimes'] = $default_interval['downtimes'];
        }
      }
      if ($services_info->field_problem_name_value && $services_info->release_type_target_id == KONSONS) {
        if (isset($default_interval['problem'])) {
          $user_notifications[$services_info->nid]['problem'] = $default_interval['problem'];
        }
      }
      if ($services_info->field_release_name_value) {
        if (isset($default_interval['release'])) {
          $user_notifications[$services_info->nid]['release'] = $default_interval['release'];
        }
      }
      if (isset($default_interval['early_warnings'])) {
        $user_notifications[$services_info->nid]['early_warnings'] = $default_interval['early_warnings'];
      }
    }
    // get priority of user services.
    $get_override_services = db_query("SELECT service_id, send_interval, type FROM {service_notifications_override} 
                             WHERE uid = :uid and rel_type = :rel_type", array(":uid" => $uid, ":rel_type" => $rel_type))->fetchAll();
    if (count($get_override_services) > 0) {
      foreach ($get_override_services as $get_override_services_vals) {
        unset($user_notifications[$get_override_services_vals->service_id][$get_override_services_vals->type]);
      }
    }
    return $user_notifications;
  }
  
  static function hzd_modify_services($user_notifications, $type, $uid, $int_val, $send_interval) {
    //pr($user_notifications);exit;
    foreach ($user_notifications as $key => $vals) {
      if (isset($vals[$type])) {
        // get users list of previous default interval of user
        $uids = self::hzd_get_user_service_interval($key, $type, $int_val);
        if (is_array($uids) && (count($uids) > 0)) {
          if (in_array($uid, $uids)) {
            $diff = array_diff($uids, array($uid));
            $serialized_uid = serialize($diff);
            //update user service notifications
            self::hzd_update_service_notifications($serialized_uid, $key, $type, $int_val);
          }
        }
        
        // get users list of user submitted default interval of user
        $default_uids = self::hzd_get_user_service_interval($key, $type, $send_interval);
        if (is_array($default_uids) && (count($default_uids) > 0)) {
          if (!in_array($uid, $default_uids)) {
            $default_uids[] = $uid;
            $serialized_users = serialize($default_uids);
            //update user quickinfo notifications
            self::hzd_update_service_notifications($serialized_users, $key, $type, $send_interval);
          }
        } else {
          if ($default_uids == '') {
            $default_uids = array();
            $default_uids[] = $uid;
            $serialized_users = serialize($default_uids);
            //update user quickinfo notifications
            self::hzd_update_service_notifications($serialized_users, $key, $type, $send_interval);
          }
        }
      }
    }
  }
  
  // update service notifications
  static function hzd_update_service_notifications($serialized_uid, $sid, $type, $int_val) {
    \Drupal::database()->update('service_notifications')->fields(array('uids' => $serialized_uid))
      ->condition('service_id', $sid)
      ->condition('type', $type)
      ->condition('send_interval', $int_val)
      ->execute();
  }
  
  static function hzd_user_group_default_interval($uid) {
    $default_intval = \Drupal::database()->query("SELECT group_id, default_send_interval FROM {group_notifications_user_default_interval} WHERE uid = :uid", array(":uid" => $uid))->fetchAll();
    $interval = array();
    foreach ($default_intval as $val) {
      $interval[$val->group_id] = $val->default_send_interval;
    }
    return $interval;
  }
  
  static function hzd_user_groups_list($uid) {
    $user_groups_query = \Drupal::database()->query("SELECT gfd.id, gfd.label FROM {groups_field_data} gfd, {group_content_field_data} gcfd 
                   WHERE gcfd.request_status = 1 AND gfd.id = gcfd.gid AND gfd.status = 1 AND gcfd.entity_id = :eid ORDER BY gfd.label", array(":eid" => $uid))->fetchAll();
    $user_groups = array();
    foreach ($user_groups_query as $groups_list) {
      $user_groups[$groups_list->id] = $groups_list->label;
    }
    return $user_groups;
  }
  
  static function insert_default_group_user_intervel($gid, $label, $int_val, $uid) {
    $group_record = array('uid' => $uid, 'group_id' => $gid, 'group_name' => $label, 'default_send_interval' => $int_val);
    \Drupal::database()->insert('group_notifications_user_default_interval')->fields($group_record)->execute();
  }
}
