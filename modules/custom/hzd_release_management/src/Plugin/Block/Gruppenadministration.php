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
      '#markup' => $this->hzd_group_admin_links(),
    );
  }
  
  protected function blockAccess(AccountInterface $account) {
    if(\Drupal::currentUser()->id()) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }
  
  function hzd_group_admin_links() {
    return t('Will update the links once groups feature complete.');
  }

}
