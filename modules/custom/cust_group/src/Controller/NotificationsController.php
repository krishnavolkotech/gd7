<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * save and send notifications based on user settings.
 */
class NotificationsController extends ControllerBase {
  
  static $hzdMailManger = null;
  static $mailCount = 0;
  
  function __construct(){
    
  }
  
  
  static function recordContentAlter($node,$action){
    if(empty($node)){
      return null;
    }
    
    if(in_array($node->getType(),['downtimes','early_warnings','problem','release'])){
      $data = self::getServiceNotificationData($node);
      self::insertNotification($data);
    }elseif($node->getType() == 'planning_files'){
      $data = self::getPlanningFileNotificationData($node);
      self::insertNotification($data);
    }elseif($node->getType() == 'quickinfo'){
      $data = self::getQuickInfoNotificationData($node);
      self::insertNotification($data);
    }elseif($action == 'update' && !empty($groupContent = \Drupal::entityQuery('group_content')->condition('entity_id',$node->id())->execute()) && in_array($node->getType(),['event','faq','forum','page','newsletter'])){
      //get the group content id which is reffered to node->id();
      $data = self::getGroupNotificationData($groupContent);
      self::insertNotification($data);
    }
  }
  
  static function recordGroupContentInsert($node,$action){
    if($action == 'insert' && !empty($groupContent = \Drupal::entityQuery('group_content')->condition('entity_id',$node->id())->execute()) && in_array($node->getType(),['event','faq','forum','page','newsletter'])){
      //get the group content id which is reffered to node->id();
      $data = self::getGroupNotificationData($groupContent);
      self::insertNotification($data);
    }
  }
  
  static function getQuickInfoNotificationData($node){
    $cckVal = $node->get('field_other_services')->value;
    $cckOptions = $node->get('field_other_services')->getSetting('allowed_values');
    //pr($cck);exit;
    $quick_info_notifications = \Drupal::database()->select('quickinfo_notifications','qn')
      ->fields('qn',['id'])
      ->condition('send_interval',[604800,86400],'IN')
      ->condition('cck',$cckOptions[$cckVal])
      ->execute()
      ->fetchCol();
    $data = [];
    foreach((array)$quick_info_notifications as $id){
      $data[] = ['type'=>'quickinfo_notifications','type_id'=>$id,'timestamp'=>REQUEST_TIME,'nid'=>$node->id()]; 
    }
    return $data;
  }
  
  static function getPlanningFileNotificationData($node){
    $planning_file_notifications = \Drupal::database()->select('planning_files_notifications','pfn')
      ->fields('pfn',['id'])
      ->condition('send_interval',[604800,86400],'IN')
      ->execute()
      ->fetchCol();
    $data = [];
    foreach((array)$planning_file_notifications as $id){
      $data[] = ['type'=>'planning_files_notifications','type_id'=>$id,'timestamp'=>REQUEST_TIME,'nid'=>$node->id()]; 
    }
    return $data;
  }
  
  static function getServiceNotificationData($node){
    $service_id = null;
    $type = $node->getType(); 
    switch($type){
      case 'downtimes':
        $service_id = \Drupal::database()->select('downtimes','d')
          ->fields('d',['service_id'])
          ->condition('d.downtime_id',$node->id(),'=')
          ->execute()
          ->fetchField();
        break;
      case 'problem':
        $service_id = $node->get('field_services')->referencedEntities()[0]->id();
        break;
      case 'release':
        $service_id = $node->get('field_relese_services')->referencedEntities()[0]->id();
        break;
      case 'early_warnings':
        $service_id = $node->get('field_release_service')->value;
        break;
    }
    $service_notifications = \Drupal::database()->select('service_notifications','sn')
      ->fields('sn',['sid'])
      ->condition('service_id',$service_id)
      ->condition('type',$type)
      ->condition('send_interval',[604800,86400],'IN')
      ->execute()
      ->fetchCol();
    $data = [];
    foreach((array)$service_notifications as $id){
      $data[] = ['type'=>'service_notifications','type_id'=>$id,'timestamp'=>REQUEST_TIME,'nid'=>$node->id()]; 
    }
    return $data;
  }
  
