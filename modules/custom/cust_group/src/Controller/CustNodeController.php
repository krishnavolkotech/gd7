<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\group\Entity\GroupContentInterface;

/**
 * Returns responses for Node routes.
 */
class CustNodeController extends ControllerBase {
  
  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type entity for the node.
   *
   * @return array
   *   A node submission form.
   */
  public function add($group_id, NodeTypeInterface $node_type) {
    $maintainance_id = \Drupal::config('downtimes.settings')
      ->get('maintenance_group_id');
    $quickinfo_id = \Drupal::config('quickinfo.settings')
      ->get('quickinfo_group_id');
    
    $node = $this->entityManager()->getStorage('node')->create(array(
      'type' => $node_type->id(),
    ));
    
    $form = $this->entityFormBuilder()->getForm($node);
    
    return $form;
  }
  
  function groupContentView(RouteMatchInterface $routeMatch) {
    $parm = $routeMatch->getParameter('group_content');
    $entity = $parm->get('entity_id')->referencedEntities()[0];
    $view_builder = \Drupal::entityManager()
      ->getViewBuilder($entity->getEntityTypeId());
    return $view_builder->view($entity, 'full', 'de');
  }
  
  function groupContentTitle(RouteMatchInterface $routeMatch) {
    $parm = $routeMatch->getParameter('group_content');
    if ($parm instanceof GroupContent && $parm->hasField('entity_id')) {
      return $parm->get('entity_id')->referencedEntities()[0]->label();
    }else{
      return [];
    }
  }
  
  function groupMemberView() {
    $member = \Drupal::routeMatch()->getParameter('group_content');
    $user = $member->get('entity_id')->referencedEntities()[0];
    $view_builder = \Drupal::entityManager()->getViewBuilder('user');
    return $view_builder->view($user);
  }
  
  function groupMemberTitle() {
    $parm = \Drupal::routeMatch()->getParameter('group_content');
    return $parm->get('entity_id')->referencedEntities()[0]->label();
  }
  
  static function hzdGroupAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
    if ($group = $route_match->getParameter('group')) {
      if (!is_object($group)) {
        $group = \Drupal\group\Entity\Group::load($group);
      }
      $groupMember = $group->getMember($user);
      if (($groupMember && $groupMember->getGroupContent()
            ->get('request_status')->value == 1) || $user->id() == 1 || in_array('site_administrator', $user->getRoles())
      ) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }
  
  static function hzdCreateDowntimesAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
    if ($user) {
      $group = $route_match->getParameter('group');
      $groupMember = $group->getMember($user);
      if ($group->id() == INCIDENT_MANAGEMENT && (($groupMember && $groupMember->getGroupContent()
              ->get('request_status')->value == 1) || array_intersect([
            'site_administrator',
            'administrator'
          ], $user->getRoles()))
      ) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::forbidden();
  }
  
  static function hzdIncidentGroupAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
    $uid = $user->id();
    if ($uid == 0) {
      return AccessResult::allowed();
    }
    if ($group = $route_match->getParameter('group')) {
      if (!is_object($group)) {
        $group = \Drupal\group\Entity\Group::load($group);
      }
      $groupMember = $group->getMember($user);
      if (($groupMember && $groupMember->getGroupContent()
            ->get('request_status')->value == 1) || $user->id() == 1 || in_array('site_administrator', $user->getRoles())
      ) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }
  
