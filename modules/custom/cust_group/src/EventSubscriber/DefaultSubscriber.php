<?php

namespace Drupal\cust_group\EventSubscriber;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber;

/**
 * Exception subscriber for handling core custom HTML error pages.
 */
class DefaultSubscriber extends DefaultExceptionHtmlSubscriber {
 
  public function __construct(){

  }
  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return -49;
  }

  /**
   * {@inheritdoc}
   */
  public function on403(GetResponseForExceptionEvent $event) {
    if(\Drupal::currentUser()->isAnonymous()){
      global $base_url;
      $currentPath = \Drupal::service('path.current')->getPath();
      $loginPath = '/user/login?destination='.$currentPath;
      drupal_set_message(t('Please login to access the page.'), 'error');
      //return new \Symfony\Component\HttpFoundation\RedirectResponse($loginPath);
      header('Location: '.$base_url.$loginPath);
      exit;
    }
  }

}
