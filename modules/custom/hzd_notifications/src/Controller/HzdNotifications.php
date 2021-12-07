<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Controller\HzdNotifications
 */

namespace Drupal\hzd_notifications\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\hzd_notifications\HzdNotificationsHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;

//if(!defined('KONSONS'))
//  define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
if (!defined('EXEOSS')) {
  define('EXEOSS', \Drupal::config('hzd_release_management.settings')
    ->get('ex_eoss_service_term_id'));
}

/**
 * Class HzdNotifications
 * @package Drupal\hzd_notifications\Controller
 */
class HzdNotifications extends ControllerBase {
  
  protected $routeMatch;
  protected $db;
  var $isNewUser = FALSE;
  
  public function __construct($routeMatch, $db) {
    $this->routeMatch = $routeMatch;
    $this->db = $db;
  }
  
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'), $container->get('database'));
  }
  
  // konsons notification settings
  public function service_notifications($user = NULL) {
    $output[]['#attached']['library'] = array('hzd_notifications/hzd_notifications');
    $rel_type = KONSONS;
    $output[] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_notifications\Form\ServiceNotificationsUserForm', $user, $rel_type);
    $output[] = array('#markup' => "<div class = 'notifications_title'>" . $this->t('Add new notification request') . "</div>");
    $output[] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_notifications\Form\ServiceSpecificNotificationsUserForm', $user, $rel_type);
    $notifications_priority = \Drupal::database()->query("SELECT sid, service_id, type, send_interval FROM {service_notifications_override} WHERE uid = :uid AND rel_type = :rel_type", array(
      ":uid" => $user,
      ":rel_type" => $rel_type
    ))->fetchAll();
    if (count($notifications_priority) > 0) {
      $output[] = array('#markup' => "<div class = 'service_specific_notifications'><div class = 'notifications_title'>" . $this->t('My current notification requests') . "</div>");
      foreach ($notifications_priority as $vals) {
        $output[] = \Drupal::formBuilder()
          ->getForm('Drupal\hzd_notifications\Form\UpdateServiceSpecificNotifications', $user, $vals->service_id, $vals->type, $vals->send_interval, $rel_type, $vals->sid);
      }
      $output[] = array('#markup' => "</div>");
    }
    return $output;
  }
  
  // exeoss notification settings
  public function exeoss_notifications($user = NULL) {
    $rel_type = EXEOSS;
    $output[]['#attached']['library'] = array('hzd_notifications/hzd_notifications');
    $output[] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_notifications\Form\ServiceNotificationsUserForm', $user, $rel_type);
    $output[] = array('#markup' => "<div class = 'notifications_title'>" . $this->t('Add new notification request') . "</div>");
    $output[] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_notifications\Form\ServiceSpecificNotificationsUserForm', $user, $rel_type);
    $notifications_priority = \Drupal::database()->query("SELECT sid, service_id, type, send_interval FROM {service_notifications_override} WHERE uid = :uid AND rel_type = :rel_type", array(
      ":uid" => $user,
      ":rel_type" => $rel_type
    ))->fetchAll();
    if (count($notifications_priority) > 0) {
      $output[] = array('#markup' => "<div class = 'service_specific_notifications'><div class = 'notifications_title'>" . $this->t('My current notification requests') . "</div>");

      foreach ($notifications_priority as $vals) {
          $output[] = \Drupal::formBuilder()
              ->getForm('Drupal\hzd_notifications\Form\UpdateServiceSpecificNotifications', $user, $vals->service_id, $vals->type, $vals->send_interval, $rel_type, $vals->sid);
      }
      $output[] = array('#markup' => "</div>");
    }
    return $output;
  }
  
  public function notifications($user = NULL) {
    //$output[] = array('#markup' => $this->t('My Notifications'));
    $items = [
      $this->t('Sie haben Benachrichtigungen abonniert.'),
      $this->t('Ihre Benachrichtigungen werden versandt via HTML Mail.'),
      //$this->t('Ihr Standardintervall zum Versenden von Benachrichtigungen ist Sofort.'),
    ];
    $markup[] = [
      '#title' => $this->t('Aktuelle Einstellungen:'),
      '#items' => $items,
      '#theme' => 'item_list',
      '#type' => 'ul',
    ];
    $user = $this->routeMatch->getParameter('user');
    $userEntity = User::load($user);
    $optionLinks = [];
    $currentNotificationStatus = $userEntity->get('field_notifications_status')->value;
    $hassActiveNotifySetting = $currentNotificationStatus == 'Enable' ? 1 : 0;
//    pr($hassActiveNotifySetting);exit;
    $notificationsCount = $this->getAllSubscribedNotificationsCount($user);
    $optionLinks[] = [
      '#title' => $hassActiveNotifySetting ? $this->t('Alle Benachrichtigungen temporär deaktivieren, z.B. während Ihres Urlaubs') : $this->t('(Re-)Aktivieren Sie Ihre Benachrichtigungen'),
      '#type' => 'link',
      '#url' => Url::fromRoute('hzd_notifications.update_subscriptions', [
        'user' => $user,
        'status' => $hassActiveNotifySetting ? 'disable' : 'enable'
      ])
    ];
    if ($notificationsCount) {
      $optionLinks[] = [
        '#title' => $this->t('Alle Benachrichtigungen dauerhaft cancel'),
        '#type' => 'link',
        '#url' => Url::fromRoute('hzd_notifications.cancel_all_subscriptions', ['user' => $user], ['attributes' => ['onclick' => 'if(!confirm("Sind Sie sicher, alle Benachrichtigungen zu annullieren?")){return false;}']])
      ];
    }
    
    $markup[] = [
      '#title' => $this->t('Optionen:'),
      '#items' => $optionLinks,
      '#theme' => 'item_list',
      '#type' => 'ul',
    ];
    return $markup;
  }
  
  
  public function notificationsUpdateForUser($user, $status = 'enable') {
    ///temporarily disable/enable notifications by setting status field to false/true.
    $data = ['enable' => 'Enable', 'disable' => 'Disable'];
    $user->set('field_notifications_status', $data[$status])->save();
    $url = Url::fromRoute('hzd_notifications.notifications', ['user' => $user->id()])
      ->toString();
    return \Symfony\Component\HttpFoundation\RedirectResponse::create($url);
  }
  
  protected function getAllSubscribedNotificationsCount($user) {
    $qINotificationsCount = $this->db->select('quickinfo_notifications_user_default_interval', 'qiudi')
      ->fields('qiudi', ['affected_service'])
      ->condition('uid', $user)
      ->condition('default_send_interval', -1, '<>')
      ->execute()
      ->fetchAll();
    $pFNotificationsCount = $this->db->select('planning_files_notifications_default_interval', 'pfudi')
      ->fields('pfudi', ['id'])
      ->condition('uid', $user)
      ->condition('default_send_interval', -1, '<>')
      ->execute()
      ->fetchAll();
    $groupNotificationsCount = $this->db->select('group_notifications_user_default_interval', 'gnudi')
      ->fields('gnudi', ['group_id'])
      ->condition('uid', $user)
      ->condition('default_send_interval', -1, '<>')
      ->execute()
      ->fetchAll();
    $serviceNotificationsCount = $this->db->select('service_notifications_user_default_interval', 'sno')
      ->fields('sno', ['id'])
      ->condition('uid', $user)
      ->condition('default_send_interval', -1, '<>')
      ->execute()
      ->fetchCol();
    $serviceNotificationsOverridesCount = $this->db->select('service_notifications_override', 'sno')
      ->fields('sno', ['service_id'])
      ->condition('uid', $user)
      ->condition('send_interval', -1, '<>')
      ->execute()
      ->fetchAll();
    return count($qINotificationsCount) + count($pFNotificationsCount) + count($groupNotificationsCount) + count($serviceNotificationsCount) + count($serviceNotificationsOverridesCount);
    
    
  }
  
  public function cancelUserNotifications($user) {
    
    $this->getAllSubscribedNotificationsCount($user);
//        exit;
    $this->setAllDefaultNotifications($user, -1);

    // Remove Subscription from AL-EDV
    \Drupal::database()->query("DELETE FROM {arbeitsanleitung_notifications__user_default_interval} where uid = :uid", array(":uid" => $user));

    \Drupal::messenger()->addMessage($this->t('Preferrences Saved Successfully'));
    $url = Url::fromRoute('hzd_notifications.notifications', ['user' => $user])
      ->toString();
    return \Symfony\Component\HttpFoundation\RedirectResponse::create($url);
  }
  
  
  public function setAllDefaultNotifications($user, $interval) {
    $this->setDefaultForQuickInfoNotifications($user, $interval);
    $this->setDefaultForPlanningFilesNotifications($user, $interval);
//    pr($interval);exit;
    $this->setDefaultForGroupNotifications($user, $interval);
    $this->setDefaultForServiceNotifications($user, $interval);
  }
  
  /**
   * Set default service type defaults to a new user
   *
   * @param $type
   * @param $user
   * @param $intervalValue
   */
  public function setDefaultForServiceTypeNotifications($type, $user, $intervalValue) {
    if (in_array($type, ['downtimes', 'problem'])) {
      $rel_types = [KONSONS];
    }
    else {
      $rel_types = [KONSONS, EXEOSS];
    }
    $serviceNotificationsInterval = $this->db->select('service_notifications_user_default_interval', 'sno')
      ->fields('sno', ['id'])
      ->condition('uid', $user)
      ->condition('service_type', $type)
      ->execute()
      ->fetchCol();
    if (empty($serviceNotificationsInterval)) {
      foreach ($rel_types as $releaseTypeId) {
        $this->db->insert('service_notifications_user_default_interval')
          ->fields([
            'uid' => $user,
            'service_type' => $type,
            'default_send_interval' => $intervalValue,
            'rel_type' => $releaseTypeId
          ])
          ->execute();
      }
    }
    if (!empty($serviceNotificationsInterval) || !empty($serviceNotificationsOverrides)) {
      $this->db->update('service_notifications_user_default_interval')
        ->fields(['default_send_interval' => $intervalValue])
        ->condition('id', $serviceNotificationsInterval, 'IN')
        ->execute();
    }
    $serviceNotifications = $this->db->select('service_notifications', 'sn')
      ->condition('type', $type)
      ->fields('sn')
//            ->condition('service_id', $serviceNotificationsInterval, 'IN')
      ->execute()
      ->fetchAll();
//    pr($serviceNotifications);exit;
    foreach ($serviceNotifications as $id) {
      $users = array_values((array) unserialize($id->uids));
      $key = array_search($user, (array) $users);
      $update = FALSE;
      foreach ($users as $userKey => $item) {
        if ($item == $user) {
          unset($users[$userKey]);
          $update = TRUE;
        }
      }
      if ($id->send_interval == $intervalValue) {
        $users[] = $user;
        $update = TRUE;
      }
      if ($update == TRUE) {
        $this->db->update('service_notifications')
          ->fields(['uids' => serialize($users)])
          ->condition('sid', $id->sid)
          ->execute();
      }
    }
  }
  
  public function setDefaultForQuickInfoNotifications($user, $intervalValue) {
    $qINotificationsInterval = $this->db->select('quickinfo_notifications_user_default_interval', 'qiudi')
      ->fields('qiudi', ['affected_service'])
      ->condition('uid', $user)
      ->condition('default_send_interval', $intervalValue, '<>')
      ->execute()
      ->fetchCol();
    if (!empty($qINotificationsInterval)) {
      $this->db->update('quickinfo_notifications_user_default_interval')
        ->fields(['default_send_interval' => $intervalValue])
        ->condition('uid', $user)
        ->execute();
    }
    else {
      $options = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_other_services')
        ->getSetting('allowed_values');
      foreach ($options as $option) {
        $this->db->insert('quickinfo_notifications_user_default_interval')
          ->fields([
            'affected_service' => $option,
            'uid' => $user,
            'default_send_interval' => $intervalValue
          ])
          ->execute();
      }
    }
    $qINotifications = $this->db->select('quickinfo_notifications', 'qin')
      ->fields('qin')
//                ->condition('cck', $qINotificationsInterval, 'IN')
      ->execute()
      ->fetchAll();
    if (!empty($qINotifications)) {
      foreach ((array) $qINotifications as $id) {
        $users = array_values(unserialize($id->uids));
        $key = array_search($user, $users);
        $update = FALSE;
        foreach ($users as $userKey => $item) {
          if ($item == $user) {
            unset($users[$userKey]);
            $update = TRUE;
          }
        }
        if ($id->send_interval == $intervalValue) {
          $users[] = $user;
          $update = TRUE;
        }
        if ($update == TRUE) {
          $this->db->update('quickinfo_notifications')
            ->fields(['uids' => serialize($users)])
            ->condition('id', $id->id)
            ->execute();
        }
      }
    }
    
  }
  
  public function setDefaultForPlanningFilesNotifications($user, $intervalValue) {
    $pFNotificationsInterval = $this->db->select('planning_files_notifications_default_interval', 'pfudi')
      ->fields('pfudi', ['id'])
      ->condition('uid', $user)
      ->condition('default_send_interval', $intervalValue, '<>')
      ->execute()
      ->fetchCol();
    if (!empty($pFNotificationsInterval)) {
      $this->db->update('planning_files_notifications_default_interval')
        ->fields(['default_send_interval' => $intervalValue])
        ->condition('uid', $user)
        ->execute();
      $pFNotifications = $this->db->select('planning_files_notifications', 'pfn')
        ->fields('pfn')
//                ->condition('id', $pFNotificationsInterval, 'IN')
        ->execute()
        ->fetchAll();
      if (!empty($pFNotifications)) {
        foreach ((array) $pFNotifications as $id) {
          $users = array_values(unserialize($id->uids));
          $key = array_search($user, $users);
          $update = FALSE;
          foreach ($users as $userKey => $item) {
            if ($item == $user) {
              unset($users[$userKey]);
              $update = TRUE;
            }
          }
          if ($id->send_interval == $intervalValue) {
            $users[] = $user;
            $update = TRUE;
          }
          if ($update == TRUE) {
            $this->db->update('planning_files_notifications')
              ->fields(['uids' => serialize($users)])
              ->condition('id', $id->id)
              ->execute();
          }
        }
      }
    }
  }
  
  public function setDefaultForGroupNotifications($user, $intervalValue) {
    $groupNotificationsInterval = $this->db->select('group_notifications_user_default_interval', 'gnudi')
      ->fields('gnudi', ['group_id'])
      ->condition('uid', $user)
//      ->condition('default_send_interval', $intervalValue, '<>')
      ->execute()
      ->fetchCol();
    if (empty($groupNotificationsInterval)) {
      if ($this->isNewUser == TRUE) {
        $groupsIds = \Drupal::request()->request->get('groups');
        $preselected = array(
            RELEASE_MANAGEMENT => RELEASE_MANAGEMENT,
            INCIDENT_MANAGEMENT => INCIDENT_MANAGEMENT,
            PROBLEM_MANAGEMENT => PROBLEM_MANAGEMENT,
            SERVICE_LEVEL_MANAGEMENT => SERVICE_LEVEL_MANAGEMENT,
            BETRIEBSPORTAL_KONSENS => BETRIEBSPORTAL_KONSENS,
            KAPAZITATSMANAGEMENT => KAPAZITATSMANAGEMENT,
            VERFUGBARKEITSMANAGEMENT => VERFUGBARKEITSMANAGEMENT,
            COVID_INFO => COVID_INFO,
            RISIKOMANAGEMENT => RISIKOMANAGEMENT,
        );
        
        if (is_array($preselected)) {
	  // rschombu: This check is necessary to allow automatic user creation for testautomation
	  if ($groupsIds) {
            $groupsIds = array_merge($groupsIds, $preselected);
	  }
	  else {
	    $groupsIds = $preselected;
	  }
	}

	if(empty($groupsIds)){
          return;
        }
        $groups = Group::loadMultiple($groupsIds);
      }
      else {
        $groupIds = HzdNotificationsHelper::hzd_user_groups_list($user);
        $groups = Group::loadMultiple(array_keys($groupIds));
      }
      $groupNotifications = NULL;
      foreach ($groups as $group) {
        $groupNotifications[] = $this->db->insert('group_notifications_user_default_interval')
          ->fields([
            'uid' => $user,
            'default_send_interval' => $intervalValue,
            'group_id' => $group->id()
          ])
          ->execute();
      }
      
      $groupNotificationsInterval = $this->db->select('group_notifications_user_default_interval', 'gnudi')
        ->fields('gnudi', ['group_id'])
        ->condition('id', (array) $groupNotifications, 'IN')
        ->execute()
        ->fetchCol();
      
    }
//    pr($groupNotificationsInterval);exit;
    if (!empty($groupNotificationsInterval)) {
      $this->db->update('group_notifications_user_default_interval')
        ->fields(['default_send_interval' => $intervalValue])
        ->condition('uid', $user)
        ->execute();
      
      $groupNotifications = $this->db->select('group_notifications', 'gn')
        ->fields('gn')
        ->condition('group_id', $groupNotificationsInterval, 'IN')
        ->execute()
        ->fetchAll();
//    pr($groupNotifications);exit;
      if (!empty($groupNotifications)) {
        foreach ((array) $groupNotifications as $id) {
          $users = array_values(unserialize($id->uids));
          $key = array_search($user, $users);
          $update = FALSE;
          foreach ($users as $userKey => $item) {
            if ($item == $user) {
              unset($users[$userKey]);
              $update = TRUE;
            }
          }
          if ($id->send_interval == $intervalValue) {
            $users[] = $user;
            $update = TRUE;
          }
          if ($update == TRUE) {
            $this->db->update('group_notifications')
              ->fields(['uids' => serialize($users)])
              ->condition('id', $id->id)
              ->execute();
          }
        }
      }
    }
    
  }
  
  public function setDefaultForServiceNotifications($user, $intervalValue) {
    $serviceNotificationsInterval = $this->db->select('service_notifications_user_default_interval', 'sno')
      ->fields('sno', ['id'])
      ->condition('uid', $user)
      ->condition('default_send_interval', $intervalValue, '<>')
      ->execute()
      ->fetchCol();
    $serviceNotificationsOverrides = $this->db->select('service_notifications_override', 'sno')
      ->fields('sno', ['service_id'])
      ->condition('uid', $user)
      ->condition('send_interval', $intervalValue, '<>')
      ->execute()
      ->fetchCol();
    if (!empty($serviceNotificationsInterval) || !empty($serviceNotificationsOverrides)) {
      $this->db->update('service_notifications_user_default_interval')
        ->fields(['default_send_interval' => $intervalValue])
        ->condition('uid', $user)
        ->execute();
      $this->db->update('service_notifications_override')
        ->fields(['send_interval' => $intervalValue])
        ->condition('uid', $user)
        ->execute();
    }
    $serviceNotifications = $this->db->select('service_notifications', 'sn')
      ->fields('sn')
//            ->condition('service_id', $serviceNotificationsInterval, 'IN')
      ->execute()
      ->fetchAll();
    foreach ($serviceNotifications as $id) {
      $users = array_values((array) unserialize($id->uids));
      $key = array_search($user, (array) $users);
      $update = FALSE;
      foreach ($users as $userKey => $item) {
        if ($item == $user) {
          unset($users[$userKey]);
          $update = TRUE;
        }
      }
      if ($id->send_interval == $intervalValue) {
        $users[] = $user;
        $update = TRUE;
      }
      if ($update == TRUE) {
        $this->db->update('service_notifications')
          ->fields(['uids' => serialize($users)])
          ->condition('sid', $id->sid)
          ->execute();
      }
    }
  }

  public function arbeitsanleitungen_notifications($user = NULL) {
    $output[] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_notifications\Form\ArbeitsanleitungNotifications', $user);
    return $output;
  }
  
  public function rz_schnellinfos_notifications($user = NULL) {
    $output[] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_notifications\Form\SchnellinfosNotifications', $user);
    return $output;
  }

  /**
   * SAMS event-subscription management under personal notification preferences
   */
  public function sams_notifications($user = NULL) {
    // pr($user);exit;
    // Fügt die js-library hinzu, die man benötigt, um den Formularbatzen (Formulartabelle) unten zu bearbeiten.
    // Der normale Submit funktioniert nicht - es wrd immer nur das erste Formular submitted.
    $output[]['#attached']['library'] = array(
      'hzd_notifications/hzd_notifications',
      'hzd_sams/hzd_sams.artifact_info'
    );

    $output[] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_notifications\Form\SamsNotifications', $user);

    $connection = \Drupal::database();
    // Should use uid from URL
    if ($user == NULL) {
      $user = \Drupal::currentUser()->id();
    }
    // Abo-Übersichts-Formular:
    $subscriptions = $connection->query("SELECT id, service, class, product, status FROM {sams_notifications__user_default_interval} WHERE uid = $user")->fetchAll();
    if (count($subscriptions) > 0) {
      $output[] = array('#markup' => "<div class = 'sams_notifications'><div class = 'notifications_title'>" . $this->t('My current subscriptions') . "</div>");
      $output[] = [
        '#markup' => '
          <h4 class="hzd-form-element col-md-3">Verfahren</h4>
          <h4 class="hzd-form-element col-md-2">Klasse</h4>
          <h4 class="hzd-form-element col-md-3">Produkt</h4>
          <h4 class="hzd-form-element col-md-2">Status</h4>
          <h4 class="hzd-form-element col-md-1">Aktion</h4>
          ', 
      ];
      foreach ($subscriptions as $vals) {
        $output[] = \Drupal::formBuilder()
          ->getForm('Drupal\hzd_notifications\Form\UpdateSamsNotifications', $vals->id, $user, $vals->service, $vals->class, $vals->product, $vals->status);
      }
      $output[] = array('#markup' => "</div>");
    }
    return $output;
  }

  public
  function group_notifications($user = NULL) {
    $output[] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_notifications\Form\GroupNotifications', $user);
    return $output;
  }
  
  public
  function delete_notifications() {
    $service = \Drupal::request()->get('service');
    $type = \Drupal::request()->get('content_type');
    $interval = \Drupal::request()->get('interval');
    $uid = \Drupal::request()->get('uid');
    $rel_type = \Drupal::request()->get('rel_type');
    $id = \Drupal::request()->get('id');
    $content_types = array(
      1 => 'downtimes',
      2 => 'problem',
      3 => 'release',
      4 => 'early_warnings',
      5 => 'release_comments'
    );

    $action = \Drupal::request()->get('type');
    //pr($action);exit;
    if ($action == 'delete') {

      // \Drupal::database()->delete('service_notifications_override')
      //   ->condition('service_id', $service)
      //   ->condition('type', $content_types[$type])
      //   ->condition('uid', $uid)
      //   ->condition('send_interval', $interval)
      //   ->execute();
      //Instead Delete by primary key

      \Drupal::database()->delete('service_notifications_override')
        ->condition('sid', $id)
        ->execute();

      // get user default interval of a particlural type
      $default_intval = HzdNotificationsHelper::hzd_default_content_type_intval($uid, $content_types[$type], $rel_type);
      
      // remove the default interval of particular service and update the overrided interval
      HzdNotificationsHelper::hzd_update_content_type_intval($service, $default_intval, $uid, $content_types[$type], $interval);
    }
    else {
      $default_intval = HzdNotificationsHelper::hzd_default_content_type_intval($uid, $content_types[$type], $rel_type);
      HzdNotificationsHelper::hzd_update_content_type_intval($service, $interval, $uid, $content_types[$type], $default_intval);
      $sid = \Drupal::database()
        ->select('service_notifications_override', 'sno')
        ->fields('sno', ['sid'])
        ->condition('service_id', $service)
        ->condition('type', $content_types[$type])
        ->condition('uid', $uid)
        ->execute()
        ->fetchField();
      //echo $interval;
      //$qfd = \Drupal::database()
      //->query("UPDATE service_notifications_override SET send_interval = $interval WHERE sid=$qu;")
      //->execute();
      $qfd = \Drupal::database()->update('service_notifications_override')
        ->fields(['send_interval' => $interval])
        ->condition('sid', $sid)
        ->execute();
//echo $qfd->__toString();
    }
    
    $resp = new \Drupal\Core\Ajax\AjaxResponse();
    $resp->setData(['success' => TRUE, 'data' => 'success']);
    return $resp;
  }

  /**
   * Gets called by Ajax POST-request and deletes a SAMS subscription
   */
  public function delete_sams_notifications() {
    $id = \Drupal::request()->get('id');
    $action = \Drupal::request()->get('type');
    if ($action == 'delete') {
      \Drupal::database()->delete('sams_notifications__user_default_interval')
        ->condition('id', $id)
        ->execute();
    }    
    $resp = new \Drupal\Core\Ajax\AjaxResponse();
    $resp->setData(['success' => TRUE, 'data' => 'success']);
    return $resp;
  }

  public
  function notification_templates() {
    $output[] = array('#markup' => $this->t('Notifications Templates'));
    $output[] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_notifications\Form\NotificationsTemplates');
    return $output;
  }
  
  function hzd_get_default_interval($uid, $rel_type) {
    $default_intval_per_user = \Drupal::database()->query("SELECT service_type, default_send_interval FROM {service_notifications_user_default_interval}  WHERE uid = :uid and rel_type = :type", array(
      ":uid" => $uid,
      ":type" => $rel_type
    ))->fetchAll();
    $default_interval = array();
    foreach ($default_intval_per_user as $val) {
      $default_interval[$val->service_type] = $val->default_send_interval;
    }
    return $default_interval;
  }
  
  function hzd_get_all_services($rel_type) {
    $query = \Drupal::database()->select('node_field_data', 'n');
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
  
  function userAccess($user = NULL) {
    if (empty($user)) {
      $user = \Drupal::currentUser()->id();
    }
    if (in_array('site_administrator', \Drupal::currentUser()->getRoles())) {
      return AccessResult::allowed();
    }
    if ($user == \Drupal::currentUser()->id()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * @param null $user
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  function userNewAccess($user = NULL) {
    if (is_object($user)) {
      $user = $user->id();
    } else {
      $user = User::load($user);
    }
    $group = Group::load(\Drupal::config('cust_group.arbeitsanleitungen.settings')->get('arbeitsanleitungen_id'));
    if($group) {
      $groupMember = $group->getMember($user);
      $user_role = $user->getRoles(TRUE);
      if (($groupMember && group_request_status($groupMember)) || array_intersect($user_role, array('site_administrator', 'administrator'))) {
        return AccessResult::allowed();
      } else {
        return AccessResult::forbidden();
      }
    }else {
      return AccessResult::forbidden();
    }

  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  function userSamsAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }
    
    $routeUserId = \Drupal::routeMatch()->getParameters()->get('user');
    $routeUser = User::load($routeUserId);
    $currentRoles = $account->getRoles();
    $group = Group::load(\Drupal::config('cust_group.sams.settings')->get('sams_id'));
    
    if($group) {
      $groupMember = $group->getMember($routeUser);
      if (!$groupMember) {
        return AccessResult::forbidden();
      }
      if (!group_request_status($groupMember)) {
        return AccessResult::forbidden();
      }
      if ($routeUserId == $account->id()) {
        return AccessResult::allowed();
      }
      if (in_array('site_administrator', $currentRoles)) {
    // Verbuggt
    // if (array_intersect($user_role, array('site_administrator', 'administrator'))) {
      return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    else {
      return AccessResult::forbidden();
    }
  }
}
