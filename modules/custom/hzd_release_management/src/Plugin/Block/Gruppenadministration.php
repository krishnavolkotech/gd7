<?php

/**
 * @file
 * Contains \Drupal\hzd_release_management\Plugin\Block\Gruppenadministration.
 */
namespace Drupal\hzd_release_management\Plugin\Block;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides my custom block.
 *
 * @Block(
 *   id = "hzd_group_admin",
 *   admin_label = @Translation("HZD Gruppenadministration"),
 *   category = @Translation("Blocks")
 * )
 */
class Gruppenadministration extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->hzdGroupAdminLinks(),
      '#cache'=>['max-age'=>0],
    );
  }
  
  protected function blockAccess(AccountInterface $account) {
    if(\Drupal::currentUser()->id()) {
        $group = \Drupal::routeMatch()->getParameter('group');
        if(is_object($group)){
            $groupId = $group->id();
        }else{
            $groupId = $group;
        }
        if(\Drupal\cust_group\Controller\CustNodeController::isGroupAdmin($groupId)){
            return AccessResult::allowed();
        }
        return AccessResult::neutral();
    }
    else {
      return AccessResult::neutral();
    }
  }
  
  function hzdGroupAdminLinks() {
    $group = \Drupal::routeMatch()->getParameter('group');  
    if(is_object($group)){
      $groupId = $group->id();
    }else{
      $groupId = $group;
    }
    $menuHtml = '<ul class="menu nav">
    <li><a href="/group/'.$groupId.'/content">Contents</a></li>
    <li><a href="/group/'.$groupId.'/node/create">Content</a></li>
    <li><a href="/group/'.$groupId.'/approved-members">Users</a></li>
    <li><a href="/group/'.$groupId.'/downtime_settings">Disturbances and block times</a></li>
    <li><a href="/group/'.$groupId.'/problem_settings">Known Issues</a></li>
    <li><a href="/group/'.$groupId.'/release_settings">Releases</a></li>
    </ul>';

    return $this->t($menuHtml);
  }

}
