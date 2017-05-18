<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\cust_group\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Component\Utility\Xss;
use Drupal\Core\EventSubscriber\DefaultExceptionSubscriber;
use Drupal\cust_group\CustGroupHelper;
use Drupal\Core\Routing;
use Drupal\user\Entity\User;

/**
 * Description of ContentView
 *
 * @author sandeep
 */
class ContentView implements EventSubscriberInterface {

  //put your code here
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('groupContentRedirect');
    return $events;$response = new RedirectResponse($groupContent->toUrl()->toString());
  }

  function groupContentRedirect(GetResponseEvent $event) {
    $request = $event->getRequest();
    if(\Drupal::service('module_handler')->moduleExists('queue_mail')){
      module_set_weight('queue_mail', 20);
    }
    if(\Drupal::service('module_handler')->moduleExists('reroute_email')){
      module_set_weight('reroute_email', 19);
    }
    if ($request->attributes->get('_route') == 'front_page.front') {
      //$response = new RedirectResponse(Url::fromRoute('front_page.front',['tour'=> TRUE])->toString());
      $user = \Drupal::currentUser()->id();
      if ($user) {
        $currentUser = User::load($user);
        if (!(in_array('site_administrator', $currentUser->getRoles()) || $user == 1)) {
          if ($currentUser->getLastAccessedTime() == $currentUser->getLastLoginTime()) {
            if (!isset($_GET['tour'])) {
              $all_query['query'] = ['tour' => TRUE];
              $response = new RedirectResponse(Url::fromUserInput('/', $all_query)->toString());
              $event->setResponse($response);
              return $event;
            }
          }
        }
      }
    }

    if ($request->attributes->get('_route') !== 'entity.node.canonical') {
      return;
    }
    $node = $request->attributes->get('node');
//    if ($groupContent = CustGroupHelper::getGroupNodeFromNodeId($node->id())) {
//      $type = $node->getType();
//      $typeMappings = ['problem'=>'problems','quickinfo'=>'rz-schnellinfos','downtimes'=>'downtimes'];
//      if(in_array($type,array_keys($typeMappings))){
//        $group = $groupContent->getGroup()->id();
//        $groupContentTye = $typeMappings[$type];
//        
//        $response = new RedirectResponse(Url::fromRoute('cust_group.group_content_view',['group'=>$group,'type'=>$groupContentTye,'group_content'=>$groupContent->id()])->toString());
//      }else{
//        $response = new RedirectResponse($groupContent->toUrl()->toString());
//      }
//      
//      $event->setResponse($response);
//    }
    if($node->getType() == 'service_profile'){
      $response = new RedirectResponse(Url::fromRoute('downtimes.service_profiles',['group'=>INCIDENT_MANAGEMENT])->toString());
      $event->setResponse($response);
    }
  

    
    
    return $event;
  }

}
