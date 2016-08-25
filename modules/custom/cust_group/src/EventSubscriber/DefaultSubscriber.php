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

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Constructs a new CustomPageExceptionHtmlSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP Kernel service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $access_unaware_router
   *   A router implementation which does not check access.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, HttpKernelInterface $http_kernel, LoggerInterface $logger, RedirectDestinationInterface $redirect_destination, UrlMatcherInterface $access_unaware_router, AccessManagerInterface $access_manager) {
    parent::__construct($http_kernel, $logger, $redirect_destination, $access_unaware_router);
    $this->configFactory = $config_factory;
    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return -50;
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

  /**
   * {@inheritdoc}
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $custom_404_path = $this->configFactory->get('system.site')->get('page.404');
    if (!empty($custom_404_path)) {
      $this->makeSubrequestToCustomPath($event, $custom_404_path, Response::HTTP_NOT_FOUND);
    }
  }
  
}
