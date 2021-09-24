<?php

namespace Drupal\cust_filebrowser\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\filebrowser\Services\Common;
use Drupal\cust_filebrowser\Services\FilebrowserHelper;

/**
 * Checks access for displaying configuration translation page.
 */
class FilebrowserDownloadAccessCheck implements AccessInterface {

  /**
   * The route match object.
   *
   * @var Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The AccountInterface.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The Filebroser Common Service.
   *
   * @var Drupal\filebrowser\Services\Common
   */
  protected $common;

  /**
   * The FilebrowserHelper Service.
   *
   * @var Drupal\cust_filebrowser\Services\FilebrowserHelper
   */
  protected $filebrowserHelper;

  /**
   * The FilebrowserDownloadAccessCheck constructor.
   *
   * @param Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match object.
   * @param Drupal\Core\Session\AccountInterface $account
   *   The AccountInterface.
   * @param Drupal\filebrowser\Services\Common $common
   *   The Filebroser Common Service.
   * @param Drupal\cust_filebrowser\Services\FilebrowserHelper $filebrowserHelper
   *   The FilebrowserHelper Service.
   */
  public function __construct(RouteMatchInterface $routeMatch, AccountInterface $account, Common $common, FilebrowserHelper $filebrowserHelper) {
    $this->routeMatch = $routeMatch;
    $this->account = $account;
    $this->common = $common;
    $this->filebrowserHelper = $filebrowserHelper;
  }

  /**
   * A custom access check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    $fid = $this->routeMatch->getParameter('fid');
    $fileInfo = $this->common->nodeContentLoad($fid);
    $nid = $fileInfo['nid'];
    $group = $this->filebrowserHelper->getGroupFromNodeId($nid);
    $permission = Common::DOWNLOAD;

    return $this->filebrowserHelper->checkGroupPermission($group, $this->account, $permission);
  }

}