  function getGroupNotificationData($groupContent){
    $groupNode = \Drupal\group\Entity\GroupContent::load(reset($groupContent));
    $group = $groupNode->getGroup()->id();
    //echo $group;exit;
    $group_notifications_id = \Drupal::database()->select('group_notifications','gn')
      ->fields('gn',['id'])
      ->condition('group_id',$group)
      ->condition('send_interval',[604800,86400],'IN')
      ->execute()
      ->fetchCol();
    $data = [];
    foreach($group_notifications_id as $id){
      $data[] = ['type'=>'group_notifications','type_id'=>$id,'timestamp'=>REQUEST_TIME,'nid'=>$groupNode->id()]; 
    }
    return $data;
  }
  
  
  static function insertNotification($data){
    if(empty($data)){
      return null;
    }
    foreach($data as $item){
      $periodic_notifications_id = \Drupal::database()->select('periodic_notifications','pn')
      ->fields('pn',['id'])
      ->condition('type',$item['type'])
      ->condition('type_id',$item['type_id'])
      ->condition('nid',$item['nid'])
      ->condition('mail_sent',0)
      ->execute()
      ->fetchField();
      if(empty($periodic_notifications_id)){
        $insert = \Drupal::database()->insert('periodic_notifications')
          ->fields($item)->execute();
      }else{
        $update = \Drupal::database()->update('periodic_notifications')
          ->fields($item)->condition('id',$periodic_notifications_id)->execute();
      }
    }
    
    
  }
  
  
  static function dailyCron(){
    self::processQuickInfoNotification('86400');
    self::processServiceNotification('86400');
    self::processGroupNotification('86400');
    self::processPlanningFileNotification('86400');
    return true;
  }
  
  static function weeklyCron(){
    self::processQuickInfoNotification('604800');
    self::processServiceNotification('604800');
    self::processGroupNotification('604800');
    self::processPlanningFileNotification('604800');
    return true;
  }
  
  
  
  function processQuickInfoNotification($interval = null){
    if(is_null($interval) || !in_array($interval,[-1,0,86400,604800])){
      return false;
    }
    $quick_info_notifications = \Drupal::database()->select('quickinfo_notifications','qn')
      ->fields('qn',['uids','cck']);
    $quick_info_notifications->addJoin('inner','periodic_notifications','pn','pn.type_id = qn.id');
    $quick_info_notifications->addField('pn','nid');
    $quick_info_notifications->addField('pn','id');
    $quick_info_notifications->condition('qn.send_interval',$interval);
    $quick_info_notifications->condition('pn.mail_sent',0);
    $quick_info_notifications->condition('pn.type','quickinfo_notifications');
    $quick_info_notifications->condition('timestamp',REQUEST_TIME-$interval,'>=');
    $quick_info_notifications = $quick_info_notifications->execute()
      ->fetchAll();
    $data = null;
    $usersList = [];
    foreach($quick_info_notifications as $key=>$item){
      $uids = unserialize($item->uids);
      foreach($uids as $uid){
        $usersList[$uid][$item->cck][] = $item->nid;
      }
      $log[] = $item->id;
    }
    self::prepareQuickInfoMailData($usersList);
    if(!empty($log)){
      $update = \Drupal::database()->update('periodic_notifications')
        ->fields(['mail_sent'=>1])
        ->condition('id',$log,'IN')
        ->execute();
    }
  }
  
  function prepareQuickInfoMailData($userData){
    if(empty($userData)){
      return false;
    }
    //pr($userData);exit;
    $users = array_keys($userData);
    $userEmails = self::getUserEmails($users);
    foreach($userData as $userId => $quickInfo){
      if(in_array($userId,array_keys($userEmails))){
        $markup = null;
        $quickInfoNodes = [];
        foreach($quickInfo as $label=>$nideIds){
          foreach($nideIds as $nideId){
            if(!isset($quickInfoData[$nideId])){
              $quickInfoData[$nideId] = \Drupal\node\Entity\Node::load($nideId);
            }
            $quickInfoNodes[] = $quickInfoData[$nideId]->toLink(NULL,'canonical',['absolute'=>1]);
          }
          $markup[] = [
                    '#prefix'=>'<strong>'.$label.'</strong>:',
                    '#items'=>$quickInfoNodes,
                    '#theme'=>'item_list',
                    '#type'=>'ul',
                    '#weight'=>100,
                    ];
        }
        $params['message'] = $markup;
        $params['subject'] = 'HZD Quick Info Updated';
        self::sendNotificationMail($userEmails[$userId],$params);
        $params = null;
      }
    }
  }
  
