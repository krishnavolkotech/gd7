<?php

namespace Drupal\hzd_notifications;

use Drupal\Core\Url;
define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));

class HzdNotificationsHelper {

  // default send intervals array
  function hzd_notification_send_interval() {
    return array(-1 => 'Never', 0 => 'Immediately', 86400 => 'Daily', 604800 => 'Weekly');
  }

  /*
   * service notifications content type
   */
  function service_notifications_content_type($rel_type = KONSONS) {
    if($rel_type == KONSONS) {
      return array('1' => 'Current Incidents and Planned Maintenances', 2 => 'Problems', 3 => 'Releases', 4 => 'Early Warnings');
    }
    else {
      return array('1' => 'Releases', 'Early Warnings');
    }
  }

  // Update service notifications user default interval
  function insert_default_user_intervel($type, $int_val, $uid, $rel_type) {
    db_merge('service_notifications_user_default_interval')
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

  /*function insert_default_pf_user_intervel($pf_int_val, $uid) {
    
  }*/

  // get default interval of user
  function _get_default_timeintervals($uid, $rel_type) {
    $default_vals = db_query("SELECT service_type, default_send_interval FROM {service_notifications_user_default_interval} WHERE uid = :uid AND rel_type = :rel_type", array(':uid' => $uid, ':rel_type' => $rel_type))->fetchAll();
    foreach($default_vals as $val) {
      $time_interval[$val->service_type] = $val->default_send_interval; 
    }
    return $time_interval;
  }

  // get list of all services of a release type
  function _services_list($rel_type = KONSONS){
    $services_query = db_query("SELECT n.nid, n.title FROM node_field_data n, node__release_type nrt WHERE n.nid = nrt.entity_id and nrt.release_type_target_id = :tid AND n.type = :type ORDER BY n.title ASC", array(":tid" => $rel_type, ":type" => 'services'))->fetchAll();
    $services = array();
    foreach($services_query as $services_info) {
      $services[$services_info->nid] = $services_info->title;
    }
    return $services;
  }

  /*
   * Return content types for the service related
   */
  function _get_content_types($service, $default = NULL, $rel_type = KONSONS) {
    if (!$default) {
      $content_types = array('0' => t('Content Type'));
    }

    $query = db_select('node_field_data', 'n');
    $query->leftJoin('node__field_release_name', 'nfrn', 'n.nid = nfrn.entity_id');
    $query->leftJoin('node__field_problem_name', 'nfpn', 'n.nid = nfpn.entity_id');
    $query->leftJoin('node__field_enable_downtime', 'nfed', 'n.nid = nfed.entity_id');
    $query->condition('n.nid', $service, '=')
          ->condition('n.type', 'services', '=')
          ->fields('nfrn', array('field_release_name_value'))
          ->fields('nfpn', array('field_problem_name_value'))
          ->fields('nfed', array('field_enable_downtime_value'));
    $result = $query->execute()->fetchAll();

    foreach($result as $services_info) {
      if($services_info->field_enable_downtime_value) {
        if ($rel_type == KONSONS)
	        $content_types[1] =  t('Current Incidents and Planned Maintenances');
      }
      if($services_info->field_problem_name_value) {
        if ($rel_type == KONSONS)
	        $content_types[2] =  t('Problems');
      }
      if($services_info->field_release_name_value) {
        $content_types[3] =  t('Releases');
      }
    }
    if($service) {
      $content_types[4] = t('Early Warnings');
    }
    return $content_types;
  }

  /*
   * Returns the default time intervals
   */
  function get_default_quickinfo_timeintervals($uid) {
    $query = db_query("SELECT default_send_interval as send_interval, affected_service as value 
             FROM {quickinfo_notifications_user_default_interval} WHERE uid = :uid", array(":uid" => $uid))->fetchAll();
    foreach($query as $default_values) {
      $time_interval[$default_values->value] = $default_values->send_interval; 
    }
    return $time_interval;
  }
  
  /*
   * Inserting user default intervel
   */
  function insert_default_quickinfo_user_intervel($type, $intervel, $uid) {
    $quickinfo_record = array('uid' => $uid, 'affected_service' => $type, 'default_send_interval' => $intervel);
    db_insert('quickinfo_notifications_user_default_interval')->fields($quickinfo_record)->execute();
  }

  // get default interval of a particular content type
  function hzd_default_content_type_intval($uid, $type, $rel_type) {
    $intval = db_query("SELECT default_send_interval FROM {service_notifications_user_default_interval} 
              WHERE uid = :uid AND service_type = :type AND rel_type = :rel_type", 
              array(":uid" => $uid, ":type" => $type, ":rel_type" => $rel_type))->fetchField();
    return $intval;
  }

  // get content types list of release type
  function hzd_get_content_type_name($rel_type = KONSONS) {
    if($rel_type == KONSONS) {
      $types = array(1 => 'downtimes', 'problem', 'release', 'early_warnings');
    }
    else {
      $types = array(1 => 'release', 'early_warnings');
    }
    return $types;
  }

  // update the overrided content type interval
  function hzd_update_content_type_intval($service, $send_interval, $uid, $type, $default_intval) {
    // get uids list of default user interval of service
    $uids_list = self::hzd_get_user_service_interval($service, $type, $default_intval);

    if(($key = array_search($uid, $uids_list)) !== false) {
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
  }

  // get uids list of default user interval of service
  function hzd_get_user_service_interval($service, $type, $default_intval) {
    $uids_query = db_query("SELECT uids FROM {service_notifications} WHERE service_id = :sid AND type = :type AND send_interval = :intval", 
                   array(":sid" => $service, ":type" => $type, ":intval" => $default_intval))->fetchField();
    $uids_list = unserialize($uids_query);
    return $uids_list;
  }

  // update user service notifications
  function hzd_update_users_service_notifications($service, $type, $default_intval, $serialized_uid) {
    db_update('service_notifications')->fields(array('uids' => $serialized_uid))
	    ->condition('service_id', $service)
	    ->condition('type', $type)
	    ->condition('send_interval', $default_intval)
      ->execute();
  }
  
  // update user quickinfo notifications
  function hzd_modify_quickinfo_notifications($content, $intval, $default_intval, $uid) {
    // get users list of previous default interval of user
    $uids = self::hzd_get_user_quickinfo_interval($content, $intval);
    if(is_array($uids) && (count($uids) > 0)) {
      if(in_array($uid, $uids)) {
        $diff = array_diff($uids, array($uid));
        $serialized_uid = serialize($diff);
        //update user quickinfo notifications
        self::hzd_update_quickinfo_notifications($content, $intval, $serialized_uid);
      }
    }
    
    // get users list of user submitted default interval of user
    $default_uids = self::hzd_get_user_quickinfo_interval($content, $default_intval);
    if(is_array($default_uids) && (count($default_uids) > 0)) {
      if(!in_array($uid, $default_uids)) {
        $default_uids[] = $uid;
        $serialized_users = serialize($default_uids);
        //update user quickinfo notifications
        self::hzd_update_quickinfo_notifications($content, $default_intval, $serialized_users);
      }
    }
  }

  // get users list from quickinfo notifications table
  function hzd_get_user_quickinfo_interval($content, $intval) {
    $serialized_uids_query = db_query("SELECT uids FROM {quickinfo_notifications} WHERE cck = :cck AND send_interval = :intval",
                             array(":cck" => $content, ":intval" => $intval))->fetchField();
    $uids = unserialize($serialized_uids_query);
    return $uids;
  }

  // update quickinfo notifications table
  function hzd_update_quickinfo_notifications($content, $intval, $serialized_uid) {
    db_update('quickinfo_notifications')->fields(array('uids' => $serialized_uid))
     ->condition('cck', $content)
     ->condition('send_interval', $intval)
     ->execute();
  }
}
