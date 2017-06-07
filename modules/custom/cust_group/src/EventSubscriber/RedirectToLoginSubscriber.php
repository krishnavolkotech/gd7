<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RedirectToLogin
 *
 * @author sandeep
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

class RedirectToLoginSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new RedirectToLoginSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, RedirectDestinationInterface $redirect_destination) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->redirectDestination = $redirect_destination;
  }
  
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = array('on403HzdException');
    return $events;
  }

  static function on403HzdException(GetResponseEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      $node = \Drupal::routeMatch()->getParameter('node');
      $type = is_object($node)?$node->getType():null;
//      echo $type;exit;
      if (\Drupal::currentUser()->isAnonymous() && $type != 'downtimes') {
        global $base_url;
        $currentPath = \Drupal::service('path.current')->getPath();
        $resultPath = \Drupal::service('path.alias_manager')->getAliasByPath($currentPath);
        $loginPath = '/user/login?destination=' . $resultPath;
        drupal_set_message(t('Please login to access the page.'), 'error');
        header('Location: ' . $base_url . $loginPath);
        exit;
      }
    }
  }

}
