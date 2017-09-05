<?php

namespace Drupal\cust_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;

/**
 * Returns responses for NSM Users routes.
 */
class NSMPortalController extends ControllerBase {
  
  var $nsmDomain = 'http://nsm-portal.hessen.testa-de.net/hzdnsm/login_from_bp.jsp';

  public function userList(){
    $nsmRoles = $this->nsmRoles();
    $header = array(t('State'));
    foreach($nsmRoles as $roles){
      array_push($header,t($roles));
    }
    $states=$this->get_all_user_state();
    foreach($states as $stid => $state){
      if($stid!=1){
	//incrementing value of state id(id starts from o after shifting)
	$row = array($this->t($state));
	foreach($nsmRoles as $rid=>$roles){
	  $nsm_user_names = \Drupal::database()
	    ->select('nsm_user','nu')
	    ->fields('nu')
	    ->condition('nu.state_id',$stid)
	    ->condition('nu.nsm_role_id',$rid)
	    ->execute()
	    ->fetchAssoc();
	  array_push($row,$nsm_user_names['nsm_username']);
	}
	$rows_nsm[] = $row;
      }
    }
    //    pr($rows_nsm);exit;
    return ['#type' => 'table','#header' => $header,'#rows' =>$rows_nsm,];
  }

  public function nsmRoles(){
    $roles = \Drupal::database()
      ->select('nsm_role','nr')
      ->fields('nr')
      ->execute()
      ->fetchAll();
    foreach($roles as $role){
      $nsmRoles[$role->id] = $role->rolename;
    }
    return $nsmRoles;
  }

  function get_all_user_state($active = 0){
    $userStates = \Drupal::database()->select('states','s')
      ->fields('s',['id','state','abbr']);
    if ($active) {
      $userStates = $userStates->condition('s.active','1');
    }
    $userStates = $userStates->execute()->fetchAll();
    foreach($userStates as $state){
      if($state->abbr == null){
	$states[$state->id] = 'Bundesland';
      }
      else
	$states[$state->id] = $state->state . "(".$state->abbr.")";
    }
    return $states;
  }
  



  function authentication(){
    //used to validate the user session sent from external nsm portal.
    //params 

    $user_name = $_REQUEST['user_bp'];
    $sid = $_REQUEST['id'];
    $nsm_user = $_REQUEST['user_nsm'];
    $userQuery = \Drupal::entityQuery('user')
      ->condition('name', $user_name);
    $uid = $userQuery->execute();
    $res = \Drupal::database()->select('sessions','s')
      ->fields('s',['sid'])
      ->condition('s.uid',$uid)
      ->condition('s.sid',$sid)
      ->execute()->fetchAll();


    if ($uid) {
      $drupalUser = \Drupal\user\Entity\User::load($uid);
      $userState = \Drupal::database()->select('cust_profile','cp')
	->fields('cp',['state_id'])
	->condition('cp.uid',$uid)   
	->execute()
	->fetchField();
      $userNSMRole = \Drupal::database()->select('nsm_user_role','nur')
	->fields('nur',['nsm_role_id'])
	->condition('nur.user_id',$uid)   
	->execute()
	->fetchField();
      $nsm_username = \Drupal::database()->select('nsm_user','nu')
	->fields('nu',['nsm_username'])
	->condition('nu.nsm_role_id',$userNSMRole)
	->condition('nu.state_id',$userState)
	->execute()
	->fetchField();
    }
    if (trim($nsm_username) == trim($nsm_user)) {
      echo (int) $res;
    }
    exit;
  }

  function login(){
    $id = \Drupal::service('session')->getId();
    $user = \Drupal::currentUser();
    $user_bp = $user->getAccountName();
    $user_nsm = \Drupal::database()->select('nsm_user','nu')
      ->fields('nu',['nsm_username']);
    $user_nsm->addJoin('INNER','nsm_user_role','nur','nu.nsm_role_id=nur.nsm_role_id');
    $user_nsm = $user_nsm->condition('nur.user_id',$user->id())
      ->execute()
      ->fetchField();    
    $params = ['id='.$id,'user_bp='.$user_bp,'user_nsm='.$user_nsm];
    $preparedAuthUrl = $this->nsmDomain.'?'.implode('&',$params);
    return new TrustedRedirectResponse($preparedAuthUrl);
  }

}
