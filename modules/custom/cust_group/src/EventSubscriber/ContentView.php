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

/**
 * Description of ContentView
 *
 * @author sandeep
 */
class ContentView implements EventSubscriberInterface {

  //put your code here
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('groupContentRedirect');
    return $events;
  }

  function groupContentRedirect(GetResponseEvent $event) {
    $request = $event->getRequest();
    if ($request->attributes->get('_route') !== 'entity.node.canonical') {
      return;
    }
    $node = $request->attributes->get('node');
    if ($groupContent = CustGroupHelper::getGroupNodeFromNodeId($node->id())) {
      $response = new RedirectResponse($groupContent->toUrl()->toString());
      $event->setResponse($response);
    }
    if($node->getType() == 'service_profile'){
      $response = new RedirectResponse(Url::fromRoute('downtimes.service_profiles',['group'=>INCIDENT_MANAGEMENT])->toString());
      $event->setResponse($response);
    }
    return $event;
  }

}
