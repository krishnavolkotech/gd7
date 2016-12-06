<?php

/**
 * @file
 * Contains Drupal\favorites\Plugin\Block\StarFavAddBlock.
 */

namespace Drupal\favorites\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\favorites\FavoriteStorage;
use Drupal\Core\Url;

/**
 * Provides a 'favorites' block.
 *
 * @Block(
 *   id = "star_favorites_block",
 *   admin_label = @Translation("Add To Favorites"),
 * )
 */
class StarFavAddBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'manage favorites');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form = \Drupal::formBuilder()->getForm('Drupal\favorites\Form\StarFavAddForm');

    return $form;
  }

}