  static function hzdnodeConfirmAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
    if ($group = $route_match->getParameter('group')) {
      if (!is_object($group)) {
        $group = \Drupal\group\Entity\Group::load($group);
      }
      $groupMember = $group->getMember($user);
      if (($groupMember && $groupMember->getGroupContent()
            ->get('request_status')->value == 1) || $user->id() == 1 || in_array('site_administrator', $user->getRoles())
      ) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    else {
      if ($node = $route_match->getParameter('node')) {
        $group_id = \Drupal::database()
          ->select('group_content_field_data', 'gcfd')
          ->fields('gcfd', ['gid', 'id'])
          ->condition('gcfd.entity_id', $node)
          ->execute()
          ->fetchAssoc();
        if ($group_id) {
          $group = \Drupal\group\Entity\Group::load($group_id['gid']);
          $groupMember = $group->getMember($user);
          if (($groupMember && $groupMember->getGroupContent()
                ->get('request_status')->value == 1) || $user->id() == 1 || in_array('site_administrator', $user->getRoles())
          ) {
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
    return AccessResult::neutral();
  }
  
  ///added for drupal core views
  static function hzdGroupViewsAccess(Route $route, RouteMatch $route_match, AccountInterface $user) {
    if ($group = $route_match->getParameter('arg_0')) {
      if (!is_object($group)) {
        $group = \Drupal\group\Entity\Group::load($group);
      }
      $groupMember = $group->getMember($user);
      if (($groupMember && $groupMember->getGroupContent()
            ->get('request_status')->value == 1) || $user->id() == 1
      ) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }
  
  static function isGroupAdmin($group_id = NULL) {
    if (!$group_id) {
      return FALSE;
    }
    if (in_array('site_administrator', \Drupal::currentUser()
        ->getRoles()) || \Drupal::currentUser()->id() == 1
    ) {
      return TRUE;
    }
    $group = \Drupal\group\Entity\Group::load($group_id);
    $content = $group->getMember(\Drupal::currentUser());
    if ($content && $content->getGroupContent()
        ->get('request_status')->value == 1
    ) {
      $contentId = $content->getGroupContent()->id();
      $adminquery = \Drupal::database()
        ->select('group_content__group_roles', 'gcgr')
        ->fields('gcgr', ['group_roles_target_id'])
        ->condition('entity_id', $contentId)
        ->execute()
        ->fetchAll();
      return (bool) !empty($adminquery);
    }
    
    return FALSE;
  }
  
  static function getNodeGroupId($node = NULL) {
    if (!$node) {
      return FALSE;
    }
//      $checkGroupNode = Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($node->id());
//    $checkGroupNode = \Drupal::database()->select('group_content_field_data', 'gcfd')
//                    ->fields('gcfd', ['gid', 'id'])
//                    ->condition('gcfd.entity_id', $node->id())
//                    ->execute()->fetchAssoc();
//    if (!empty($checkGroupNode)) {
//      return $checkGroupNode;
//    }
    return \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($node->id());
  }
  
  function groupNodeEdit() {
    //pr(\Drupal::routeMatch()->getParameter('group_content'));exit;
    $group_content = \Drupal::routeMatch()->getParameter('group_content');
    $group = \Drupal::routeMatch()->getParameter('group');
    $node = $group_content->get('entity_id')->referencedEntities()[0];
    $form = \Drupal::entityTypeManager()
      ->getFormObject('node', 'default')
      ->setEntity($node);
    $url = new \Drupal\Core\Url('entity.group_content.canonical', [
      'group' => $group->id(),
      'group_content' => $group_content->id()
    ]);
    return \Drupal::formBuilder()->getForm($form, ['redirect' => $url]);
  }
  
  function groupMemberCleanup() {
    $groupContent = \Drupal::entityQuery('group_content');
    $orCondition = $groupContent->orConditionGroup()
      ->condition('type', '%member%', 'LIKE')
      ->condition('type', [
        'group_content_type_b2ed3eb8d19c9',
        'group_content_type_d4b06e2b6aad0',
        'group_content_type_ecf0249297413'
      ], 'IN');
    $groupContent = $groupContent->condition($orCondition)
      ->execute();
    //pr($groupContent);exit;
    
    foreach ($groupContent as $groupUser) {
      $gUser = \Drupal\group\Entity\GroupContent::load($groupUser);
      
      if ($gUser && $gUser->entity_id->referencedEntities()) {
        
      }
      elseif ($gUser) {
        $gUser->delete();
      }
    }
    echo 'completed';
    exit;
  }
  
  static public function ContactformTitle() {
    return t('Contact');
  }
  
  
  function updateNotifications() {
    $this->db = \Drupal::database();
    $this->intervals = [-1, 0];
    
    $this->updateServiceNotifications();
    $this->updateGroupNotifications();
    $this->updatePlanningFileNotifications();
    $this->updateQuickinfoNotifications();
    echo 'Successufully updated all users notification';
    exit;
  }
  
  
  function updateServiceNotifications() {
    
    //As users data is not properly saved in drupal 6 we were stuck for service update notifications.
    
    $gi = $this->db->select('service_notifications_override', 'base_table')
      ->fields('base_table')
      ->execute()
      ->fetchAll();
    $preparedArray = [];
    foreach ($gi as $item) {
      $preparedArray[$item->service_id][$item->type][$item->send_interval][] = $item->uid;
    }
    foreach ($preparedArray as $item => $values) {
      foreach ($values as $type => $value) {
        foreach ($this->intervals as $interval) {
          $finalArray[] = [
            'service_id' => $item,
            'type' => $type,
            'send_interval' => $interval,
            'uids' => @serialize($value[$interval])
          ];
        }
      }
      
    }
    
    foreach ($finalArray as $value) {
      $check = $this->db->select('service_notifications', 'base_table')
        ->fields('base_table', ['sid'])
        ->condition('service_id', $value['service_id'])
        ->condition('type', $value['type'])
        ->condition('send_interval', $value['send_interval'])
        ->execute()
        ->fetchField();
      if (empty($check)) {
        $this->db->insert('service_notifications')
          ->fields($value)
          ->execute();
      }
      else {
        $this->db->update('service_notifications')
          ->fields($value)
          ->condition('sid', $check)
          ->execute();
      }
    }
  }
  
  function updateGroupNotifications() {
    $gi = $this->db->select('group_notifications_user_default_interval', 'base_table')
      ->fields('base_table')
      ->execute()
      ->fetchAll();
    $preparedArray = [];
    foreach ($gi as $item) {
      $preparedArray[$item->group_id][$item->default_send_interval][] = $item->uid;
    }
    foreach ($preparedArray as $item => $value) {
      foreach ($this->intervals as $interval) {
//        $group = Group::load($item)->label();
        $finalArray[] = [
          'group_id' => $item,
          'group_name' => '',
          'send_interval' => $interval,
          'uids' => @serialize($value[$interval])
        ];
      }
    }
    foreach ($finalArray as $value) {
      $check = $this->db->select('group_notifications', 'base_table')
        ->fields('base_table', ['id'])
        ->condition('group_id', $value['group_id'])
        ->condition('send_interval', $value['send_interval'])
        ->execute()
        ->fetchField();
      if (empty($check)) {
        $this->db->insert('group_notifications')
          ->fields($value)
          ->execute();
      }
      else {
        $this->db->update('group_notifications')
          ->fields($value)
          ->condition('id', $check)
          ->execute();
      }
    }
  }
  
  function updatePlanningFileNotifications() {
    $gi = $this->db->select('planning_files_notifications_default_interval', 'base_table')
      ->fields('base_table')
      ->execute()
      ->fetchAll();
    $preparedArray = [];
    foreach ($gi as $item) {
      $preparedArray[$item->default_send_interval][] = $item->uid;
    }
    foreach ($preparedArray as $item => $value) {
      $finalArray[] = [
        'send_interval' => $item,
        'uids' => @serialize($value)
      ];
      
    }
    foreach ($finalArray as $value) {
      $check = $this->db->select('group_notifications', 'base_table')
        ->fields('base_table', ['id'])
        ->condition('group_id', $value['group_id'])
        ->condition('send_interval', $value['send_interval'])
        ->execute()
        ->fetchField();
      if (empty($check)) {
        $this->db->insert('planning_files_notifications')
          ->fields($value)
          ->execute();
      }
      else {
        $this->db->update('planning_files_notifications')
          ->fields($value)
          ->condition('id', $check)
          ->execute();
      }
    }
  }
  
  function updateQuickinfoNotifications() {
    $qi = $this->db->select('quickinfo_notifications_user_default_interval', 'base_table')
      ->fields('base_table')
      ->execute()
      ->fetchAll();
    $preparedArray = [];
    foreach ($qi as $item) {
      $preparedArray[$item->affected_service][$item->default_send_interval][] = $item->uid;
    }
    foreach ($preparedArray as $item => $value) {
      foreach ($this->intervals as $interval) {
        $finalArray[] = [
          'cck' => $item,
          'send_interval' => $interval,
          'uids' => @serialize($value[$interval])
        ];
      }
    }
    $this->db->query("Alter table {quickinfo_notifications} convert to character set utf8 collate utf8_general_ci;");
    foreach ($finalArray as $value) {
      $check = $this->db->select('quickinfo_notifications', 'base_table')
        ->fields('base_table', ['id'])
        ->condition('cck', $value['cck'])
        ->condition('send_interval', $value['send_interval'])
        ->execute()
        ->fetchField();
      if (empty($check)) {
        $this->db->insert('quickinfo_notifications')
          ->fields($value)
          ->execute();
      }
      else {
        $this->db->update('quickinfo_notifications')
          ->fields($value)
          ->condition('id', $check)
          ->execute();
      }
    }
    //Updating Quuick info notifications completed.
  }
  
  function updateUrlAlias() {
    $aliasStorage = \Drupal::service('path.alias_storage');
    $groupContent = \Drupal::entityQuery('group_content')
      ->condition('type', '%group_node-pag%', 'LIKE')
      ->execute();
    $pid = [];
    foreach ($groupContent as $item) {
      $groupContentEntity = GroupContent::load($item);
      $node = $groupContentEntity->getEntity();
//      $nodeAlias = $node->get('path')->value;
//      pr($node->toUrl()->getInternalPath());
      $urlAlias = $aliasStorage->load([
        'source' => '/' . $node->toUrl()
            ->getInternalPath()
      ]);
      if (!empty($urlAlias)) {
        $aliasStorage->save('/' . $groupContentEntity->toUrl()
            ->getInternalPath(), $urlAlias['alias'], $urlAlias['langcode'], $urlAlias['pid']);
        $pid[] = $urlAlias['pid'];
      }
    }
    pr(($pid));
    echo 'Success';
    exit;
  }
}