  function processServiceNotification($interval = null){
    if(is_null($interval) || !in_array($interval,[-1,0,86400,604800])){
      return false;
    }
    $service_notifications = \Drupal::database()->select('service_notifications','sn')
      ->fields('sn',['uids','service_id']);
    $service_notifications->addJoin('inner','periodic_notifications','pn','pn.type_id = sn.sid');
    $service_notifications->addField('pn','nid');
    $service_notifications->addField('pn','id');
    $service_notifications->condition('sn.send_interval',$interval);
    $service_notifications->condition('pn.mail_sent',0);
    $service_notifications->condition('pn.type','service_notifications');
    $service_notifications->condition('timestamp',REQUEST_TIME-$interval,'>=');
    $service_notifications = $service_notifications->execute()
      ->fetchAll();
    $data = null;
    $usersList = [];
    foreach($service_notifications as $key=>$item){
      $uids = unserialize($item->uids);
      foreach($uids as $uid){
        $usersList[$uid][$item->service_id][] = $item->nid;
      }
      $log[] = $item->id;
    }
    //$plannigMailData = self::processPlanningFileNotification($interval);
    //pr($plannigMailData);exit;
    self::prepareServiceMailData($usersList);
    if(!empty($log)){
      $update = \Drupal::database()->update('periodic_notifications')
        ->fields(['mail_sent'=>1])
        ->condition('id',$log,'IN')
        ->execute();
    }
  }
  
  function prepareServiceMailData($userData){
    if(empty($userData)){
      return false;
    }
    $users = array_keys($userData);
    $userEmails = self::getUserEmails($users);
    foreach($userData as $userId => $service){
      if(!in_array($userId,array_keys($userEmails))){
        break;
      }
      $markup = null;
      foreach($service as $serviceId => $nideId){
        if(!isset($serviceEntity[$serviceId])){
          $serviceEntity[$serviceId] = \Drupal\node\Entity\Node::load($serviceId);
        }
        $serviceNodes = null;
        foreach($nideId as $cont){
          if(!isset($serviceEntity[$cont])){
            $serviceEntity[$cont] = \Drupal\node\Entity\Node::load($cont);
          }
          $serviceNodes[] = $serviceEntity[$cont]->toLink(NULL,'canonical',['absolute'=>1]);
        }
        $markup[] = [
                    '#prefix'=>'<strong>'.$serviceEntity[$serviceId]->label().'</strong>:',
                    '#items'=>$serviceNodes,
                    '#theme'=>'item_list',
                    '#type'=>'ul',
                    '#weight'=>100,
                    ];
        //$markup[] = $plannigMailData[$userId]['planning_files'];
      }
      $params['message'] = $markup;
      $params['subject'] = 'HZD Service Content Updated';
      self::sendNotificationMail($userEmails[$userId],$params);
      $params = null;
    }
  }
  
  function processGroupNotification($interval = null){
    if(is_null($interval) || !in_array($interval,[-1,0,86400,604800])){
      return false;
    }    
    $periodic_notifications = \Drupal::database()->select('group_notifications','gn')
      ->fields('gn',['uids','group_id']);
    $periodic_notifications->addJoin('inner','periodic_notifications','pn','pn.type_id = gn.id');
    $periodic_notifications->addField('pn','nid');
    $periodic_notifications->addField('pn','id');
    $periodic_notifications->condition('gn.send_interval',$interval);
    $periodic_notifications->condition('pn.mail_sent',0);
    $periodic_notifications->condition('pn.type','group_notifications');
    $periodic_notifications->condition('timestamp',REQUEST_TIME-$interval,'>=');
    $periodic_notifications = $periodic_notifications->execute()
      ->fetchAll();
    $data = null;
    $usersList = [];
    foreach($periodic_notifications as $key=>$item){
      $uids = unserialize($item->uids);
      foreach($uids as $uid){
        $usersList[$uid][$item->group_id][] = $item->nid;
      }
      $log[] = $item->id;
    }
    self::prepareGroupsMailData($usersList);
    if(!empty($log)){
      $update = \Drupal::database()->update('periodic_notifications')
        ->fields(['mail_sent'=>1])
        ->condition('id',$log,'IN')
        ->execute();
    }
  }
  
  function prepareGroupsMailData($userData){
    if(empty($userData)){
      return false;
    }
    $groupContentEntity = $groupEntity = [];
    $users = array_keys($userData);
    $userEmails = self::getUserEmails($users);
    foreach($userData as $userId => $group){
      if(!in_array($userId,array_keys($userEmails))){
        break;
      }
      $markup = null;
      foreach($group as $groupId => $groupContent){
        if(!isset($groupEntity[$groupId])){
          $groupEntity[$groupId] = \Drupal\group\Entity\Group::load($groupId);
        }
        $groupContentItem = null;
        foreach($groupContent as $cont){
          if(!isset($groupContentEntity[$cont])){
            $groupContentEntity[$cont] = \Drupal\group\Entity\GroupContent::load($cont);
          }
            $groupContentItem[] = $groupContentEntity[$cont]->toLink($groupContentEntity[$cont]->label(),'canonical',['absolute'=>1]);
            
          
        }
        $markup[] = [
                    '#prefix'=>'<strong>'.$groupEntity[$groupId]->label().'</strong>:',
                    '#items'=>$groupContentItem,
                    '#theme'=>'item_list',
                    '#type'=>'ul',
                    '#weight'=>100,
                    ];
        
      }
      $params['message'] = $markup;
      $params['subject'] = 'HZD Group Content Updated';
      self::sendNotificationMail($userEmails[$userId],$params);
      $params = null;
    }
  }
  
  function processPlanningFileNotification($interval = null){
    if(is_null($interval) || !in_array($interval,[-1,0,86400,604800])){
      return false;
    }
    $planning_files_notifications = \Drupal::database()->select('planning_files_notifications','pfn')
      ->fields('pfn',['id','uids']);
    $planning_files_notifications->addJoin('inner','periodic_notifications','pn','pn.type_id = pfn.id');
    $planning_files_notifications->addField('pn','nid');
    $planning_files_notifications->addField('pn','id','pnid');
    $planning_files_notifications->condition('pfn.send_interval',$interval);
    $planning_files_notifications->condition('pn.mail_sent',0);
    $planning_files_notifications->condition('pn.type','planning_files_notifications');
    $planning_files_notifications->condition('timestamp',REQUEST_TIME-$interval,'>=');
    $planning_files_notifications = $planning_files_notifications->execute()->fetchAll();
    $data = null;
    $usersList = [];
    foreach($planning_files_notifications as $key=>$item){
      $uids = unserialize($item->uids);
      foreach($uids as $uid){
        $usersList[$uid][$item->nid] = $item->nid;
      }
      $log[$item->pnid] = $item->pnid;
    }
    //pr($log);exit;
    //pr($usersList);exit;
    self::preparePlanningFileMailData($usersList);
    
    if(!empty($log)){
      $update = \Drupal::database()->update('periodic_notifications')
        ->fields(['mail_sent'=>1])
        ->condition('id',$log,'IN')
        ->execute();
    }
  }
  
  function preparePlanningFileMailData($userData){
    if(empty($userData)){
      return false;
    }
    
    
    $users = array_keys($userData);
    $userEmails = self::getUserEmails($users);
    foreach($userData as $userId => $planningfile){
      if(in_array($userId,array_keys($userEmails))){
        $markup = null;
        $planningNodes = [];
        foreach($planningfile as $nideId){
          if(!isset($planningfileData[$nideId])){
            $planningfileData[$nideId] = \Drupal\node\Entity\Node::load($nideId);
          }
          $planningNodes[] = $planningfileData[$nideId]->toLink(NULL,'canonical',['absolute'=>1]);
          
        }
        $markup[] = [
                      '#prefix'=>'<strong>Planning Files</strong>:',
                      '#items'=>$planningNodes,
                      '#theme'=>'item_list',
                      '#type'=>'ul',
                      '#weight'=>100,
                      ];
        $params['message'] = $markup;
        $params['subject'] = 'HZD Planning Files Updated';
        self::sendNotificationMail($userEmails[$userId],$params);
        $params = null;
      }
    }
  }
  
  function sendNotificationMail($to,$params){
    if(!isset(self::$hzdMailManger)){
      self::$hzdMailManger = \Drupal::service('plugin.manager.mail');
    }
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $params['message'] = self::getBodyPreText().\Drupal::service('renderer')->render($params['message']);
    $send = true;
    //if(!isset(self::$mailCount)){
    //  self::$mailCount = 0;
    //}
    //if(self::$mailCount < 5){
      $result = self::$hzdMailManger->mail('cust_group', 'periodic_notifications', $to, $langcode, $params, NULL, $send);
    //}
    //self::$mailCount++; 
  }
  
  function getUserEmails(array $uids = []){
    $userEmails = \Drupal::database()->select('users_field_data','ufd')
      ->fields('ufd',['uid','mail'])
      ->condition('ufd.uid',$uids,'IN')
      ->condition('ufd.status',1);
    $userEmails->leftJoin('inactive_users','iu','iu.uid = ufd.uid');
    $orCondition = $userEmails->orConditionGroup()->condition('iu.inactive_user_notification_flag',0)->isNull('iu.inactive_user_notification_flag');
    $userEmails->condition($orCondition);
    $userEmails = $userEmails->execute()->fetchAllAssoc('uid');
    $userData = [];
    foreach($userEmails as $user){
      $userData[$user->uid] = $user->mail;
    }
    return $userData;
  }
  
  function getBodyPreText(){
    return "Lieber Benutzer,<br><br>Der folgende Inhalt wurde auf dem Portal aktualisiert.<br><br>";
  }
  
}
